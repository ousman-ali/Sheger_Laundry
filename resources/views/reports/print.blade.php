<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>{{ $title }}</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, Arial, "Apple Color Emoji", "Segoe UI Emoji"; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        thead { background: #f9fafb; }
        .container { padding: 10px; }
        h2 { margin: 0 0 8px; }
        @media print { .no-print { display:none; } }
    </style>
    <script>window.addEventListener('load', () => window.print());</script>
    </head>
<body>
    <div class="container">
        <div class="no-print" style="text-align:right; margin-bottom:6px;">
            <button onclick="window.print()">Print</button>
        </div>
        <h2>{{ $title }}</h2>
        <table>
            <thead>
                <tr>
                    @foreach($columns as $c)
                        <th>{{ $c }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        @foreach($r as $v)
                            <td>{{ $v }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columns) }}" style="text-align:center; color:#6b7280;">No data to display</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
