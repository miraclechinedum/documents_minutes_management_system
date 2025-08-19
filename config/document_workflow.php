<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Document Workflow Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the Document Workflow
    | application. These settings control various aspects of document processing,
    | security, and integrations.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    */
    
    'max_upload_size' => env('DW_MAX_UPLOAD_SIZE', 52428800), // 50MB in bytes
    
    'allowed_file_types' => [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    
    'enable_virus_scan' => env('DW_ENABLE_VIRUS_SCAN', false),
    'clamscan_path' => env('DW_CLAMSCAN_PATH', '/usr/bin/clamscan'),
    'quarantine_infected_files' => true,
    
    /*
    |--------------------------------------------------------------------------
    | OCR Settings
    |--------------------------------------------------------------------------
    */
    
    'enable_ocr' => env('DW_ENABLE_OCR', false),
    'tesseract_path' => env('DW_TESSERACT_PATH', '/usr/bin/tesseract'),
    'ocr_languages' => ['eng'], // Tesseract language codes
    
    /*
    |--------------------------------------------------------------------------
    | PDF Processing Settings
    |--------------------------------------------------------------------------
    */
    
    'pdf_dpi' => 150, // DPI for PDF to image conversion
    'thumbnail_size' => 300, // Thumbnail width in pixels
    
    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    */
    
    'export_cache_ttl' => 3600, // Cache exported PDFs for 1 hour
    'enable_overlay_export' => true,
    'watermark_exported_pdfs' => false,
    
    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    
    'notifications' => [
        'email_enabled' => true,
        'database_enabled' => true,
        'send_virus_alerts' => true,
        'send_assignment_notifications' => true,
        'send_forwarding_notifications' => true,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    */
    
    'documents_per_page' => 12,
    'minutes_per_page' => 20,
    'audit_logs_per_page' => 50,
    
    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    */
    
    'search' => [
        'results_per_page' => 10,
        'max_results' => 100,
        'boost_title_matches' => 2.0,
        'boost_recent_documents' => 1.5,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    
    'integrations' => [
        'enable_api' => true,
        'api_rate_limit' => 60, // requests per minute
        'webhook_timeout' => 30, // seconds
    ],
];