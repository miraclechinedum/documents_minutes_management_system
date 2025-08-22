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
use Illuminate\Support\Str;
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

        $documents = $query->paginate(12)->appends($request->query());
        $departments = Department::where('is_active', true)->get();

        return view('documents.index', compact('documents', 'departments'));
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
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,tif,tiff|max:' . (config('document_workflow.max_upload_size', 52428800) / 1024),
            'assigned_to_type' => 'required|in:user,department',
            'assigned_to_id' => 'required|integer',
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date|after:today',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileExtension = $file->getClientOriginalExtension();
        $storedFileName = Str::uuid() . '.' . $fileExtension;

        // Store file
        $filePath = $file->storeAs('documents', $storedFileName, 'local');

        // Calculate checksum
        $checksum = hash_file('sha256', Storage::path($filePath));

        // Get pages count for PDF
        $pages = null;
        if ($file->getMimeType() === 'application/pdf') {
            $pages = $this->getPdfPageCount(Storage::path($filePath));
        }

        // Create document record
        $assignedToUserId = null;
        $assignedToDepartmentId = null;

        if ($request->assigned_to_type === 'user') {
            $assignedToUserId = $request->assigned_to_id;
        } else {
            $assignedToDepartmentId = $request->assigned_to_id;
        }

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

        // Generate thumbnail for images
        if (in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/tiff'])) {
            $this->generateThumbnail($document);
        }

        // Queue virus scan
        ScanDocumentForVirus::dispatch($document);

        activity()
            ->performedOn($document)
            ->log('document.uploaded');

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully and queued for scanning.');
    }

    public function show(Document $document)
    {
        $this->authorize('view', $document);

        $document->load(['creator', 'assignedToUser', 'assignedToDepartment', 'minutes.creator', 'routes.fromUser', 'routes.toUser', 'routes.toDepartment']);

        $minutes = $document->minutes()->whereHas('document', function ($query) {
            // Only show minutes the user can view
            return $query;
        })->get()->filter(function ($minute) {
            return $minute->canViewBy(Auth::user());
        });

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

    private function exportWithOverlay(Document $document)
    {
        // This would require more complex PDF processing
        // For now, fallback to appendix mode
        return $this->exportWithAppendix($document);
    }
}
