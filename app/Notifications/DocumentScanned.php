<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentScanned extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Document $document,
        public string $scanResult
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
        $mailMessage = (new MailMessage)
            ->line('A document has been scanned and processed.');

        if ($this->scanResult === 'clean') {
            $mailMessage
                ->subject('Document Ready: ' . $this->document->title)
                ->line('The document "' . $this->document->title . '" has been scanned and is ready for review.')
                ->action('View Document', route('documents.show', $this->document));
        } else {
            $mailMessage
                ->subject('Security Alert: Document Infected')
                ->line('The document "' . $this->document->title . '" has been flagged as infected and quarantined.')
                ->line('Please contact your system administrator.');
        }

        return $mailMessage;
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
            'scan_result' => $this->scanResult,
            'message' => $this->scanResult === 'clean' 
                ? 'Document is ready for review'
                : 'Document has been quarantined due to security concerns',
        ];
    }
}