<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Debug DB Info - REMOVE THIS</title>
    <style>
        body { font-family: monospace; background: #0F2D5C; color: #fff; padding: 40px; }
        .card { background: #fff; color: #0F2D5C; border-radius: 12px; padding: 24px; max-width: 640px; margin-top: 20px; }
        h1 { color: #f00; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px 12px; border-bottom: 1px solid #eee; }
        td:first-child { font-weight: bold; width: 180px; }
        .warn { background: #fee2e2; border: 2px solid #dc2626; color: #991b1b; padding: 12px; border-radius: 8px; margin-top: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>⚠️ TEMPORARY DEBUG - HAPUS ROUTE INI SETELAH PAKAI</h1>
    <div class="card">
        <table>
            <tr><td>DB_CONNECTION</td><td>{{ config('database.default') }}</td></tr>
            <tr><td>DB_HOST</td><td><strong>{{ config('database.connections.mysql.host') }}</strong></td></tr>
            <tr><td>DB_PORT</td><td>{{ config('database.connections.mysql.port') }}</td></tr>
            <tr><td>DB_DATABASE</td><td><strong>{{ config('database.connections.mysql.database') }}</strong></td></tr>
            <tr><td>DB_USERNAME</td><td>{{ config('database.connections.mysql.username') }}</td></tr>
            <tr><td>DB_PASSWORD</td><td>(disembunyikan)</td></tr>
        </table>
        <div class="warn">
            HAPUS route /_db-info dari routes/web.php dan view debug/db-info.blade.php,
            lalu push ulang setelah selesai.
        </div>
    </div>
</body>
</html>
