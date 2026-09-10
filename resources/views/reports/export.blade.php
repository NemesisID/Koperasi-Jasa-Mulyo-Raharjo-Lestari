<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        h1 { font-size: 14px; margin: 0 0 12px; }
        table { border-collapse: collapse; width: 100%; }
        td { border: 1px solid #ccc; padding: 3px 6px; }
        tr:first-child td { background: #eef3fc; font-weight: bold; }
    </style>
</head>
<body>
    <h1>{{ ucwords(str_replace('_', ' ', $title)) }}</h1>
    <table>
        @foreach ($rows as $row)
            <tr>
                @foreach ($row as $cell)
                    <td>{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>
</html>
