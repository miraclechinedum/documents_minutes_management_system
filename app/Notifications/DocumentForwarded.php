<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\Minute;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentForwarded extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Document $document,
        public Minute $minute
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Document Forwarded: ' . $this->document->title)
            ->line('A document has been forwarded to you for review.')
            ->line('Document: ' . $this->document->title)
            ->line('From: ' . $this->minute->creator->name)
            ->line('Message: ' . substr($this->minute->body, 0, 200) . '...')
            ->action('View Document', route('documents.show', $this->document));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'document_title' => $this->document->title,
            'minute_id' => $this->minute->id,
            'from_user' => $this->minute->creator->name,
            'message' => 'Document forwarded to you',
        ];
    }
}