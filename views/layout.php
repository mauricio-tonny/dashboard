<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= htmlspecialchars($title ?? 'Dashboard Financeiro', ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <style>
        :root {
            color-scheme: light;
            --bg: #f3f6f8;
            --surface: #ffffff;
            --surface-soft: #eef3f5;
            --brand-dark: #111820;
            --brand-panel: #1b2630;
            --accent: #00a86b;
            --accent-strong: #008f5a;
            --accent-soft: #dff6ec;
            --text: #17212b;
            --muted: #65717d;
            --danger: #b42318;
            --border: #dce4e8;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                linear-gradient(180deg, rgba(17, 24, 32, 0.04), transparent 260px),
                var(--bg);
            color: var(--text);
        }
        body::before {
            content: "";
            display: block;
            height: 8px;
            background: linear-gradient(90deg, var(--brand-dark), var(--accent));
        }
        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 20px 60px;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(17, 24, 32, 0.06);
            margin-bottom: 20px;
        }
        .grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        h1, h2, h3 { margin-top: 0; }
        .muted { color: var(--muted); }
        .badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 6px;
            background: var(--accent-soft);
            color: var(--accent-strong);
            font-size: 0.9rem;
        }
        .error {
            color: var(--danger);
            margin-bottom: 12px;
        }
        label {
            display: block;
            margin-bottom: 14px;
            font-weight: 600;
        }
        input, select, button {
            width: 100%;
            margin-top: 6px;
            padding: 12px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 1rem;
        }
        button {
            background: var(--accent);
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .btn-brand {
            background: var(--accent);
            color: #fff;
        }
        .btn-brand:hover {
            background: var(--accent-strong);
            color: #fff;
        }
        .actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .actions form { margin: 0; }
        .inline-button {
            width: auto;
            padding: 10px 16px;
        }
    </style>
</head>
<body>
    <main class="container">
        <?= $content ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>
