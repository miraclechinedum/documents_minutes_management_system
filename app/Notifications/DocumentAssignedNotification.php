<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Document;

class DocumentAssignedNotification extends Notification
{
    use Queueable;

    protected $document;
    protected $assignedBy;

    public function __construct(Document $document, $assignedBy = null)
    {
        $this->document = $document;
        $this->assignedBy = $assignedBy;
    }

    public function via($notifiable)
    {
        // store in database (notifications table). Add mail if you want email alerting.
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $assignedByName = $this->assignedBy ? $this->assignedBy->name : null;

        return [
            'document_id' => $this->document->id,
            'title' => 'New document assigned to you',
            'message' => "{$assignedByName} assigned the document \"{$this->document->title}\" to you.",
            'file_name' => $this->document->file_name,
            'file_path' => $this->document->file_path,
            'assigned_by_id' => $this->assignedBy ? $this->assignedBy->id : null,
            'assigned_at' => now()->toDateTimeString(),
            'route' => route('documents.show', $this->document->id),
        ];
    }
}
