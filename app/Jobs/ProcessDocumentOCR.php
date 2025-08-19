<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentOCR implements ShouldQueue
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
        if (!config('document_workflow.enable_ocr', false)) {
            return;
        }

        $tesseractPath = config('document_workflow.tesseract_path', '/usr/bin/tesseract');
        
        // Check if Tesseract is available
        if (!file_exists($tesseractPath)) {
            Log::warning('Tesseract not found, skipping OCR', [
                'document_id' => $this->document->id,
                'tesseract_path' => $tesseractPath
            ]);
            return;
        }

        $filePath = Storage::path($this->document->file_path);
        $extractedText = '';

        try {
            if ($this->document->mime_type === 'application/pdf') {
                // For PDF files, we would need to convert to images first
                // This is a simplified version - in production, you might use ImageMagick or similar
                $extractedText = $this->extractTextFromPdf($filePath);
            } else {
                // For image files, use Tesseract directly
                $extractedText = $this->extractTextFromImage($filePath);
            }

            // Update document with OCR text
            $this->document->update(['ocr_text' => $extractedText]);
            
            // Update search index
            $this->document->searchable();

            activity()
                ->performedOn($this->document)
                ->log('document.ocr_completed');

        } catch (\Exception $e) {
            Log::error('OCR processing failed', [
                'document_id' => $this->document->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function extractTextFromImage(string $filePath): string
    {
        $tesseractPath = config('document_workflow.tesseract_path', '/usr/bin/tesseract');
        $tempOutputPath = tempnam(sys_get_temp_dir(), 'ocr_output');
        
        $command = escapeshellcmd($tesseractPath) . ' ' . 
                  escapeshellarg($filePath) . ' ' . 
                  escapeshellarg($tempOutputPath) . ' -l eng';
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($tempOutputPath . '.txt')) {
            $text = file_get_contents($tempOutputPath . '.txt');
            unlink($tempOutputPath . '.txt');
            return $text;
        }

        return '';
    }

    private function extractTextFromPdf(string $filePath): string
    {
        // This is a simplified version. In production, you would:
        // 1. Convert PDF pages to images using ImageMagick or similar
        // 2. Run OCR on each image
        // 3. Concatenate the results
        
        // For now, we'll just log that PDF OCR is not fully implemented
        Log::info('PDF OCR processing requested but not fully implemented', [
            'document_id' => $this->document->id,
            'file_path' => $filePath
        ]);

        return '';
    }
}