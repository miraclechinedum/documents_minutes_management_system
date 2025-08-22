<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $document->title }} - Export with Minutes</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #000;
            background: white;
            padding: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .header .subtitle {
            font-size: 12pt;
            color: #666;
        }

        .document-info {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        .info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 5px 10px;
            border-bottom: 1px dotted #ccc;
        }

        .info-label {
            font-weight: bold;
            width: 30%;
        }

        .info-value {
            width: 70%;
        }

        .status-badge,
        .priority-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 9pt;
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

        .document-placeholder {
            margin: 30px 0;
            padding: 40px;
            border: 2px dashed #ccc;
            text-align: center;
            background-color: #fafafa;
            color: #666;
            font-style: italic;
        }

        .minutes-section {
            margin-top: 40px;
            page-break-before: auto;
        }

        .minutes-header {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #000;
            text-align: center;
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
            margin-bottom: 10px;
            font-size: 10pt;
        }

        .minute-author {
            font-weight: bold;
            color: #007cba;
        }

        .minute-date {
            color: #666;
            font-style: italic;
            float: right;
        }

        .minute-visibility {
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            float: right;
            margin-left: 10px;
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
            clear: both;
        }

        .minute-overlay-info {
            margin-top: 8px;
            font-size: 9pt;
            color: #666;
            font-style: italic;
        }

        .minute-forwarded {
            margin-top: 10px;
            padding: 8px;
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
            font-size: 9pt;
        }

        .no-minutes {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 40px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }

        @page {
            margin: 1in;
            size: A4;
        }

        .document-preview {
            text-align: center;
            margin: 20px 0;
            border: 1px solid #ccc;
            min-height: 600px;
        }

        .document-preview iframe {
            width: 100%;
            height: 600px;
            border: none;
        }

        .document-alternative {
            padding: 40px;
            text-align: center;
            color: #666;
            font-style: italic;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $document->title }}</h1>
        <div class="subtitle">Document with Minutes - {{ now()->format('F j, Y') }}</div>
    </div>

    <!-- Original Document Content -->
    <div class="document-preview">
        @if(in_array($document->mime_type, ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/tiff']))
        <iframe src="{{ route('documents.preview', $document) }}">
            <p>Your browser does not support embedded documents.
                <a href="{{ route('documents.download', $document) }}">Download the document</a>
            </p>
        </iframe>
        @else
        <div class="document-alternative">
            <p><strong>{{ $document->file_name }}</strong></p>
            <p>This document type cannot be previewed inline.</p>
            <p>
                <a href="{{ route('documents.download', $document) }}">
                    Download and view the original document
                </a>
            </p>
        </div>
        @endif
    </div>

    <!-- Minutes Section -->
    <div class="minutes-section">
        <div class="minutes-header">
            MINUTES & ANNOTATIONS ({{ $minutes->count() }} {{ $minutes->count() === 1 ? 'minute' : 'minutes' }})
        </div>

        @forelse($minutes as $minute)
        <div class="minute-item">
            <div class="minute-header">
                <span class="minute-author">{{ $minute->creator->name }}</span>
                <span class="minute-date">{{ $minute->created_at->format('F j, Y \a\t g:i A') }}</span>
                <span class="minute-visibility visibility-{{ $minute->visibility }}">
                    {{ ucfirst($minute->visibility) }}
                </span>
                @if($minute->hasOverlay())
                <span class="minute-visibility" style="background-color: #e1bee7; color: #4a148c;">
                    Page {{ $minute->page_number }}
                </span>
                @endif
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

    <!-- Footer -->
    <div class="footer">
        <p>Generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
        <p>Document Workflow & Minutes Management System</p>
    </div>
</body>

</html>