<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Department;
use App\Models\User;
use App\Jobs\ScanDocumentForVirus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Notifications\DocumentAssigned;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Fpdi;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::query()->with(['creator', 'assignedToUser', 'assignedToDepartment']);

        // Apply filters based on user permissions and selections
        if (!Auth::user()->hasRole('admin')) {
            $query->where(function ($q) {
                $q->where('created_by', Auth::id())
                    ->orWhere('assigned_to_user_id', Auth::id())
                    ->orWhere('assigned_to_department_id', Auth::user()->department_id);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by assigned type
        if ($request->filter === 'my_docs') {
            $query->where('assigned_to_user_id', Auth::id());
        } elseif ($request->filter === 'dept_docs') {
            $query->where('assigned_to_department_id', Auth::user()->department_id);
        } elseif ($request->filter === 'created_by_me') {
            $query->where('created_by', Auth::id());
        }

        // Search
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('reference_number', 'like', "%{$searchTerm}%")
                    ->orWhere('file_name', 'like', "%{$searchTerm}%")
                    ->orWhere('ocr_text', 'like', "%{$searchTerm}%");
            });
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortBy, $sortDirection);

        // PAGINATE: 10 per page
        $documents = $query->paginate(10)->appends($request->query());

        // Pass departments and users so the embedded upload form can populate selects
        $departments = Department::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();

        return view('documents.index', compact('documents', 'departments', 'users'));
    }

    public function create()
    {
        $this->authorize('create', Document::class);

        $departments = Department::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();

        return view('documents.create', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Document::class);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:100|unique:documents',
            'description' => 'nullable|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,tif,tiff|max:' . ((int) (config('document_workflow.max_upload_size', 52428800) / 1024)),
            'assigned_to_type' => 'required|in:user,department',
            'assigned_to_id' => 'required|integer',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileExtension = $file->getClientOriginalExtension();
        $storedFileName = Str::uuid() . '.' . $fileExtension;

        // Store file on local disk under documents/
        $filePath = $file->storeAs('documents', $storedFileName, 'local');

        // Calculate checksum (sha256)
        $checksum = null;
        try {
            $checksum = hash_file('sha256', Storage::path($filePath));
        } catch (\Exception $e) {
            Log::warning('Failed to compute checksum for file: ' . ($filePath ?? 'n/a') . ' - ' . $e->getMessage());
        }

        // Get pages count for PDF files
        $pages = null;
        try {
            if ($file->getMimeType() === 'application/pdf') {
                $pages = $this->getPdfPageCount(Storage::path($filePath));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to determine PDF page count: ' . $e->getMessage());
            $pages = null;
        }

        // Determine assignment IDs
        $assignedToUserId = null;
        $assignedToDepartmentId = null;

        if ($request->assigned_to_type === 'user') {
            $assignedToUserId = $request->assigned_to_id;
        } else {
            $assignedToDepartmentId = $request->assigned_to_id;
        }

        // Create the document record
        $document = Document::create([
            'title' => $request->title,
            'reference_number' => $request->reference_number,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'checksum' => $checksum,
            'pages' => $pages,
            'status' => Document::STATUS_QUARANTINED,
            'created_by' => Auth::id(),
            'assigned_to_user_id' => $assignedToUserId,
            'assigned_to_department_id' => $assignedToDepartmentId,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'description' => $request->description,
        ]);

        // Generate thumbnail for images (non-blocking method call — keeps controller simple)
        try {
            if (in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/tiff'])) {
                $this->generateThumbnail($document);
            }
        } catch (\Exception $e) {
            Log::warning('Thumbnail generation failed for document ' . $document->id . ': ' . $e->getMessage());
        }

        // Queue virus scan job
        try {
            ScanDocumentForVirus::dispatch($document);
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch virus scan for document ' . $document->id . ': ' . $e->getMessage());
        }

        // Activity log
        activity()
            ->performedOn($document)
            ->log('document.uploaded');

        // Dispatch notifications to assignee(s)
        try {
            $assignedBy = Auth::user();

            if ($request->assigned_to_type === 'user' && $assignedToUserId) {
                $recipient = User::where('is_active', true)->find($assignedToUserId);
                if ($recipient) {
                    \Illuminate\Support\Facades\Notification::send(
                        $recipient,
                        new \App\Notifications\DocumentAssignedNotification($document, $assignedBy)
                    );
                }
            } elseif ($request->assigned_to_type === 'department' && $assignedToDepartmentId) {
                // notify all active users in the department
                $recipients = User::where('department_id', $assignedToDepartmentId)
                    ->where('is_active', true)
                    ->get();

                if ($recipients->isNotEmpty()) {
                    \Illuminate\Support\Facades\Notification::send(
                        $recipients,
                        new \App\Notifications\DocumentAssignedNotification($document, $assignedBy)
                    );
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to notify assignees for document ' . $document->id . ': ' . $e->getMessage());
        }

        $successMessage = 'Document uploaded successfully and queued for scanning.';

        if ($request->ajax() || $request->wantsJson()) {
            // return minimal JSON that frontend can use
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'document' => [
                    'id' => $document->id,
                    'title' => $document->title,
                    'created_by' => $document->creator?->name,
                    'created_at' => $document->created_at?->toDateTimeString(),
                ],
            ], 201);
        }

        return redirect()->route('documents.index')->with('success', $successMessage);
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        // Load document relations (light)
        $document->load(['creator', 'assignedToUser', 'assignedToDepartment', 'routes.fromUser', 'routes.toUser', 'routes.toDepartment']);

        // Prepare minutes payload for the JS viewer — include relative storage URLs
        $minutes = $document->minutes()->with('creator')->get()
            ->filter(function ($minute) {
                return $minute->canViewBy(Auth::user());
            })
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'body' => $m->body,
                    'visibility' => $m->visibility,
                    'page_number' => $m->page_number,
                    'pos_x' => $m->pos_x,
                    'pos_y' => $m->pos_y,
                    'box_style' => $m->box_style,
                    'creator' => [
                        'id' => $m->creator?->id,
                        'name' => $m->creator?->name ?? 'Unknown',
                    ],
                    'attachment_path' => $m->attachment_path,
                    // relative URL ensures same host/port as current page
                    'attachment_url' => $m->attachment_path ? '/storage/' . ltrim($m->attachment_path, '/') : null,
                    'created_at' => $m->created_at ? $m->created_at->format('F j, Y g:i A') : null,
                ];
            })->values()->toArray();

        return view('documents.show', compact('document', 'minutes'));
    }

    public function edit(Document $document)
    {
        $this->authorize('update', $document);

        $departments = Department::where('is_active', true)->get();
        $users = User::where('is_active', true)->get();

        return view('documents.edit', compact('document', 'departments', 'users'));
    }

    public function update(Request $request, Document $document)
    {
        $this->authorize('update', $document);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:100|unique:documents,reference_number,' . $document->id,
            'description' => 'nullable|string',
            'assigned_to_type' => 'required|in:user,department',
            'assigned_to_id' => 'required|integer',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date|after:today',
            'status' => 'required|in:quarantined,scanning,infected,received,in_progress,completed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $assignedToUserId = null;
        $assignedToDepartmentId = null;

        if ($request->assigned_to_type === 'user') {
            $assignedToUserId = $request->assigned_to_id;
        } else {
            $assignedToDepartmentId = $request->assigned_to_id;
        }

        $document->update([
            'title' => $request->title,
            'reference_number' => $request->reference_number,
            'assigned_to_user_id' => $assignedToUserId,
            'assigned_to_department_id' => $assignedToDepartmentId,
            'priority' => $request->priority,
            'due_date' => $request->due_date,
            'description' => $request->description,
            'status' => $request->status,
        ]);

        return redirect()->route('documents.show', $document)->with('success', 'Document updated successfully.');
    }

    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        // Delete file
        if (Storage::exists($document->file_path)) {
            Storage::delete($document->file_path);
        }

        // Delete thumbnail
        if ($document->thumbnail_path && Storage::exists($document->thumbnail_path)) {
            Storage::delete($document->thumbnail_path);
        }

        $document->delete();

        activity()
            ->performedOn($document)
            ->log('document.deleted');

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }

    public function download(Document $document)
    {
        $this->authorize('view', $document);

        if (!Storage::exists($document->file_path)) {
            abort(404, 'File not found');
        }

        activity()
            ->performedOn($document)
            ->log('document.downloaded');

        return Storage::download($document->file_path, $document->file_name);
    }

    public function print(Document $document)
    {
        $this->authorize('view', $document);

        $document->load(['creator', 'assignedToUser', 'assignedToDepartment']);

        // Get minutes that the user can view
        $minutes = $document->minutes()->with('creator')->get()->filter(function ($minute) {
            return $minute->canViewBy(Auth::user());
        })->sortBy('created_at');

        activity()
            ->performedOn($document)
            ->log('document.printed');

        return view('documents.print', compact('document', 'minutes'));
    }

    private function getPdfPageCount(string $filePath): ?int
    {
        try {
            // Simple PDF page count - in production, use a proper PDF library
            $content = file_get_contents($filePath);
            $count = preg_match_all('/\/Page\W/', $content);
            return $count > 0 ? $count : 1;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function generateThumbnail(Document $document): void
    {
        // Simplified thumbnail generation - in production, use proper image processing
        // This would require ImageMagick or similar
        $thumbnailPath = 'thumbnails/' . Str::uuid() . '.jpg';
        $document->update(['thumbnail_path' => $thumbnailPath]);
    }

    public function preview(Document $document)
    {
        $this->authorize('view', $document);

        if (!Storage::exists($document->file_path)) {
            abort(404, 'File not found');
        }

        $file = Storage::get($document->file_path);
        $mime = Storage::mimeType($document->file_path);

        return response($file)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'inline; filename="' . $document->file_name . '"');
    }

    public function export(Document $document, Request $request)
    {
        $this->authorize('export', $document);

        $mode = $request->get('mode', 'appendix'); // 'appendix' or 'overlay'

        activity()
            ->performedOn($document)
            ->log('document.exported', ['mode' => $mode]);

        if ($mode === 'overlay') {
            return $this->exportWithOverlay($document);
        }

        return $this->exportWithAppendix($document);
    }

    private function exportWithAppendix(Document $document)
    {
        $document->load(['creator', 'assignedToUser', 'assignedToDepartment']);

        $minutes = $document->minutes()->whereHas('document', function ($query) {
            return $query;
        })->get()->filter(function ($minute) {
            return $minute->canViewBy(Auth::user());
        });

        // Create a new PDF that combines the original document and minutes
        if ($document->mime_type === 'application/pdf') {
            return $this->combinePdfWithMinutes($document, $minutes);
        }
        // For images, convert to PDF and add minutes
        else if (in_array($document->mime_type, ['image/jpeg', 'image/png', 'image/tiff'])) {
            return $this->convertImageToPdfWithMinutes($document, $minutes);
        }
        // For other file types, use the existing method
        else {
            $pdf = app('dompdf.wrapper');
            $html = view('documents.pdf-export', compact('document', 'minutes'))->render();
            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

            $filename = Str::slug($document->title) . '_with_minutes.pdf';
            return $pdf->download($filename);
        }
    }

    private function combinePdfWithMinutes($document, $minutes)
    {
        try {
            // Initialize FPDI
            $pdf = new Fpdi();

            // Get page count of original document
            $pageCount = $pdf->setSourceFile(Storage::path($document->file_path));

            // Import each page from the original document
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                // Add a page with the appropriate orientation
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, array($size['width'], $size['height']));
                $pdf->useTemplate($templateId);
            }

            // Add minutes as new pages
            if ($minutes->count() > 0) {
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(0, 10, 'Minutes and Annotations', 0, 1, 'C');
                $pdf->Ln(10);

                $pdf->SetFont('Arial', '', 12);
                foreach ($minutes as $minute) {
                    $pdf->SetFont('', 'B');
                    $pdf->Cell(0, 10, "{$minute->creator->name} - " . $minute->created_at->format('F j, Y \a\t g:i A'), 0, 1);

                    $pdf->SetFont('', '');
                    // Handle multi-line text
                    $pdf->MultiCell(0, 8, $minute->body);
                    $pdf->Ln(5);
                }
            }

            $filename = Str::slug($document->title) . '_with_minutes.pdf';

            return response($pdf->Output('S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            // Fallback to DomPDF if FPDI fails
            $pdf = app('dompdf.wrapper');
            $html = view('documents.pdf-export', compact('document', 'minutes'))->render();
            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

            $filename = Str::slug($document->title) . '_with_minutes.pdf';
            return $pdf->download($filename);
        }
    }

    private function convertImageToPdfWithMinutes($document, $minutes)
    {
        try {
            $pdf = new Fpdi();

            // Add the image as a page
            $pdf->AddPage();
            $imagePath = Storage::path($document->file_path);

            // Get image dimensions
            list($width, $height) = getimagesize($imagePath);

            // Calculate aspect ratio to fit on page (A4 size in mm)
            $pageWidth = 210;
            $pageHeight = 297;

            // Convert pixels to mm (assuming 72 DPI)
            $widthInMm = ($width / 72) * 25.4;
            $heightInMm = ($height / 72) * 25.4;

            $ratio = min($pageWidth / $widthInMm, $pageHeight / $heightInMm);
            $widthInMm *= $ratio;
            $heightInMm *= $ratio;

            // Center image on page
            $x = ($pageWidth - $widthInMm) / 2;
            $y = ($pageHeight - $heightInMm) / 2;

            $pdf->Image($imagePath, $x, $y, $widthInMm, $heightInMm);

            // Add minutes as additional pages
            if ($minutes->count() > 0) {
                $pdf->AddPage();
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->Cell(0, 10, 'Minutes and Annotations', 0, 1, 'C');
                $pdf->Ln(10);

                $pdf->SetFont('Arial', '', 12);
                foreach ($minutes as $minute) {
                    $pdf->SetFont('', 'B');
                    $pdf->Cell(0, 10, "{$minute->creator->name} - " . $minute->created_at->format('F j, Y \a\t g:i A'), 0, 1);

                    $pdf->SetFont('', '');
                    // Handle multi-line text
                    $pdf->MultiCell(0, 8, $minute->body);
                    $pdf->Ln(5);
                }
            }

            $filename = Str::slug($document->title) . '_with_minutes.pdf';

            return response($pdf->Output('S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            // Fallback to DomPDF if FPDI fails
            $pdf = app('dompdf.wrapper');
            $html = view('documents.pdf-export', compact('document', 'minutes'))->render();
            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->getDomPDF()->getOptions()->set('isRemoteEnabled', true);

            $filename = Str::slug($document->title) . '_with_minutes.pdf';
            return $pdf->download($filename);
        }
    }

    /**
     * Export the original document with minutes/annotations overlaid on the pages.
     */
    private function exportWithOverlay(Document $document)
    {
        $document->load(['creator', 'assignedToUser', 'assignedToDepartment']);

        $minutes = $document->minutes()->with('creator')->get()->filter(function ($minute) {
            return $minute->canViewBy(Auth::user());
        });

        if ($document->mime_type !== 'application/pdf') {
            return $this->exportWithAppendix($document);
        }

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile(Storage::path($document->file_path));
            $minutesByPage = $minutes->groupBy(function ($m) {
                return $m->page_number ?? 1;
            });

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = $size['width'] > $size['height'] ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $pageMinutes = $minutesByPage->get($pageNo, collect([]));

                foreach ($pageMinutes as $minute) {
                    if ($minute->pos_x === null || $minute->pos_y === null) {
                        continue;
                    }

                    $centerX = (float) $minute->pos_x * $size['width'];
                    $anchorY = (float) $minute->pos_y * $size['height']; // anchor (bottom center)

                    // If attachment exists and box_style provided, draw the image at recorded normalized size
                    if ($minute->attachment_path && !empty($minute->box_style) && is_array($minute->box_style)) {
                        $box = $minute->box_style;
                        $normW = isset($box['w']) ? (float)$box['w'] : 0.15;
                        $normH = isset($box['h']) ? (float)$box['h'] : 0;
                        // compute target size
                        $imgW = max(10, $normW * $size['width']);
                        // If height not provided, scale by image aspect ratio
                        $imgH = $normH > 0 ? max(10, $normH * $size['height']) : null;

                        $attachmentFullPath = Storage::path($minute->attachment_path);

                        // Try to get image size if imgH is null
                        if ($imgH === null) {
                            try {
                                [$wPx, $hPx] = getimagesize($attachmentFullPath);
                                if ($wPx > 0 && $hPx > 0) {
                                    $ratio = $hPx / $wPx;
                                    $imgH = $imgW * $ratio;
                                } else {
                                    $imgH = $imgW * 0.7;
                                }
                            } catch (\Exception $e) {
                                $imgH = $imgW * 0.7;
                            }
                        }

                        // center horizontally on centerX, bottom at anchorY
                        $imgLeft = $centerX - ($imgW / 2);
                        $imgTop = $anchorY - $imgH;

                        $pad = 4;
                        if ($imgLeft < $pad) $imgLeft = $pad;
                        if ($imgLeft + $imgW > $size['width'] - $pad) $imgLeft = $size['width'] - $imgW - $pad;
                        if ($imgTop < $pad) $imgTop = $pad;
                        if ($imgTop + $imgH > $size['height'] - $pad) $imgTop = $size['height'] - $imgH - $pad;

                        try {
                            $pdf->Image($attachmentFullPath, $imgLeft, $imgTop, $imgW, $imgH);
                        } catch (\Exception $e) {
                            // If image drawing fails, fallback to text note below
                            // no-op here; will fall through to text addition if needed
                        }

                        // also add author and timestamp under image (small)
                        $pdf->SetFont('Arial', 'B', 7);
                        $author = $minute->creator ? $minute->creator->name : 'Unknown';
                        $timestamp = $minute->created_at ? $minute->created_at->format('F j, Y g:i A') : '';
                        $pdf->SetXY($imgLeft, $imgTop + $imgH + 1);
                        $pdf->Cell(min($imgW, 80), 4, mb_strimwidth($author, 0, 30, '…'), 0, 0, 'L');
                        $pdf->Cell($imgW - min($imgW, 80), 4, $timestamp, 0, 0, 'R');

                        continue;
                    }

                    // Fallback: render text note (original behavior)
                    $noteWidth = min(140, $size['width'] * 0.25);
                    $noteHeight = min(64, $size['height'] * 0.15);
                    $noteLeft = $centerX - ($noteWidth / 2);
                    $pad = 6;
                    if ($noteLeft < $pad) $noteLeft = $pad;
                    if ($noteLeft + $noteWidth > $size['width'] - $pad) $noteLeft = $size['width'] - $noteWidth - $pad;
                    $noteTop = $anchorY - $noteHeight;
                    if ($noteTop < $pad) $noteTop = $pad;
                    if ($noteTop + $noteHeight > $size['height'] - $pad) $noteTop = $size['height'] - $noteHeight - $pad;

                    $pdf->SetFillColor(255, 249, 196);
                    $pdf->SetDrawColor(200, 180, 80);
                    $pdf->SetLineWidth(0.4);
                    $pdf->Rect($noteLeft, $noteTop, $noteWidth, $noteHeight, 'DF');

                    $pdf->SetTextColor(20, 20, 20);
                    $pdf->SetFont('Arial', '', 8);
                    $text = trim((string)$minute->body);
                    if (mb_strlen($text) > 1500) {
                        $text = mb_substr($text, 0, 1500) . '…';
                    }
                    $innerX = $noteLeft + 4;
                    $innerWidth = $noteWidth - 8;
                    $pdf->SetXY($innerX, $noteTop + 4);
                    $pdf->MultiCell($innerWidth, 4.2, $text, 0);
                    $pdf->SetFont('Arial', 'B', 7);
                    $author = $minute->creator ? $minute->creator->name : 'Unknown';
                    $timestamp = $minute->created_at ? $minute->created_at->format('F j, Y g:i A') : '';
                    $bottomY = $noteTop + $noteHeight - 8;
                    $pdf->SetXY($innerX, $bottomY);
                    $half = floor($innerWidth / 2);
                    $pdf->Cell($half, 4, mb_strimwidth($author, 0, 30, '…'), 0, 0, 'L');
                    $pdf->Cell($innerWidth - $half, 4, $timestamp, 0, 0, 'R');
                }
            }

            $filename = \Illuminate\Support\Str::slug($document->title) . '_annotated.pdf';
            return response($pdf->Output('S'), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('PDF overlay export failed: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->exportWithAppendix($document);
        }
    }
}
