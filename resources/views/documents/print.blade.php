<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }} — Export with Annotations</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #fff;
        }

        .page {
            position: relative;
            margin: 20px auto;
            box-shadow: 0 0 4px rgba(0, 0, 0, 0.15);
            page-break-after: always;
        }

        .page img {
            display: block;
            width: 100%;
            height: auto;
        }

        .pin {
            position: absolute;
            transform: translate(-50%, -100%);
            background: #fffa8b;
            border: 1px solid #e0c200;
            border-radius: 6px;
            padding: 4px 6px;
            font-size: 12px;
            max-width: 240px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        }

        .pin .meta {
            display: block;
            font-size: 10px;
            margin-top: 2px;
            color: #555;
        }

        @media print {
            body {
                margin: 0;
            }

            .page {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    @foreach ($pages as $i => $page)
    <div class="page" style="width:{{ $page['width'] }}px; height:{{ $page['height'] }}px;">
        <img src="{{ $page['dataUrl'] }}" alt="Page {{ $i+1 }}">

        @foreach ($minutes->where('page_number', $i+1) as $minute)
        <div class="pin" style="left: {{ $minute->pos_x * 100 }}%; top: {{ $minute->pos_y * 100 }}%;">
            {{ $minute->body }}
            <span class="meta">
                {{ $minute->creator?->name ?? 'Unknown' }} •
                {{ $minute->created_at?->format('jS F, Y g:i A') }}
            </span>
        </div>
        @endforeach
    </div>
    @endforeach
</body>

</html>