<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->title }} - Print View</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Times New Roman', serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: white;
            padding: 20px;
        }

        .print-header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .print-header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .print-header .subtitle {
            font-size: 14pt;
            color: #666;
        }

        .document-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .info-group {
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .info-value {
            display: inline-block;
        }

        .status-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-received {
            background-color: #d4edda;
            color: #155724;
        }

        .status-in-progress {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-quarantined {
            background-color: #f8d7da;
            color: #721c24;
        }

        .priority-high {
            background-color: #f8d7da;
            color: #721c24;
        }

        .priority-medium {
            background-color: #fff3cd;
            color: #856404;
        }

        .priority-low {
            background-color: #d4edda;
            color: #155724;
        }

        .document-content {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #ddd;
            min-height: 200px;
            background-color: #fafafa;
            text-align: center;
            color: #666;
        }

        .minutes-section {
            margin-top: 40px;
        }

        .minutes-header {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
        }

        .minute-item {
            margin-bottom: 25px;
            padding: 15px;
            border: 1px solid #ddd;
            border-left: 4px solid #007cba;
            background-color: #f8f9fa;
            page-break-inside: avoid;
        }

        .minute-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 11pt;
        }

        .minute-author {
            font-weight: bold;
            color: #007cba;
        }

        .minute-date {
            color: #666;
            font-style: italic;
        }

        .minute-visibility {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .visibility-public {
            background-color: #d4edda;
            color: #155724;
        }

        .visibility-department {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .visibility-internal {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .minute-body {
            margin-top: 10px;
            line-height: 1.8;
            text-align: justify;
        }

        .minute-overlay-info {
            margin-top: 8px;
            font-size: 10pt;
            color: #666;
            font-style: italic;
        }

        .minute-forwarded {
            margin-top: 10px;
            padding: 8px;
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
            font-size: 10pt;
        }

        .no-minutes {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        .print-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10pt;
            color: #666;
        }

        /* Print-specific styles */
        @media print {
            body {
                padding: 0;
                font-size: 11pt;
            }

            .print-header {
                margin-bottom: 20px;
            }

            .minute-item {
                page-break-inside: avoid;
                margin-bottom: 15px;
            }

            .minutes-section {
                page-break-before: auto;
            }
        }

        @page {
            margin: 1in;
            size: A4;
        }

        .document-preview {
            text-align: center;
            margin: 20px 0;
            border: 1px solid #ccc;
            min-height: 500px;
        }
        
        .document-preview iframe {
            width: 100%;
            height: 500px;
            border: none;
        }
        
        .document-alternative {
            padding: 30px;
            text-align: center;
            color: #666;
            font-style: italic;
            border: 2px dashed #ccc;
        }
    </style>
</head>

<body>
    <!-- Print Header -->
    <div class="print-header">
        <h1>{{ $document->title }}</h1>
        <div class="subtitle">Document with Minutes - {{ now()->format('F j, Y') }}</div>
    </div>

    <!-- Document Information -->
    <div class="document-info">
        <div>
            <div class="info-group">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-{{ str_replace('_', '-', $document->status) }}">
                        {{ ucfirst(str_replace('_', ' ', $document->status)) }}
                    </span>
                </span>
            </div>

            @if($document->reference_number)
            <div class="info-group">
                <span class="info-label">Reference:</span>
                <span class="info-value">{{ $document->reference_number }}</span>
            </div>
            @endif

            <div class="info-group">
                <span class="info-label">Priority:</span>
                <span class="info-value">
                    <span class="status-badge priority-{{ $document->priority }}">
                        {{ ucfirst($document->priority) }}
                    </span>
                </span>
            </div>

            <div class="info-group">
                <span class="info-label">Created By:</span>
                <span class="info-value">{{ $document->creator->name }}</span>
            </div>
        </div>

        <div>
            <div class="info-group">
                <span class="info-label">Created On:</span>
                <span class="info-value">{{ $document->created_at->format('F j, Y \a\t g:i A') }}</span>
            </div>

            @if($document->getCurrentAssignee())
            <div class="info-group">
                <span class="info-label">Assigned To:</span>
                <span class="info-value">{{ $document->getCurrentAssignee() }}</span>
            </div>
            @endif

            @if($document->due_date)
            <div class="info-group">
                <span class="info-label">Due Date:</span>
                <span class="info-value" style="color: #d32f2f; font-weight: bold;">{{ $document->due_date->format('F j,
                    Y') }}</span>
            </div>
            @endif

            <div class="info-group">
                <span class="info-label">File:</span>
                <span class="info-value">{{ $document->file_name }} ({{ number_format($document->file_size / 1024, 1) }}
                    KB)</span>
            </div>
        </div>
    </div>

    @if($document->description)
    <div style="margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9;">
        <strong>Description:</strong><br>
        {{ $document->description }}
    </div>
    @endif

    <!-- Original Document Content -->
    <div class="document-content" style="margin-bottom: 30px;">
        @if($document->mime_type === 'application/pdf')
        <!-- For PDF files -->
        <div style="text-align: center; margin: 20px 0; page-break-inside: avoid;">
            <div style="border: 2px solid #333; padding: 10px; background-color: #f9f9f9;">
                <p style="margin: 0; font-weight: bold;">📄 PDF Document: {{ $document->file_name }}</p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                    Original document content - {{ number_format($document->file_size / 1024, 1) }} KB
                    @if($document->pages) • {{ $document->pages }} page(s) @endif
                </p>
            </div>
            <!-- Embed PDF for browsers that support it -->
            <iframe src="{{ route('documents.preview', $document) }}" width="100%" height="600px"
                style="border: 1px solid #ccc;">
                <p>Your browser does not support embedded documents.
                    <a href="{{ route('documents.download', $document) }}">Download the document</a>
                </p>
            </iframe>
        </div>
        @elseif(in_array($document->mime_type, ['image/jpeg', 'image/jpg', 'image/png', 'image/tiff']))
        <!-- For image files -->
        <div style="text-align: center; margin: 20px 0; page-break-inside: avoid;">
            <div style="border: 2px solid #333; padding: 10px; background-color: #f9f9f9; margin-bottom: 10px;">
                <p style="margin: 0; font-weight: bold;">🖼️ Image Document: {{ $document->file_name }}</p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                    Original document content - {{ number_format($document->file_size / 1024, 1) }} KB
                </p>
            </div>
            <img src="{{ route('documents.download', $document) }}" alt="{{ $document->title }}"
                style="max-width: 100%; height: auto; border: 1px solid #333;">
        </div>
        @else
        <!-- For other file types -->
        <div
            style="text-align: center; margin: 20px 0; padding: 30px; border: 2px dashed #333; background-color: #f9f9f9;">
            <p style="margin: 0; font-weight: bold; font-size: 16px;">📎 {{ $document->file_name }}</p>
            <p style="margin: 10px 0; color: #666;">
                File Type: {{ strtoupper(pathinfo($document->file_name, PATHINFO_EXTENSION)) }} •
                Size: {{ number_format($document->file_size / 1024, 1) }} KB
            </p>
            <p style="margin: 15px 0 0 0; font-style: italic;">
                This file type cannot be displayed inline. Please download to view the original content.
            </p>
        </div>
        @endif
    </div>

    <!-- Minutes Section -->
    <div class="minutes-section">
        <div class="minutes-header">
            Minutes & Annotations ({{ $minutes->count() }} {{ $minutes->count() === 1 ? 'minute' : 'minutes' }})
        </div>

        @forelse($minutes as $minute)
        <div class="minute-item">
            <div class="minute-header">
                <div>
                    <span class="minute-author">{{ $minute->creator->name }}</span>
                    <span class="minute-date">{{ $minute->created_at->format('F j, Y \a\t g:i A') }}</span>
                </div>
                <div>
                    <span class="minute-visibility visibility-{{ $minute->visibility }}">
                        {{ ucfirst($minute->visibility) }}
                    </span>
                    @if($minute->hasOverlay())
                    <span class="minute-visibility" style="background-color: #e1bee7; color: #4a148c;">
                        Page {{ $minute->page_number }}
                    </span>
                    @endif
                </div>
            </div>

            <div class="minute-body">
                {{ $minute->body }}
            </div>

            @if($minute->hasOverlay())
            <div class="minute-overlay-info">
                📍 Positioned on page {{ $minute->page_number }} at coordinates ({{ number_format($minute->pos_x * 100,
                1) }}%, {{ number_format($minute->pos_y * 100, 1) }}%)
            </div>
            @endif

            @if($minute->getForwardedToName())
            <div class="minute-forwarded">
                <strong>📤 Forwarded to:</strong> {{ $minute->getForwardedToName() }}
            </div>
            @endif
        </div>
        @empty
        <div class="no-minutes">
            No minutes have been added to this document yet.
        </div>
        @endforelse
    </div>

    <!-- Print Footer -->
    <div class="print-footer">
        <p>Printed on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p>Document Workflow & Minutes Management System</p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
        
        // Close window after printing (optional)
        window.onafterprint = function() {
            // Uncomment the line below if you want to auto-close the print window
            // window.close();
        };
    </script>
</body>

</html>