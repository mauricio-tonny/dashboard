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
        .logout-nav button {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.22);
            color: #fff;
            margin-top: 8px;
        }
        .logout-nav button:hover {
            background: #fff;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.22);
            color: var(--brand-dark);
        }
        .app-main {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .sidebar-mobile-header {
            display: none;
        }
        .mobile-menu-overlay {
            background: rgba(4, 26, 56, 0.58);
            inset: 0;
            opacity: 0;
            pointer-events: none;
            position: fixed;
            transition: opacity 180ms ease;
            z-index: 29;
        }
        .mobile-menu-overlay.is-open {
            opacity: 1;
            pointer-events: auto;
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
        .mobile-menu-close {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.24);
            color: #fff;
            margin-top: 0;
            padding: 9px 12px;
            width: auto;
        }
        .mobile-menu-close:hover {
            background: #fff;
            color: var(--brand-dark);
            transform: none;
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
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
        }
        .page-hero {
            align-items: center;
            background:
                radial-gradient(circle at 12% 18%, rgba(20, 184, 166, 0.18), transparent 30%),
                linear-gradient(135deg, var(--surface), #eef7fb);
            border: 1px solid var(--border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            display: flex;
            gap: 22px;
            justify-content: space-between;
            margin-bottom: 24px;
            overflow: hidden;
            padding: 28px;
            position: relative;
        }
        .page-hero::after {
            background: linear-gradient(180deg, var(--accent), var(--accent-blue));
            border-radius: 999px;
            content: "";
            height: 110px;
            opacity: 0.12;
            position: absolute;
            right: -28px;
            top: -32px;
            width: 110px;
        }
        .page-hero-content {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            min-width: 0;
            position: relative;
            z-index: 1;
        }
        .page-hero-icon {
            align-items: center;
            background: var(--brand-dark);
            border-radius: 18px;
            color: #fff;
            display: inline-flex;
            flex: 0 0 auto;
            font-size: 1.5rem;
            height: 54px;
            justify-content: center;
            width: 54px;
        }
        .page-hero h1 {
            margin-bottom: 8px;
        }
        .page-hero-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
            position: relative;
            z-index: 1;
        }
        .section-card {
            border-radius: 18px;
        }
        .section-title {
            align-items: center;
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
        }
        .section-title .bi {
            color: var(--accent-strong);
        }
        .soft-panel {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
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
            align-items: center;
            background: var(--brand-dark);
            color: #fff;
            border: none;
            cursor: pointer;
            display: inline-flex;
            gap: 8px;
            justify-content: center;
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
        .button-light {
            background: var(--surface-soft);
            color: var(--text);
        }
        .button-light:hover {
            background: #dde9f1;
            color: var(--text);
        }
        .btn-close {
            background-color: transparent;
            box-shadow: none;
            margin-top: 0;
            padding: 0.5rem;
            transform: none;
            width: auto;
        }
        .btn-close:hover {
            background-color: transparent;
            box-shadow: none;
            transform: none;
        }
        .modal-title {
            align-items: center;
            display: inline-flex;
            gap: 8px;
        }
        .modal-footer-actions {
            align-items: end;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        .form-check-group {
            margin-bottom: 14px;
        }
        .field-label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .check-option {
            align-items: center;
            display: flex;
            gap: 8px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .check-option input {
            margin: 0;
            width: auto;
        }
        .sr-only {
            height: 1px;
            overflow: hidden;
            position: absolute;
            width: 1px;
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
        tbody tr {
            transition: background 140ms ease;
        }
        tbody tr:hover {
            background: #f8fbfd;
        }
        .user-details summary {
            align-items: center;
            color: var(--brand-dark);
            cursor: pointer;
            display: inline-flex;
            gap: 6px;
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
        .align-end-form {
            align-items: flex-end;
        }
        .align-end-form button {
            margin-bottom: 14px;
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
        .module-card-grid {
            display: grid;
            gap: 18px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }
        .module-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
            color: var(--text);
            padding: 20px;
            text-decoration: none;
        }
        .module-card:hover {
            color: var(--text);
            transform: translateY(-2px);
        }
        .module-card .bi {
            color: var(--accent-strong);
            display: block;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .shopping-panel {
            min-height: auto;
        }
        .wish-create-panel {
            margin-bottom: 14px;
            padding: 14px 16px;
        }
        .wish-create-panel .section-title {
            font-size: 1rem;
            margin-bottom: 8px;
        }
        .wish-form-grid {
            align-items: end;
            gap: 8px;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        }
        .wish-form-grid label {
            font-size: 0.84rem;
            margin-bottom: 0;
        }
        .wish-form-grid input,
        .wish-form-grid select,
        .wish-form-grid button {
            min-height: 40px;
            padding: 8px 10px;
        }
        .wish-form-grid .form-actions {
            align-self: end;
            display: flex;
            margin-bottom: 0;
        }
        .wish-form-grid .form-actions button,
        .finish-form .inline-button {
            justify-content: center;
            width: 100%;
        }
        .wish-form-grid .form-actions button {
            min-height: 40px;
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
        .shopping-items-panel {
            max-height: calc(100vh - 150px);
            overflow-y: auto;
        }
        .shopping-items-panel .shopping-list {
            margin-top: 12px;
        }
        .market-item,
        .shopping-item,
        .settings-item {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
        }
        .shopping-item-card {
            align-items: center;
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px;
        }
        .shopping-item-main {
            display: grid;
            min-width: 0;
        }
        .shopping-item-main strong,
        .shopping-item-main small {
            overflow-wrap: anywhere;
        }
        .shopping-item-actions {
            flex-wrap: wrap;
            justify-content: flex-end;
            margin-top: 0;
        }
        .vehicle-group {
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px;
        }
        .vehicle-group h3 {
            align-items: center;
            color: var(--brand-dark);
            display: flex;
            font-size: 1rem;
            gap: 8px;
            margin: 0 0 12px;
        }
        .vehicle-group-list {
            gap: 16px;
        }
        .market-item {
            display: block;
            min-width: 0;
        }
        .market-item-body {
            display: grid;
            gap: 10px;
            min-width: 0;
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
        .market-item-actions-row {
            align-items: center;
            border-top: 1px solid rgba(10, 61, 98, 0.08);
            display: grid;
            gap: 10px;
            padding-top: 10px;
            grid-template-columns: minmax(56px, 20%) minmax(0, 1fr);
        }
        .market-item-actions-row .item-photo {
            height: 100%;
            min-height: 48px;
            width: 100%;
        }
        .market-item-actions-row details {
            grid-column: 1 / -1;
            width: 100%;
        }
        .market-item-actions-row summary {
            align-items: center;
            display: inline-flex;
            gap: 8px;
        }
        .market-item-actions-row .check-form,
        .market-item-actions-row .is-static-check {
            min-width: 0;
        }
        .market-item-actions-row .check-form button,
        .market-item-actions-row .is-static-check {
            min-height: 48px;
            width: 100%;
        }
        .is-static-check {
            align-items: center;
            background: #eef7fb;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--muted);
            display: inline-flex;
            gap: 6px;
            justify-content: center;
        }
        .market-item-copy {
            display: grid;
            gap: 8px;
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .market-item-copy strong,
        .market-item-copy small {
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .market-item-meta,
        .invoice-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            min-width: 0;
        }
        .market-meta-chip,
        .invoice-badge {
            background: #eef7fb;
            border: 1px solid rgba(10, 61, 98, 0.08);
            border-radius: 999px;
            color: var(--muted);
            display: inline-flex;
            font-size: 0.86rem;
            line-height: 1.25;
            max-width: 100%;
            padding: 5px 10px;
        }
        .market-meta-chip.is-strong {
            color: var(--brand-dark);
            font-weight: 700;
        }
        .invoice-badge.is-done {
            background: rgba(20, 184, 166, 0.12);
            border-color: rgba(20, 184, 166, 0.22);
            color: #0f766e;
            font-weight: 700;
        }
        .invoice-badge.is-warn {
            background: rgba(245, 158, 11, 0.14);
            border-color: rgba(245, 158, 11, 0.24);
            color: #b45309;
            font-weight: 700;
        }
        .market-item details {
            min-width: 0;
        }
        @media (min-width: 768px) {
            .market-item-body {
                align-items: start;
                grid-template-columns: minmax(0, 1fr);
            }
            .market-item-copy {
                display: grid;
                gap: 10px;
            }
            .market-item-copy strong {
                line-height: 1.3;
            }
            .market-item-meta {
                justify-content: flex-start;
            }
            .market-item-actions-row {
                grid-template-columns: minmax(56px, 20%) minmax(0, 1fr);
            }
            .market-item-actions-row details {
                min-width: 0;
            }
        }
        .invoice-actions {
            margin-top: 10px;
        }
        .market-items-panel {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 14px;
            margin-top: 18px;
            padding: 0;
        }
        .market-items-panel > summary {
            align-items: center;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            list-style: none;
            padding: 14px 16px;
        }
        .market-items-panel > summary::-webkit-details-marker {
            display: none;
        }
        .market-items-panel > summary span {
            align-items: center;
            display: inline-flex;
            font-weight: 800;
            gap: 8px;
        }
        .market-items-panel > summary small {
            color: var(--muted);
        }
        .market-items-panel .market-checklist {
            border-top: 1px solid var(--border);
            max-height: calc(100vh - 260px);
            margin: 0;
            overflow-y: auto;
            padding: 14px;
        }
        .market-price-form {
            align-items: end;
        }
        .market-price-form [data-subtotal-preview] {
            background: #eef7fb;
            color: var(--brand-dark);
            font-weight: 800;
        }
        .purchased-items-panel {
            box-shadow: none;
        }
        .purchased-items-panel > summary {
            align-items: center;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            list-style: none;
        }
        .purchased-items-panel > summary::-webkit-details-marker {
            display: none;
        }
        .purchased-items-panel > summary span {
            align-items: center;
            display: inline-flex;
            font-weight: 800;
            gap: 8px;
        }
        .purchased-items-panel > summary small {
            color: var(--muted);
        }
        .checkbox-card {
            align-items: center;
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            display: flex;
            gap: 10px;
            margin-top: 12px;
            padding: 12px;
        }
        .checkbox-card input {
            width: auto;
        }
        .discord-card {
            max-width: 860px;
        }
        .discord-settings-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .discord-toggle-card {
            align-items: center;
            background: linear-gradient(135deg, #eef7fb, #f8fbfd);
            border: 1px solid var(--border);
            border-radius: 16px;
            cursor: pointer;
            display: flex;
            gap: 14px;
            margin: 0;
            padding: 16px;
        }
        .discord-toggle-card input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }
        .discord-toggle-card small {
            color: var(--muted);
            display: block;
            font-weight: 600;
            margin-top: 4px;
        }
        .discord-toggle-switch {
            background: #d6e1ea;
            border-radius: 999px;
            box-shadow: inset 0 0 0 1px rgba(15, 55, 80, 0.08);
            flex: 0 0 58px;
            height: 32px;
            position: relative;
            transition: background 180ms ease, box-shadow 180ms ease;
        }
        .discord-toggle-switch::after {
            background: #ffffff;
            border-radius: 50%;
            box-shadow: 0 8px 16px rgba(15, 55, 80, 0.18);
            content: "";
            height: 24px;
            left: 4px;
            position: absolute;
            top: 4px;
            transition: transform 180ms ease;
            width: 24px;
        }
        .discord-toggle-card input:checked + .discord-toggle-switch {
            background: var(--accent-strong);
            box-shadow: inset 0 0 0 1px rgba(15, 55, 80, 0.12);
        }
        .discord-toggle-card input:checked + .discord-toggle-switch::after {
            transform: translateX(26px);
        }
        .discord-dependent-settings {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .discord-events-panel > .muted {
            margin: 6px 0 12px;
        }
        .finance-hero {
            align-items: stretch;
            display: flex;
            gap: 18px;
            justify-content: space-between;
        }
        .month-actions {
            align-items: center;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .month-actions .inline-button {
            margin-top: 0;
            width: auto;
        }
        .month-nav-button {
            align-items: center;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(15, 55, 80, 0.14);
            border-radius: 999px;
            color: var(--brand-dark);
            display: inline-flex;
            font-size: 0.9rem;
            font-weight: 900;
            gap: 6px;
            padding: 8px 11px;
            text-decoration: none;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
            white-space: nowrap;
        }
        .month-nav-button:hover {
            border-color: var(--accent-strong);
            color: var(--accent-strong);
            transform: translateY(-1px);
        }
        .month-nav-button.is-primary {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            color: #ffffff;
        }
        .month-nav-button.is-primary:hover {
            background: var(--brand-panel);
            border-color: var(--brand-panel);
            color: #ffffff;
        }
        .finance-summary-grid {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-bottom: 20px;
        }
        .finance-summary-card {
            display: grid;
            gap: 8px;
        }
        .finance-summary-card strong {
            color: var(--brand-dark);
            font-size: clamp(1.35rem, 3vw, 2rem);
        }
        .finance-summary-card small {
            color: var(--muted);
            font-weight: 700;
        }
        .finance-filter-form {
            align-items: end;
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .finance-filter-form label {
            margin-bottom: 0;
        }
        .finance-filter-form .form-actions button {
            margin-top: 0;
        }
        .category-multi-filter {
            border: 1px solid var(--border);
            border-radius: 16px;
            grid-column: 1 / -1;
            margin: 2px 0 0;
            padding: 0;
        }
        .category-multi-filter summary {
            align-items: center;
            cursor: pointer;
            display: flex;
            gap: 10px;
            justify-content: space-between;
            list-style: none;
            padding: 14px 16px;
        }
        .category-multi-filter summary::-webkit-details-marker {
            display: none;
        }
        .category-multi-filter summary span {
            color: var(--brand-dark);
            font-weight: 900;
        }
        .category-multi-filter summary small {
            background: #eef7fb;
            border-radius: 999px;
            color: var(--muted);
            font-weight: 900;
            padding: 5px 9px;
        }
        .category-multi-filter summary::after {
            color: var(--accent-strong);
            content: "\F282";
            font-family: "bootstrap-icons";
            font-size: 0.9rem;
            margin-left: auto;
            transition: transform 160ms ease;
        }
        .category-multi-filter[open] summary::after {
            transform: rotate(180deg);
        }
        .category-multi-filter .muted {
            margin: 0 16px 12px;
        }
        .category-chip-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            max-height: 210px;
            overflow-y: auto;
            padding: 0 16px 16px;
            padding-right: 20px;
        }
        .category-filter-chip {
            margin: 0;
        }
        .category-filter-chip input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }
        .category-filter-chip span {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: var(--brand-dark);
            cursor: pointer;
            display: inline-flex;
            font-size: 0.88rem;
            font-weight: 900;
            padding: 9px 12px;
            transition: background 160ms ease, border-color 160ms ease, color 160ms ease, transform 160ms ease;
        }
        .category-filter-chip input:checked + span {
            background: var(--brand-dark);
            border-color: var(--brand-dark);
            color: #ffffff;
        }
        .category-filter-chip span:hover {
            border-color: var(--accent-strong);
            transform: translateY(-1px);
        }
        .finance-entries-table tbody tr:nth-child(odd) {
            background: #f8fbfd;
        }
        .finance-entries-table tbody tr:nth-child(even) {
            background: #ffffff;
        }
        .finance-entries-table td {
            vertical-align: middle;
        }
        .finance-entries-table td:first-child {
            min-width: 260px;
        }
        .money-cell {
            color: var(--brand-dark);
            font-weight: 900;
            min-width: 104px;
            text-align: right;
            white-space: nowrap;
        }
        .money-cell.is-positive {
            color: #047857;
        }
        .money-cell.is-negative {
            color: #b91c1c;
        }
        .finance-entries-table tbody tr.cashflow-row-income {
            background: #f0fdf4;
        }
        .finance-entries-table tbody tr.cashflow-row-expense {
            background: #fff5f5;
        }
        .finance-entries-table tbody tr.cashflow-row-income:nth-child(even) {
            background: #eafaf1;
        }
        .finance-entries-table tbody tr.cashflow-row-expense:nth-child(even) {
            background: #fff0f0;
        }
        .cashflow-type-pill {
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.8rem;
            font-weight: 900;
            padding: 5px 9px;
            white-space: nowrap;
        }
        .cashflow-type-pill.is-income {
            background: #d1fae5;
            color: #047857;
        }
        .cashflow-type-pill.is-expense {
            background: #fee2e2;
            color: #b91c1c;
        }
        .status-pill {
            border-radius: 999px;
            display: inline-flex;
            font-size: 0.82rem;
            font-weight: 900;
            padding: 5px 9px;
            white-space: nowrap;
        }
        .status-paid {
            background: #e6f7ef;
            color: #047857;
        }
        .status-open {
            background: #fff4dd;
            color: #b45309;
        }
        .last-installment-pill {
            background: var(--accent-soft);
            border-radius: 999px;
            color: var(--accent-strong);
            display: inline-flex;
            font-size: 0.78rem;
            font-weight: 900;
            margin-left: 6px;
            padding: 4px 7px;
            white-space: nowrap;
        }
        .cash-payment-pill {
            background: #eef7fb;
            border-radius: 999px;
            color: var(--muted);
            display: inline-flex;
            font-size: 0.78rem;
            font-weight: 900;
            padding: 4px 8px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .category-column-chart {
            align-items: end;
            display: flex;
            gap: 14px;
            min-height: 300px;
            overflow-x: auto;
            padding: 12px 4px 4px;
        }
        .category-column-item {
            align-items: center;
            display: grid;
            flex: 0 0 94px;
            gap: 8px;
            grid-template-rows: auto 190px auto auto;
            text-align: center;
        }
        .category-column-item strong {
            color: var(--brand-dark);
            font-size: 0.86rem;
            white-space: nowrap;
        }
        .category-column-item > span {
            color: var(--brand-dark);
            font-size: 0.78rem;
            font-weight: 900;
            line-height: 1.2;
            min-height: 34px;
            overflow-wrap: anywhere;
        }
        .category-column-item small {
            color: var(--muted);
            font-size: 0.74rem;
            font-weight: 800;
        }
        .category-column-track {
            align-items: end;
            background: #e8f0f5;
            border-radius: 18px;
            display: flex;
            height: 190px;
            overflow: hidden;
            width: 100%;
        }
        .category-column-track span {
            background: linear-gradient(180deg, var(--accent-blue), var(--accent));
            border-radius: inherit;
            display: block;
            width: 100%;
        }
        .income-column-chart .category-column-track span {
            background: linear-gradient(180deg, #34d399, #0f9f86);
        }
        pre {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--brand-dark);
            overflow-x: auto;
            padding: 14px;
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
            align-items: center;
            cursor: pointer;
            display: flex;
            gap: 8px;
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
        .finish-form {
            align-items: end;
        }
        .finish-form button {
            min-width: 220px;
        }
        .market-lock-notice {
            margin: 14px 0;
        }
        .market-total-summary {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-top: 18px;
        }
        .market-total-summary div {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
        }
        .market-total-summary span,
        .market-total-summary strong,
        .market-total-summary small {
            display: block;
        }
        .market-total-summary span {
            color: var(--muted);
            font-size: 0.88rem;
        }
        .market-total-summary strong {
            font-size: 1.2rem;
            margin-top: 4px;
        }
        .market-total-summary small {
            color: var(--muted);
            margin-top: 6px;
        }
        .report-filter {
            align-items: end;
        }
        .report-summary-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin: 18px 0;
        }
        .report-summary-card {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }
        .report-summary-card small,
        .report-summary-card strong {
            display: block;
        }
        .report-summary-card small {
            color: var(--muted);
        }
        .report-summary-card strong {
            color: var(--brand-dark);
            font-size: 1.35rem;
            margin-top: 6px;
        }
        .report-bar-chart {
            align-items: end;
            display: flex;
            gap: 14px;
            min-height: 240px;
            overflow-x: auto;
            padding: 22px 4px 8px;
        }
        .report-bar-item {
            align-items: center;
            display: grid;
            gap: 8px;
            min-width: 76px;
            text-align: center;
        }
        .report-bar-track {
            align-items: end;
            background: #edf4f8;
            border-radius: 999px;
            display: flex;
            height: 190px;
            justify-content: center;
            justify-self: center;
            overflow: hidden;
            width: 44px;
        }
        .report-bar-fill {
            background: linear-gradient(180deg, var(--accent), var(--accent-blue));
            border-radius: 999px 999px 0 0;
            display: block;
            width: 100%;
        }
        .report-bar-item strong {
            font-size: 0.82rem;
        }
        .report-bar-item small {
            color: var(--muted);
        }
        .report-mini-bar {
            background: #eaf3f7;
            border-radius: 999px;
            display: block;
            height: 8px;
            margin-top: 8px;
            max-width: 220px;
            overflow: hidden;
        }
        .report-mini-bar span {
            background: linear-gradient(90deg, var(--accent), var(--brand));
            border-radius: inherit;
            display: block;
            height: 100%;
        }
        .paired-bar-chart {
            align-items: end;
            display: flex;
            gap: 18px;
            margin: 20px 0 24px;
            min-height: 230px;
            overflow-x: auto;
            padding: 20px 4px 8px;
        }
        .paired-bar-item {
            display: grid;
            gap: 7px;
            min-width: 108px;
            text-align: center;
        }
        .paired-bar-columns {
            align-items: end;
            display: flex;
            gap: 8px;
            height: 160px;
            justify-content: center;
        }
        .paired-bar-columns span {
            border-radius: 999px 999px 0 0;
            display: block;
            width: 28px;
        }
        .paired-bar-income {
            background: linear-gradient(180deg, #2dd4bf, var(--accent));
        }
        .paired-bar-expense {
            background: linear-gradient(180deg, #f59e0b, #ef4444);
        }
        .paired-bar-item strong {
            color: var(--brand-dark);
            font-size: 0.86rem;
        }
        .paired-bar-item small {
            color: var(--muted);
            display: block;
            font-size: 0.78rem;
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
        .metric-mini-list {
            max-height: 92px;
            margin-top: 14px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .mini-list-item {
            background: #f8fbfd;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px;
        }
        .mini-list-item-compact {
            padding: 7px 10px;
        }
        .mini-list-item-compact strong {
            font-size: 0.82rem;
            line-height: 1.15;
        }
        .dashboard-indicator {
            display: block;
            font-size: 1.55rem;
            margin: 10px 0;
        }
        .pie-placeholder {
            align-items: center;
            aspect-ratio: 1;
            background:
                conic-gradient(var(--accent-strong) 0 38%, var(--accent-blue) 38% 68%, #f59e0b 68% 86%, #e5edf2 86% 100%);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            margin: 14px auto;
            max-width: 180px;
            position: relative;
        }
        .pie-placeholder::after {
            background: var(--surface);
            border-radius: 50%;
            content: "";
            height: 54%;
            position: absolute;
            width: 54%;
        }
        .pie-placeholder .bi {
            color: var(--brand-dark);
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
        }
        .annual-dr-chart {
            align-items: center;
            display: grid;
            gap: 22px;
            grid-template-columns: minmax(180px, 240px) minmax(0, 1fr);
            margin-top: 18px;
        }
        .annual-dr-pie {
            align-items: center;
            aspect-ratio: 1;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            position: relative;
            width: min(100%, 240px);
        }
        .annual-dr-pie::after {
            background: var(--surface);
            border-radius: 50%;
            content: "";
            height: 58%;
            position: absolute;
            width: 58%;
        }
        .annual-dr-pie span {
            color: var(--brand-dark);
            font-size: 0.95rem;
            font-weight: 900;
            position: relative;
            text-align: center;
            z-index: 1;
        }
        .annual-dr-legend {
            display: grid;
            gap: 10px;
            max-height: 260px;
            overflow-y: auto;
            padding-right: 4px;
        }
        .annual-dr-legend-item {
            align-items: center;
            display: grid;
            gap: 4px 9px;
            grid-template-columns: 12px minmax(0, 1fr);
        }
        .annual-dr-legend-item > span {
            border-radius: 50%;
            height: 12px;
            width: 12px;
        }
        .annual-dr-legend-item strong {
            color: var(--brand-dark);
            font-size: 0.88rem;
        }
        .annual-dr-legend-item small {
            color: var(--muted);
            font-weight: 800;
            grid-column: 2;
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
                bottom: 0;
                box-shadow: 22px 0 48px rgba(4, 26, 56, 0.28);
                display: block;
                height: 100vh;
                left: 0;
                max-width: 340px;
                padding-top: 18px;
                position: fixed;
                top: 0;
                transform: translateX(-105%);
                transition: transform 220ms ease;
                width: min(86vw, 340px);
                z-index: 30;
            }
            .app-sidebar.is-open {
                transform: translateX(0);
            }
            .sidebar-mobile-header {
                align-items: center;
                display: flex;
                gap: 12px;
                justify-content: space-between;
                margin-bottom: 14px;
            }
            .mobile-topbar {
                display: flex;
            }
            .dashboard-hero {
                align-items: stretch;
                flex-direction: column;
            }
            .page-hero {
                align-items: stretch;
                flex-direction: column;
                padding: 22px;
            }
            .page-hero-content {
                flex-direction: column;
            }
            .page-hero-actions {
                justify-content: flex-start;
            }
            .bar-chart {
                gap: 6px;
                overflow-x: auto;
            }
            .annual-dr-chart {
                grid-template-columns: 1fr;
                justify-items: center;
            }
            .annual-dr-legend {
                width: 100%;
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
                grid-template-columns: 44px minmax(0, 1fr);
            }
            .market-item-copy {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
            }
            .market-item-meta {
                min-width: 0;
            }
            .shopping-item-card {
                align-items: stretch;
                flex-direction: column;
            }
            .shopping-item-actions {
                justify-content: stretch;
            }
            .shopping-item-actions .inline-button,
            .shopping-item-actions form,
            .shopping-item-actions button {
                width: 100%;
            }
            .finance-summary-grid,
            .finance-filter-form {
                grid-template-columns: 1fr;
            }
            .month-actions .inline-button,
            .month-actions .month-nav-button {
                justify-content: center;
                width: 100%;
            }
        }
    </style>
</head>
<body class="<?= htmlspecialchars($bodyClass ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($user)): ?>
        <?php
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $isActive = static fn (string $path): string => $currentPath === $path ? 'is-active' : '';
        $isActivePrefix = static fn (string $path): string => str_starts_with($currentPath, $path) ? 'is-active' : '';
        ?>
        <div class="app-shell">
            <div class="mobile-menu-overlay" data-menu-overlay></div>
            <aside class="app-sidebar" id="appSidebar">
                <div class="sidebar-mobile-header">
                    <strong>Menu</strong>
                    <button class="mobile-menu-close" type="button" data-menu-close aria-label="Fechar menu">
                        <span class="bi bi-x-lg"></span>
                    </button>
                </div>
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
                            <div class="nav-group-title">Compras</div>
                            <a class="nav-subitem <?= $isActive('/shopping') ?>" href="/shopping"><span class="bi bi-grid"></span>Visao geral</a>
                            <a class="nav-subitem <?= $isActivePrefix('/shopping/market') ?>" href="/shopping/market"><span class="bi bi-basket2"></span>Mercado</a>
                            <a class="nav-subitem <?= $isActive('/shopping/home') ?>" href="/shopping/home"><span class="bi bi-house-heart"></span>Para casa</a>
                            <a class="nav-subitem <?= $isActive('/shopping/family') ?>" href="/shopping/family"><span class="bi bi-people"></span>Para a família</a>
                            <a class="nav-subitem <?= $isActive('/shopping/vehicle') ?>" href="/shopping/vehicle"><span class="bi bi-car-front"></span>Para o veículo</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_CONTACTS)): ?>
                        <div class="nav-group">
                            <a class="<?= $isActive('/contacts') ?>" href="/contacts"><span class="bi bi-person-lines-fill"></span>Contatos</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_CATEGORY_REPORT)): ?>
                        <div class="nav-group">
                            <div class="nav-group-title">Relatórios</div>
                            <a class="nav-subitem <?= $isActive('/reports') ?>" href="/reports"><span class="bi bi-bar-chart-line"></span>Financeiro</a>
                            <a class="nav-subitem <?= $isActive('/reports/market') ?>" href="/reports/market"><span class="bi bi-basket2"></span>Mercado</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($user->hasRole(\App\Domain\Auth\Role::ADMIN)): ?>
                        <div class="nav-group">
                            <div class="nav-group-title">Sistema</div>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_USERS)): ?>
                                <a class="nav-subitem <?= $isActive('/admin/users') ?>" href="/admin/users"><span class="bi bi-people"></span>Usuários</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::VIEW_AUDIT_LOGS)): ?>
                                <a class="nav-subitem <?= $isActive('/admin/audit-logs') ?>" href="/admin/audit-logs"><span class="bi bi-journal-text"></span>Logs</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_BACKUPS)): ?>
                                <a class="nav-subitem <?= $isActive('/system/backup') ?>" href="/system/backup"><span class="bi bi-cloud-arrow-down"></span>Backup</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_SPREADSHEET_URL)): ?>
                                <a class="nav-subitem <?= $isActive('/system/sync') ?>" href="/system/sync"><span class="bi bi-clock-history"></span>Tempo de sincronização</a>
                                <a class="nav-subitem <?= $isActive('/system/categories') ?>" href="/system/categories"><span class="bi bi-tags"></span>Categoria (DR)</a>
                                <a class="nav-subitem <?= $isActive('/system/spreadsheet') ?>" href="/system/spreadsheet"><span class="bi bi-file-earmark-spreadsheet"></span>Gerenciar planilha</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_DISCORD_NOTIFICATIONS)): ?>
                                <a class="nav-subitem <?= $isActive('/system/discord') ?>" href="/system/discord"><span class="bi bi-discord"></span>Discord</a>
                            <?php endif; ?>
                            <?php if ($user->can(\App\Domain\Auth\Permission::MANAGE_SHOPPING_SETTINGS)): ?>
                                <a class="nav-subitem <?= $isActive('/admin/shopping-settings') ?>" href="/admin/shopping-settings"><span class="bi bi-sliders"></span>Configuração compras</a>
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
                    <button class="mobile-menu-toggle" type="button" data-menu-toggle aria-controls="appSidebar" aria-expanded="false">
                        <span class="bi bi-list"></span>Menu
                    </button>
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
        const sidebar = document.querySelector('#appSidebar');
        const menuToggle = document.querySelector('[data-menu-toggle]');
        const menuOverlay = document.querySelector('[data-menu-overlay]');
        const closeMenu = () => {
            sidebar?.classList.remove('is-open');
            menuOverlay?.classList.remove('is-open');
            menuToggle?.setAttribute('aria-expanded', 'false');
        };
        const openMenu = () => {
            sidebar?.classList.add('is-open');
            menuOverlay?.classList.add('is-open');
            menuToggle?.setAttribute('aria-expanded', 'true');
        };

        menuToggle?.addEventListener('click', () => {
            sidebar?.classList.contains('is-open') ? closeMenu() : openMenu();
        });
        document.querySelector('[data-menu-close]')?.addEventListener('click', closeMenu);
        menuOverlay?.addEventListener('click', closeMenu);
        sidebar?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));
        document.querySelectorAll('[data-money-input]').forEach((input) => {
            const formatCurrency = (rawValue) => {
                const digits = String(rawValue).replace(/\D+/g, '');

                if (digits === '') {
                    return '';
                }

                const value = Number(digits) / 100;

                return value.toLocaleString('pt-BR', {
                    style: 'currency',
                    currency: 'BRL',
                });
            };

            const applyCurrency = () => {
                input.value = formatCurrency(input.value);
                input.dispatchEvent(new CustomEvent('money:formatted', { bubbles: true }));
            };

            input.addEventListener('input', applyCurrency);
            input.addEventListener('blur', applyCurrency);

            if (input.value.trim() !== '') {
                applyCurrency();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu();
            }
        });
    </script>
</body>
</html>
