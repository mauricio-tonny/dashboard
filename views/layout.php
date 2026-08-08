<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= htmlspecialchars($title ?? 'Dashboard Financeiro', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/brand/favicon.svg?v=<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fa;
            --surface: #ffffff;
            --surface-soft: #edf4f8;
            --brand-dark: #082a5a;
            --brand-deep: #041a38;
            --brand-panel: #0b3268;
            --accent: #14b8a6;
            --accent-strong: #0f9488;
            --accent-soft: #d9fbf5;
            --accent-blue: #0ea5e9;
            --accent-green: #22c55e;
            --text: #102033;
            --muted: #64748b;
            --danger: #b42318;
            --border: #d9e4ec;
            --shadow: 0 18px 45px rgba(8, 42, 90, 0.12);
        }

        * { box-sizing: border-box; }
        html, body {
            min-height: 100%;
        }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background:
                linear-gradient(180deg, rgba(8, 42, 90, 0.06), transparent 280px),
                var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }
        body::before {
            content: "";
            display: block;
            height: 8px;
            background: linear-gradient(90deg, var(--brand-dark), var(--accent), var(--accent-blue));
        }
        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 20px 60px;
            width: 100%;
            flex: 1;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px;
            box-shadow: var(--shadow);
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
            background: var(--brand-dark);
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: 700;
            transition: background 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }
        button:hover {
            background: var(--brand-panel);
            box-shadow: 0 12px 24px rgba(8, 42, 90, 0.2);
            transform: translateY(-1px);
        }
        .btn-brand {
            background: var(--brand-dark);
            color: #fff;
        }
        .btn-brand:hover {
            background: var(--brand-panel);
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
        .button-danger {
            background: var(--danger);
        }
        .button-danger:hover {
            background: #8f1d14;
        }
        .notice {
            border-radius: 8px;
            font-weight: 700;
            margin-bottom: 20px;
            padding: 14px 16px;
        }
        .notice-success {
            background: var(--accent-soft);
            color: var(--accent-strong);
        }
        .notice-error {
            background: #fee4e2;
            color: var(--danger);
        }
        .form-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .form-actions {
            align-self: end;
        }
        .stacked-form {
            border-top: 1px solid var(--border);
            margin-top: 14px;
            padding-top: 14px;
        }
        .responsive-table {
            overflow-x: auto;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border-bottom: 1px solid var(--border);
            padding: 14px 12px;
            text-align: left;
            vertical-align: top;
        }
        th {
            color: var(--muted);
            font-size: 0.82rem;
            text-transform: uppercase;
        }
        .user-details summary {
            color: var(--brand-dark);
            cursor: pointer;
            font-weight: 800;
        }
        .inline-form {
            align-items: end;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .inline-form label {
            min-width: 220px;
        }
        .compact-form {
            display: grid;
            gap: 10px;
            margin-top: 12px;
        }
        .shopping-hero {
            align-items: center;
            display: flex;
            gap: 18px;
            justify-content: space-between;
        }
        .shopping-columns {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        .shopping-panel {
            min-height: 100%;
        }
        .month-pill {
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 12px 14px;
            text-decoration: none;
        }
        .month-pill small {
            color: var(--muted);
        }
        .month-pill.is-active {
            background: var(--accent-soft);
            border-color: rgba(20, 184, 166, 0.45);
        }
        .market-checklist,
        .shopping-list,
        .settings-list {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }
        .market-item,
        .shopping-item,
        .settings-item {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
        }
        .market-item {
            align-items: center;
            display: grid;
            gap: 12px;
            grid-template-columns: 48px auto minmax(0, 1fr) auto;
        }
        .item-photo {
            align-items: center;
            background: linear-gradient(135deg, var(--brand-dark), var(--accent-blue));
            border-radius: 12px;
            color: #fff;
            display: flex;
            font-size: 1.25rem;
            font-weight: 900;
            height: 48px;
            justify-content: center;
            width: 48px;
        }
        .check-form {
            margin: 0;
        }
        .check-button {
            min-width: 82px;
            padding: 9px 12px;
        }
        .market-item-copy {
            display: grid;
        }
        .market-item-copy small,
        .shopping-item small,
        .settings-item small {
            color: var(--muted);
            display: block;
            margin-top: 4px;
        }
        .shopping-item summary,
        .settings-item summary {
            cursor: pointer;
        }
        .is-done {
            opacity: 0.66;
        }
        .is-done strong {
            text-decoration: line-through;
        }
        .total-form {
            border-top: 1px solid var(--border);
            margin-top: 18px;
            padding-top: 18px;
        }
        .app-footer {
            color: var(--muted);
            font-size: 0.85rem;
            padding: 16px 20px 28px;
            text-align: center;
        }
        .auth-page {
            background:
                linear-gradient(135deg, rgba(8, 42, 90, 0.08) 0 25%, transparent 25% 100%),
                linear-gradient(180deg, #f7fafc 0%, #eef5f8 100%);
        }
        .auth-page .container {
            align-items: center;
            display: flex;
            justify-content: center;
            padding: 28px 18px 44px;
        }
        .auth-shell {
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(340px, 420px);
            max-width: 980px;
            min-height: 560px;
            width: 100%;
            background: var(--surface);
            border: 1px solid rgba(8, 42, 90, 0.1);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .auth-brand-panel {
            background:
                linear-gradient(135deg, rgba(20, 184, 166, 0.28), transparent 34%),
                linear-gradient(160deg, var(--brand-deep) 0%, var(--brand-dark) 60%, #0f477e 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 100%;
            padding: 42px;
        }
        .auth-brand-title {
            font-size: clamp(1.75rem, 3vw, 2.7rem);
            font-weight: 800;
            line-height: 1.08;
            margin: 0;
        }
        .auth-brand-copy {
            color: rgba(255, 255, 255, 0.78);
            font-size: 1rem;
            line-height: 1.6;
            margin: 18px 0 0;
            max-width: 360px;
        }
        .auth-brand-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, 1fr);
            margin-top: 36px;
            max-width: 300px;
        }
        .auth-brand-bar {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            min-height: 92px;
            position: relative;
        }
        .auth-brand-bar::after {
            background: linear-gradient(180deg, var(--accent-green), var(--accent));
            border-radius: 6px;
            bottom: 14px;
            content: "";
            left: 50%;
            position: absolute;
            transform: translateX(-50%);
            width: 28px;
        }
        .auth-brand-bar:nth-child(1)::after { height: 34px; }
        .auth-brand-bar:nth-child(2)::after { height: 54px; background: linear-gradient(180deg, var(--accent), #2dd4bf); }
        .auth-brand-bar:nth-child(3)::after { height: 74px; background: linear-gradient(180deg, #38bdf8, var(--accent-blue)); }
        .auth-brand-footnote {
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.86rem;
            margin: 34px 0 0;
        }
        .auth-form-panel {
            align-items: center;
            display: flex;
            padding: 38px;
        }
        .auth-card {
            width: 100%;
        }
        .auth-logo {
            display: block;
            height: auto;
            margin: 0 auto 26px;
            max-width: 340px;
            width: 100%;
        }
        .auth-lead {
            color: var(--muted);
            margin: 0 0 24px;
            text-align: center;
        }
        .auth-form-panel input {
            background: #f8fbfd;
            border-color: #cddbe4;
        }
        .auth-form-panel input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.16);
            outline: none;
        }
        .auth-submit {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-panel));
            margin-top: 8px;
        }
        .auth-submit:hover {
            background: linear-gradient(135deg, var(--brand-panel), #0f477e);
        }
        @media (max-width: 760px) {
            body::before {
                height: 6px;
            }
            .container {
                padding-left: 16px;
                padding-right: 16px;
            }
            .auth-page .container {
                align-items: stretch;
                padding: 18px 14px 28px;
            }
            .auth-shell {
                grid-template-columns: 1fr;
                min-height: auto;
            }
            .auth-brand-panel {
                padding: 28px 24px;
            }
            .auth-brand-grid,
            .auth-brand-footnote {
                display: none;
            }
            .auth-form-panel {
                padding: 28px 22px 30px;
            }
            .auth-logo {
                max-width: 290px;
            }
            .shopping-hero {
                align-items: stretch;
                flex-direction: column;
            }
            .market-item {
                grid-template-columns: 44px 1fr;
            }
            .market-item details,
            .market-item .check-form {
                grid-column: 1 / -1;
            }
        }
    </style>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <main class="container">
        <?= $content ?>
    </main>
    <footer class="app-footer">
        <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Dashboard Financeiro', ENT_QUOTES, 'UTF-8') ?>
        v<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
</body>
</html>
