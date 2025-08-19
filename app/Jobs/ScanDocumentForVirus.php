<?php

namespace App\Jobs;

use App\Models\Document;
use App\Notifications\DocumentScanned;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ScanDocumentForVirus implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Document $document
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Update status to scanning
        $this->document->update(['status' => Document::STATUS_SCANNING]);
        
        activity()
            ->performedOn($this->document)
            ->log('document.scan_started');

        $filePath = Storage::path($this->document->file_path);
        
        if (!config('app.env') === 'testing' && config('document_workflow.enable_virus_scan', false)) {
            $clamScanPath = config('document_workflow.clamscan_path', '/usr/bin/clamscan');
            
            // Check if ClamAV is available
            if (!file_exists($clamScanPath)) {
                Log::warning('ClamAV not found, marking document as received', [
                    'document_id' => $this->document->id,
                    'clamscan_path' => $clamScanPath
                ]);
                $this->markAsReceived();
                return;
            }

            // Run virus scan
            $command = escapeshellcmd($clamScanPath) . ' ' . escapeshellarg($filePath);
            $output = [];
            $returnCode = 0;
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                // Clean file
                $this->markAsReceived();
            } else {
                // Infected file
                $this->markAsInfected();
            }
        } else {
            // Virus scanning disabled or testing environment
            $this->markAsReceived();
        }
    }

    private function markAsReceived(): void
    {
        $this->document->update(['status' => Document::STATUS_RECEIVED]);
        
        activity()
            ->performedOn($this->document)
            ->log('document.scan_clean');

        // Notify assigned users/departments
        $this->notifyRecipients();
        
        // Queue OCR job if enabled
        if (config('document_workflow.enable_ocr', false)) {
            ProcessDocumentOCR::dispatch($this->document);
        }
    }

    private function markAsInfected(): void
    {
        $this->document->update(['status' => Document::STATUS_INFECTED]);
        
        activity()
            ->performedOn($this->document)
            ->log('document.scan_infected');

        // Move file to quarantine
        $quarantinePath = 'quarantine/' . basename($this->document->file_path);
        Storage::move($this->document->file_path, $quarantinePath);
        $this->document->update(['file_path' => $quarantinePath]);

        // Notify admins
        $adminUsers = \App\Models\User::role('admin')->get();
        foreach ($adminUsers as $admin) {
            $admin->notify(new DocumentScanned($this->document, 'infected'));
        }
    }

    private function notifyRecipients(): void
    {
        $notification = new DocumentScanned($this->document, 'clean');

        if ($this->document->assigned_to_user_id) {
            $this->document->assignedToUser->notify($notification);
        }

        if ($this->document->assigned_to_department_id) {
            $departmentUsers = $this->document->assignedToDepartment->users;
            foreach ($departmentUsers as $user) {
                $user->notify($notification);
            }
        }
    }
}