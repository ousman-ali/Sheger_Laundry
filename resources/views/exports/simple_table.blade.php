<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $title ?? 'Export' }}</title>
    <style>
        :root { --bg: #ffffff; --fg: #111827; --muted: #6b7280; --thead: #f3f4f6; }
        html, body { margin: 0; padding: 0; background: var(--bg); color: var(--fg); font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Helvetica, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
        .container { padding: 16px; }
        h2 { margin: 0 0 12px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead tr { background: var(--thead); }
        th, td { text-align: left; padding: 8px; border-bottom: 1px solid #e5e7eb; }
        .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
        .toolbar .hint { color: var(--muted); font-size: 12px; }
        .btn { background:#111827; color:#fff; border:0; border-radius:6px; padding:6px 10px; font-size:12px; cursor:pointer; }
        @media print {
            @page { size: A4 landscape; margin: 10mm; }
            .toolbar { display:none !important; }
            body { padding:0; }
        }
    </style>
</head>
<body onload="window.print()" class="container">
    <div class="toolbar">
        <h2>{{ $title ?? 'Export' }}</h2>
        <button class="btn" onclick="window.print()">Print</button>
    </div>
    <table>
        <thead>
            <tr>
                @foreach(($columns ?? []) as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse(($rows ?? []) as $row)
                <tr>
                    @foreach($row as $val)
                        <td>{{ $val }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns ?? []) }}" style="text-align:center; color:#6b7280;">No data to export.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <script>
        // Optional: close the window after printing in some browsers
        window.onafterprint = () => { try { window.close(); } catch (e) {} };
    </script>
</body>
</html>
