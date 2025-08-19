<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Minute;
use App\Models\DocumentRoute;
use App\Models\User;
use App\Models\Department;
use App\Notifications\DocumentForwarded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MinuteController extends Controller
{
    public function store(Request $request, Document $document)
    {
        $this->authorize('create', Minute::class);
        $this->authorize('view', $document);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
            'visibility' => 'required|in:public,department,internal',
            'page_number' => 'nullable|integer|min:1',
            'pos_x' => 'nullable|numeric|min:0|max:1',
            'pos_y' => 'nullable|numeric|min:0|max:1',
            'box_style' => 'nullable|json',
            'forwarded_to_type' => 'nullable|in:user,department',
            'forwarded_to_id' => 'nullable|integer',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('minute-attachments');
        }

        $minute = Minute::create([
            'document_id' => $document->id,
            'body' => $request->body,
            'visibility' => $request->visibility,
            'page_number' => $request->page_number,
            'pos_x' => $request->pos_x,
            'pos_y' => $request->pos_y,
            'box_style' => $request->box_style ? json_decode($request->box_style, true) : null,
            'created_by' => Auth::id(),
            'forwarded_to_type' => $request->forwarded_to_type,
            'forwarded_to_id' => $request->forwarded_to_id,
            'attachment_path' => $attachmentPath,
        ]);

        // Handle forwarding
        if ($request->forwarded_to_type && $request->forwarded_to_id) {
            $this->handleForwarding($document, $minute, $request->forwarded_to_type, $request->forwarded_to_id);
        }

        activity()
            ->performedOn($minute)
            ->log('minute.created');

        return response()->json([
            'success' => true,
            'minute' => $minute->load('creator'),
        ]);
    }

    public function update(Request $request, Minute $minute)
    {
        $this->authorize('update', $minute);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
            'visibility' => 'required|in:public,department,internal',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $minute->update([
            'body' => $request->body,
            'visibility' => $request->visibility,
        ]);

        return response()->json([
            'success' => true,
            'minute' => $minute->load('creator'),
        ]);
    }

    public function destroy(Minute $minute)
    {
        $this->authorize('delete', $minute);

        // Delete attachment if exists
        if ($minute->attachment_path && Storage::exists($minute->attachment_path)) {
            Storage::delete($minute->attachment_path);
        }

        $minute->delete();

        activity()
            ->performedOn($minute)
            ->log('minute.deleted');

        return response()->json(['success' => true]);
    }

    private function handleForwarding(Document $document, Minute $minute, string $toType, int $toId): void
    {
        // Create document route record
        DocumentRoute::create([
            'document_id' => $document->id,
            'from_user_id' => Auth::id(),
            'to_type' => $toType,
            'to_id' => $toId,
            'minute_id' => $minute->id,
            'notes' => 'Forwarded with minute: ' . substr($minute->body, 0, 100),
            'routed_at' => now(),
        ]);

        // Update document assignment
        if ($toType === 'user') {
            $document->update([
                'assigned_to_user_id' => $toId,
                'assigned_to_department_id' => null,
                'status' => Document::STATUS_IN_PROGRESS,
            ]);
            
            // Notify user
            $user = User::find($toId);
            if ($user) {
                $user->notify(new DocumentForwarded($document, $minute));
            }
        } else {
            $document->update([
                'assigned_to_user_id' => null,
                'assigned_to_department_id' => $toId,
                'status' => Document::STATUS_IN_PROGRESS,
            ]);
            
            // Notify department users
            $department = Department::find($toId);
            if ($department) {
                foreach ($department->users as $user) {
                    $user->notify(new DocumentForwarded($document, $minute));
                }
            }
        }

        activity()
            ->performedOn($document)
            ->log('document.forwarded', [
                'to_type' => $toType,
                'to_id' => $toId,
                'minute_id' => $minute->id,
            ]);
    }
}