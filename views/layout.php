<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title><?= htmlspecialchars($title ?? 'Dashboard Financeiro', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/brand/favicon.svg?v=<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
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
        .app-shell {
            display: grid;
            flex: 1;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: calc(100vh - 8px);
        }
        .app-sidebar {
            background:
                radial-gradient(circle at top left, rgba(20, 184, 166, 0.28), transparent 32%),
                linear-gradient(180deg, var(--brand-deep), var(--brand-dark));
            color: #fff;
            padding: 24px 18px;
            position: sticky;
            top: 0;
            height: calc(100vh - 8px);
            overflow-y: auto;
        }
        .sidebar-brand {
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            display: flex;
            gap: 12px;
            padding-bottom: 18px;
            text-decoration: none;
        }
        .sidebar-brand img {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 12px;
            max-width: 168px;
            padding: 8px;
            width: 100%;
        }
        .sidebar-user {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            margin: 18px 0;
            padding: 14px;
        }
        .sidebar-user strong,
        .sidebar-user small {
            display: block;
        }
        .sidebar-user small {
            color: rgba(255, 255, 255, 0.72);
            margin-top: 4px;
        }
        .nav-group {
            margin-bottom: 10px;
        }
        .nav-group-title {
            color: rgba(255, 255, 255, 0.48);
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            margin: 18px 10px 8px;
            text-transform: uppercase;
        }
        .app-nav a,
        .app-nav button {
            align-items: center;
            background: transparent;
            border: 0;
            border-radius: 12px;
            color: rgba(255, 255, 255, 0.82);
            display: flex;
            gap: 10px;
            margin: 2px 0;
            padding: 11px 12px;
            text-align: left;
            text-decoration: none;
            width: 100%;
        }
        .app-nav a:hover,
        .app-nav button:hover,
        .app-nav a.is-active {
            background: rgba(255, 255, 255, 0.12);
            box-shadow: none;
            color: #fff;
            transform: none;
        }
        .app-nav .bi {
            color: var(--accent);
            font-size: 1.05rem;
            width: 22px;
        }
        .nav-subitem {
            font-size: 0.93rem;
            margin-left: 16px !important;
            padding-left: 16px !important;
        }
        .logout-nav {
            margin-top: 20px;
        }
        .app-main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .mobile-topbar {
            align-items: center;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: none;
            gap: 12px;
            justify-content: space-between;
            padding: 12px 16px;
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .mobile-topbar img {
            max-width: 160px;
        }
        .mobile-menu-toggle {
            width: auto;
        }
        .container {
            max-width: 1080px;
            margin: 0 auto;
            padding: 32px 20px 60px;
            width: 100%;
            flex: 1;
        }
        .app-main .container {
            max-width: 1240px;
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
        .dashboard-hero {
            align-items: center;
            background:
                linear-gradient(135deg, rgba(20, 184, 166, 0.16), transparent 34%),
                linear-gradient(135deg, var(--surface), #eef7fb);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            display: flex;
            justify-content: space-between;
            margin-bottom: 22px;
            padding: 28px;
        }
        .hero-version {
            background: var(--brand-dark);
            border-radius: 16px;
            color: #fff;
            min-width: 120px;
            padding: 14px 18px;
            text-align: center;
        }
        .hero-version small,
        .hero-version strong {
            display: block;
        }
        .metric-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        }
        .dashboard-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            margin-top: 24px;
        }
        .metric-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            padding: 22px;
        }
        .metric-card small,
        .metric-card strong {
            display: block;
        }
        .metric-card strong {
            font-size: 1.8rem;
            margin: 10px 0;
        }
        .metric-icon {
            align-items: center;
            background: var(--accent-soft);
            border-radius: 14px;
            color: var(--accent-strong);
            display: inline-flex;
            font-size: 1.3rem;
            height: 44px;
            justify-content: center;
            margin-bottom: 14px;
            width: 44px;
        }
        .mini-list {
            display: grid;
            gap: 10px;
        }
        .mini-list-item {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
        }
        .mini-list-item span {
            color: var(--muted);
            display: block;
            font-size: 0.9rem;
            margin-top: 4px;
        }
        .bar-chart {
            align-items: end;
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(12, 1fr);
            min-height: 190px;
        }
        .bar-chart-item {
            align-items: center;
            display: grid;
            gap: 8px;
            justify-items: center;
        }
        .bar-track {
            align-items: end;
            background: var(--surface-soft);
            border-radius: 999px;
            display: flex;
            height: 150px;
            overflow: hidden;
            width: 100%;
        }
        .bar-fill {
            background: linear-gradient(180deg, var(--accent-blue), var(--accent));
            border-radius: 999px;
            display: block;
            width: 100%;
        }
        .placeholder-card {
            overflow: hidden;
            position: relative;
        }
        .placeholder-illustration {
            align-items: center;
            background: linear-gradient(135deg, var(--brand-dark), var(--brand-panel));
            border-radius: 18px;
            color: #fff;
            display: grid;
            gap: 8px;
            justify-items: center;
            margin-top: 22px;
            padding: 34px 22px;
            text-align: center;
        }
        .placeholder-illustration .bi {
            color: var(--accent);
            font-size: 2.5rem;
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
            .app-shell {
                display: block;
            }
            .app-sidebar {
                display: none;
                height: auto;
                position: static;
            }
            .app-sidebar.is-open {
                display: block;
            }
            .mobile-topbar {
                display: flex;
            }
            .dashboard-hero {
                align-items: stretch;
                flex-direction: column;
            }
            .bar-chart {
                gap: 6px;
                overflow-x: auto;
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
    <?php if (isset($user)): ?>
        <?php
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $isActive = static fn (string $path): string => $currentPath === $path ? 'is-active' : '';
        ?>
        <div class="app-shell">
            <aside class="app-sidebar" id="appSidebar">
                <a class="sidebar-brand" href="/">
                    <img src="/assets/brand/logo.svg?v=<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>" alt="Oficina do DEV">
                </a>
                <div class="sidebar-user">
                    <strong><?= htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8') ?></strong>
                    <small><?= htmlspecialchars($user->role->label(), ENT_QUOTES, 'UTF-8') ?></small>
                </div>
                <nav class="app-nav" aria-label="Menu principal">
                    <div class="nav-group">
                        <a class="<?= $isActive('/') ?>" href="/"><span class="bi bi-speedometer2"></span>Dashboard</a>
                    </div>
                    <div class="nav-group">
                        <div class="nav-group-title">Financeiro</div>
                        <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_EXPENSE_TOTALS)): ?>
                            <a class="nav-subitem <?= $isActive('/finance/payable') ?>" href="/finance/payable"><span class="bi bi-arrow-up-right-circle"></span>A pagar</a>
                        <?php endif; ?>
                        <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_INCOME_TOTALS)): ?>
                            <a class="nav-subitem <?= $isActive('/finance/receivable') ?>" href="/finance/receivable"><span class="bi bi-arrow-down-left-circle"></span>A receber</a>
                        <?php endif; ?>
                        <?php if ($user->canEdit()): ?>
                            <a class="nav-subitem <?= $isActive('/entries/create') ?>" href="/entries/create"><span class="bi bi-plus-circle"></span>Novo lancamento</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_SHOPPING)): ?>
                        <div class="nav-group">
                            <a class="<?= $isActive('/shopping') ?>" href="/shopping"><span class="bi bi-basket2"></span>Compras</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_CONTACTS)): ?>
                        <div class="nav-group">
                            <a class="<?= $isActive('/contacts') ?>" href="/contacts"><span class="bi bi-person-lines-fill"></span>Contatos</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_CATEGORY_REPORT)): ?>
                        <div class="nav-group">
                            <a class="<?= $isActive('/reports') ?>" href="/reports"><span class="bi bi-bar-chart-line"></span>Relatorios</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($user->hasRole(\App\Domain\Auth\Role::ADMIN)): ?>
                        <div class="nav-group">
                            <div class="nav-group-title">Sistema</div>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_USERS)): ?>
                                <a class="nav-subitem <?= $isActive('/admin/users') ?>" href="/admin/users"><span class="bi bi-people"></span>Usuarios</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_AUDIT_LOGS)): ?>
                                <a class="nav-subitem <?= $isActive('/admin/audit-logs') ?>" href="/admin/audit-logs"><span class="bi bi-journal-text"></span>Logs</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_BACKUPS)): ?>
                                <a class="nav-subitem <?= $isActive('/system/backup') ?>" href="/system/backup"><span class="bi bi-cloud-arrow-down"></span>Backup</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_SPREADSHEET_URL)): ?>
                                <a class="nav-subitem <?= $isActive('/system/sync') ?>" href="/system/sync"><span class="bi bi-clock-history"></span>Tempo de sincronizacao</a>
                                <a class="nav-subitem <?= $isActive('/system/categories') ?>" href="/system/categories"><span class="bi bi-tags"></span>Categoria (DR)</a>
                                <a class="nav-subitem <?= $isActive('/system/spreadsheet') ?>" href="/system/spreadsheet"><span class="bi bi-file-earmark-spreadsheet"></span>Gerenciar planilha</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_DISCORD_NOTIFICATIONS)): ?>
                                <a class="nav-subitem <?= $isActive('/system/discord') ?>" href="/system/discord"><span class="bi bi-discord"></span>Discord</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_SHOPPING_SETTINGS)): ?>
                                <a class="nav-subitem <?= $isActive('/admin/shopping-settings') ?>" href="/admin/shopping-settings"><span class="bi bi-sliders"></span>Configuracao compras</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <form class="logout-nav" method="post" action="/logout">
                        <button type="submit"><span class="bi bi-box-arrow-right"></span>Sair</button>
                    </form>
                </nav>
            </aside>
            <div class="app-main">
                <header class="mobile-topbar">
                    <img src="/assets/brand/logo.svg?v=<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>" alt="Oficina do DEV">
                    <button class="mobile-menu-toggle" type="button" data-menu-toggle>Menu</button>
                </header>
                <main class="container">
                    <?= $content ?>
                </main>
                <footer class="app-footer">
                    <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Dashboard Financeiro', ENT_QUOTES, 'UTF-8') ?>
                    v<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>
                </footer>
            </div>
        </div>
    <?php else: ?>
        <main class="container">
            <?= $content ?>
        </main>
        <footer class="app-footer">
            <?= htmlspecialchars($_ENV['APP_NAME'] ?? 'Dashboard Financeiro', ENT_QUOTES, 'UTF-8') ?>
            v<?= htmlspecialchars($_ENV['APP_VERSION'] ?? '0.1.0', ENT_QUOTES, 'UTF-8') ?>
        </footer>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js" integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q" crossorigin="anonymous"></script>
    <script>
        document.querySelector('[data-menu-toggle]')?.addEventListener('click', () => {
            document.querySelector('#appSidebar')?.classList.toggle('is-open');
        });
    </script>
</body>
</html>
