<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $template->name }} | Print</title>
    <style>
        :root {
            --brand: #8B0000;
            --brand-dark: #660000;
            --ink: #111827;
            --muted: #6b7280;
            --paper: #ffffff;
            --page-bg: #f4f1f1;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, #faf7f7 0%, var(--page-bg) 100%);
            color: var(--ink);
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid #eadede;
        }

        .toolbar h1 {
            margin: 0;
            font-size: 1rem;
            font-weight: 800;
            color: var(--ink);
        }

        .toolbar p {
            margin: 4px 0 0;
            font-size: 0.8rem;
            color: var(--muted);
        }

        .toolbar-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            appearance: none;
            border: 0;
            border-radius: 12px;
            padding: 11px 16px;
            font-size: 0.85rem;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
            box-shadow: 0 10px 22px rgba(139, 0, 0, 0.16);
        }

        .btn-secondary {
            background: #fff;
            color: var(--brand);
            border: 1px solid #ead4d4;
        }

        .page-wrap {
            padding: 28px 18px 40px;
            display: flex;
            justify-content: center;
        }

        .sheet {
            width: min(100%, 210mm);
            background: var(--paper);
            border: 1px solid #e8dede;
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .sheet-header {
            padding: 18px 22px;
            border-bottom: 1px solid #f0e6e6;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            background: linear-gradient(180deg, #fff 0%, #fffafa 100%);
        }

        .sheet-header h2 {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 900;
            color: var(--ink);
        }

        .sheet-header .meta {
            margin-top: 6px;
            font-size: 0.8rem;
            color: var(--muted);
            line-height: 1.45;
        }

        .sheet-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 800;
            background: #f6f7fb;
            color: #374151;
        }

        .badge.brand {
            background: #fff1f1;
            color: var(--brand);
        }

        .sheet-body {
            padding: 20px 22px 26px;
        }

        .template-content {
            color: #111827;
        }

        .template-content table {
            width: 100%;
            border-collapse: collapse;
        }

        .template-content img {
            max-width: 100%;
        }

        .template-content .page-break {
            page-break-after: always;
            break-after: page;
        }

        @page {
            size: {{ $template->paper_size ?: 'A4' }} {{ $template->orientation ?: 'portrait' }};
            margin: 12mm;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none !important;
            }

            .page-wrap {
                padding: 0;
            }

            .sheet {
                width: 100%;
                border: 0;
                border-radius: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <div>
            <h1>{{ $template->name }}</h1>
            <p>Open this form, review the rendered content, then print it as a report or certificate.</p>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('dentist.dentist.report') }}" class="btn btn-secondary">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Reports
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                Print Now
            </button>
        </div>
    </div>

    <div class="page-wrap">
        <article class="sheet">
            <header class="sheet-header">
                <div>
                    <h2>{{ $template->name }}</h2>
                    <div class="meta">
                        {{ $template->code ?: 'Template Code N/A' }}<br>
                        {{ \Illuminate\Support\Str::headline($template->document_type) }}
                        @if($template->category)
                            • {{ $template->category }}
                        @endif
                    </div>
                </div>
                <div class="sheet-badges">
                    <span class="badge brand">{{ $template->paper_size ?: 'A4' }}</span>
                    <span class="badge">{{ \Illuminate\Support\Str::headline($template->orientation ?: 'portrait') }}</span>
                    <span class="badge">{{ $template->status }}</span>
                </div>
            </header>

            <section class="sheet-body">
                <div class="template-content">
                    {!! $renderedContent ?: '<p style="color:#6b7280;font-size:0.95rem;">No template content available.</p>' !!}
                </div>
            </section>
        </article>
    </div>
</body>
</html>
