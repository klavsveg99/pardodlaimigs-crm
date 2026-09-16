<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('images/favicon-32x32.jpg') }}">
    <title>404 · Lapa nav atrasta — Pārdod Laimīgs CRM</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #f8faf9;
            font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: #414042;
            text-align: center;
        }
        .card {
            max-width: 26rem;
            width: 100%;
            background: #fff;
            border: 1px solid #e5e8e7;
            border-radius: 1rem;
            padding: 2.5rem 2rem 2.25rem;
            box-shadow: 0 1px 3px rgba(40, 88, 84, 0.06);
        }
        .logo { height: 2.5rem; border-radius: 0.5rem; margin-bottom: 1.5rem; }
        .code {
            font-size: 3.25rem;
            font-weight: 800;
            line-height: 1;
            color: #285854;
            letter-spacing: -0.03em;
        }
        h1 { font-size: 1.25rem; margin: 0.75rem 0 0.5rem; color: #2b2d2c; }
        p { margin: 0.5rem 0 1.75rem; font-size: 0.9375rem; line-height: 1.55; color: #6b6f6e; }
        .btn {
            display: inline-block;
            padding: 0.625rem 1.25rem;
            background: #285854;
            color: #fff;
            font-size: 0.9375rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: background 0.15s ease;
        }
        .btn:hover { background: #1f4542; }
        .btn:focus-visible { outline: 2px solid #285854; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <img class="logo" src="{{ asset('images/favicon-180x180.jpg') }}" alt="Pārdod Laimīgs CRM">
        <div class="code">404</div>
        <h1>Lapa nav atrasta</h1>
        <p>Šī lapa neeksistē, tika izdzēsta vai jums nav piekļuves tai.</p>
        <a class="btn" href="/">Uz CRM sākumu</a>
    </main>
</body>
</html>
