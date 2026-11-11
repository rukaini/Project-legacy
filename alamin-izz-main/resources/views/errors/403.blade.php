<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Access Denied</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f9fafb; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: #fff; border-radius: 12px; padding: 48px; text-align: center; max-width: 420px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .icon { font-size: 48px; margin-bottom: 16px; }
        h1 { font-size: 24px; font-weight: 700; color: #111; margin-bottom: 8px; }
        p { color: #6b7280; margin-bottom: 24px; }
        a { display: inline-block; background: #7c3aed; color: #fff; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        a:hover { background: #6d28d9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔒</div>
        <h1>Access Denied</h1>
        <p>You do not have permission to view this page.</p>
        <a href="{{ url()->previous() }}">Go back</a>
    </div>
</body>
</html>
