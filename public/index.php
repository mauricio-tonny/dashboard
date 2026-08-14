<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\Admin\AuditLogController;
use App\Controllers\Admin\ShoppingSettingsController;
use App\Controllers\Admin\UserController;
use App\Controllers\ContactController;
use App\Controllers\DashboardController;
use App\Controllers\EntryController;
use App\Controllers\FinancePayableController;
use App\Controllers\FinanceReceivableController;
use App\Controllers\NavigationPageController;
use App\Controllers\ReportController;
use App\Controllers\ShoppingController;
use App\Controllers\System\DiscordController;
use App\Controllers\System\PermissionController;
use App\Controllers\System\SpreadsheetController;
use App\Core\App;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\UserRepository;
use App\Domain\Auth\UserPermissionRepository;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AdminUserRepository;
use App\Domain\Contacts\ContactRepository;
use App\Domain\Finance\FinanceService;
use App\Domain\Finance\FinanceEntryRepository;
use App\Domain\Shopping\ShoppingRepository;
use App\Domain\System\DiscordNotificationRepository;
use App\Domain\System\DiscordNotifier;
use App\Domain\System\SpreadsheetLinkTester;
use App\Domain\System\SpreadsheetSettingsRepository;
use App\Infrastructure\Auth\DatabaseUserRepository;
use App\Infrastructure\Finance\ExcelFinanceRepository;
use App\Support\Env;

require_once dirname(__DIR__) . '/src/Support/helpers.php';

$vendorAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

Env::load(dirname(__DIR__) . '/.env');

$session = new Session($_ENV['SESSION_NAME'] ?? 'dashboard_financeiro');
$request = Request::capture();
$database = new Database();

$userRepository = new DatabaseUserRepository($database);
$adminUserRepository = new AdminUserRepository($database);
$authService = new AuthService($userRepository, $session);
$auditLogger = new AuditLogger($database);
$shoppingRepository = new ShoppingRepository($database);
$discordNotificationRepository = new DiscordNotificationRepository($database);
$discordNotifier = new DiscordNotifier($discordNotificationRepository);
$spreadsheetSettingsRepository = new SpreadsheetSettingsRepository($database);
$spreadsheetLinkTester = new SpreadsheetLinkTester();
$contactRepository = new ContactRepository($database);
$financeEntryRepository = new FinanceEntryRepository($database);
$userPermissionRepository = new UserPermissionRepository($database);
$financeRepository = new ExcelFinanceRepository($_ENV['EXCEL_FILE'] ?? dirname(__DIR__) . '/storage/financeiro.xlsx');
$financeService = new FinanceService($financeRepository);

$authService->enforceIdleTimeout((int) ($_ENV['SESSION_IDLE_TIMEOUT_MINUTES'] ?? 15) * 60, $auditLogger);

$app = new App([
    Database::class => $database,
    Session::class => $session,
    Request::class => $request,
    UserRepository::class => $userRepository,
    AdminUserRepository::class => $adminUserRepository,
    AuthService::class => $authService,
    AuditLogger::class => $auditLogger,
    ShoppingRepository::class => $shoppingRepository,
    DiscordNotificationRepository::class => $discordNotificationRepository,
    DiscordNotifier::class => $discordNotifier,
    SpreadsheetSettingsRepository::class => $spreadsheetSettingsRepository,
    SpreadsheetLinkTester::class => $spreadsheetLinkTester,
    ContactRepository::class => $contactRepository,
    FinanceEntryRepository::class => $financeEntryRepository,
    UserPermissionRepository::class => $userPermissionRepository,
    FinanceService::class => $financeService,
]);

$router = new Router($app);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/admin/audit-logs', [AuditLogController::class, 'index']);
$router->get('/admin/users', [UserController::class, 'index']);
$router->post('/admin/users', [UserController::class, 'store']);
$router->post('/admin/users/update', [UserController::class, 'update']);
$router->post('/admin/users/toggle-status', [UserController::class, 'toggleStatus']);
$router->get('/admin/shopping-settings', [ShoppingSettingsController::class, 'index']);
$router->post('/admin/shopping-settings/simple', [ShoppingSettingsController::class, 'saveSimple']);
$router->post('/admin/shopping-settings/simple/toggle', [ShoppingSettingsController::class, 'toggleSimple']);
$router->post('/admin/shopping-settings/vehicles', [ShoppingSettingsController::class, 'saveVehicle']);
$router->post('/admin/shopping-settings/vehicles/toggle', [ShoppingSettingsController::class, 'toggleVehicle']);

$router->get('/shopping', [ShoppingController::class, 'index']);
$router->get('/shopping/market', [ShoppingController::class, 'market']);
$router->get('/shopping/market/history', [ShoppingController::class, 'marketHistory']);
$router->get('/shopping/home', [ShoppingController::class, 'home']);
$router->get('/shopping/family', [ShoppingController::class, 'family']);
$router->get('/shopping/vehicle', [ShoppingController::class, 'vehicle']);
$router->post('/shopping/market/lists', [ShoppingController::class, 'createMarketList']);
$router->post('/shopping/market/items', [ShoppingController::class, 'addMarketItem']);
$router->post('/shopping/market/items/update', [ShoppingController::class, 'updateMarketItem']);
$router->post('/shopping/market/items/toggle', [ShoppingController::class, 'toggleMarketItem']);
$router->post('/shopping/market/items/delete', [ShoppingController::class, 'deleteMarketItem']);
$router->post('/shopping/market/lists/finish', [ShoppingController::class, 'finishMarketList']);
$router->post('/shopping/market/lists/reopen', [ShoppingController::class, 'reopenMarketList']);
$router->post('/shopping/market/lists/delete', [ShoppingController::class, 'deleteMarketList']);
$router->post('/shopping/market/invoices', [ShoppingController::class, 'uploadMarketInvoice']);
$router->get('/shopping/market/invoices/download', [ShoppingController::class, 'downloadMarketInvoice']);
$router->post('/shopping/market/access-key', [ShoppingController::class, 'storeMarketAccessKey']);
$router->post('/shopping/wish-items', [ShoppingController::class, 'addWishItem']);
$router->post('/shopping/wish-items/update', [ShoppingController::class, 'updateWishItem']);
$router->post('/shopping/wish-items/toggle', [ShoppingController::class, 'toggleWishItem']);
$router->post('/shopping/wish-items/delete', [ShoppingController::class, 'deleteWishItem']);

$router->get('/contacts', [ContactController::class, 'index']);
$router->post('/contacts', [ContactController::class, 'store']);
$router->post('/contacts/update', [ContactController::class, 'update']);
$router->post('/contacts/toggle', [ContactController::class, 'toggle']);

$router->get('/finance/payable', [FinancePayableController::class, 'index']);
$router->get('/finance/receivable', [FinanceReceivableController::class, 'index']);
$router->get('/reports', [ReportController::class, 'index']);
$router->get('/reports/categories', [ReportController::class, 'categories']);
$router->get('/reports/category-chart', [ReportController::class, 'categoryChart']);
$router->get('/reports/vendors', [ReportController::class, 'vendors']);
$router->get('/reports/paid-vs-received', [ReportController::class, 'paidVsReceived']);
$router->get('/reports/cashflow', [ReportController::class, 'cashflow']);
$router->get('/reports/market', [ReportController::class, 'market']);
$router->get('/system/backup', [NavigationPageController::class, 'systemBackup']);
$router->get('/system/sync', [NavigationPageController::class, 'systemSync']);
$router->get('/system/categories', [NavigationPageController::class, 'systemCategories']);
$router->get('/system/discord', [DiscordController::class, 'index']);
$router->post('/system/discord', [DiscordController::class, 'save']);
$router->post('/system/discord/test', [DiscordController::class, 'test']);
$router->get('/system/permissions', [PermissionController::class, 'index']);
$router->post('/system/permissions', [PermissionController::class, 'save']);
$router->get('/system/spreadsheet', [SpreadsheetController::class, 'index']);
$router->post('/system/spreadsheet', [SpreadsheetController::class, 'save']);
$router->post('/system/spreadsheet/test', [SpreadsheetController::class, 'test']);
$router->post('/system/spreadsheet/remove', [SpreadsheetController::class, 'remove']);

$router->get('/entries/create', [EntryController::class, 'create']);
$router->post('/entries', [EntryController::class, 'store']);

$response = $router->dispatch($request);
auditPageUsage($request, $response, $authService, $auditLogger);
$response->send();

function auditPageUsage(Request $request, \App\Core\Response $response, AuthService $authService, AuditLogger $auditLogger): void
{
    if ($request->method() !== 'GET') {
        return;
    }

    $user = $authService->user();

    if ($user === null) {
        return;
    }

    $path = $request->path();

    if ($path === '/login') {
        return;
    }

    $pageLabels = [
        '/' => 'Dashboard',
        '/admin/audit-logs' => 'Logs de auditoria',
        '/admin/users' => 'Usuarios',
        '/admin/shopping-settings' => 'Configuracao de compras',
        '/shopping' => 'Compras - visao geral',
        '/shopping/market' => 'Compras - mercado',
        '/shopping/market/history' => 'Compras - mercado historico',
        '/shopping/home' => 'Compras - para casa',
        '/shopping/family' => 'Compras - para a familia',
        '/shopping/vehicle' => 'Compras - para veiculo',
        '/contacts' => 'Contatos',
        '/finance/payable' => 'Financeiro - A pagar',
        '/finance/receivable' => 'Financeiro - A receber',
        '/reports' => 'Relatorios - central',
        '/reports/categories' => 'Relatorio por categoria',
        '/reports/category-chart' => 'Relatorio grafico por categoria',
        '/reports/vendors' => 'Relatorio por fornecedor',
        '/reports/paid-vs-received' => 'Relatorio Pago x Recebido',
        '/reports/cashflow' => 'Relatorio Fluxo de Caixa',
        '/reports/market' => 'Relatorio de Mercado',
        '/system/backup' => 'Sistema - backup',
        '/system/sync' => 'Sistema - sincronizacao',
        '/system/categories' => 'Sistema - categorias',
        '/system/discord' => 'Sistema - Discord',
        '/system/permissions' => 'Sistema - permissoes',
        '/system/spreadsheet' => 'Sistema - planilha',
        '/entries/create' => 'Novo lancamento',
    ];

    $label = $pageLabels[$path] ?? $path;
    $metadata = [
        'path' => $path,
        'label' => $label,
        'status_code' => $response->statusCode(),
        'query' => sanitizedAuditQuery($request->query()),
        'ip' => (string) $request->server('REMOTE_ADDR', ''),
        'user_agent' => mb_substr((string) $request->server('HTTP_USER_AGENT', ''), 0, 255),
    ];

    if (str_starts_with($path, '/reports/') && $path !== '/reports/market') {
        $auditLogger->log('report_viewed', 'report', null, $user, [
            ...$metadata,
            'report' => str_replace('/reports/', '', $path),
            'filters' => reportAuditFilters($request->query()),
        ]);

        return;
    }

    if ($path === '/reports/market') {
        $auditLogger->log('report_viewed', 'report', null, $user, [
            ...$metadata,
            'report' => 'market',
            'filters' => reportAuditFilters($request->query()),
        ]);

        return;
    }

    $auditLogger->log('page_viewed', 'page', null, $user, $metadata);
}

function sanitizedAuditQuery(array $query): array
{
    $blockedKeys = ['password', 'admin_password', 'token', 'webhook_url', 'access_key'];
    $sanitized = [];

    foreach ($query as $key => $value) {
        if (in_array((string) $key, $blockedKeys, true)) {
            $sanitized[$key] = '[redacted]';
            continue;
        }

        $sanitized[$key] = is_array($value)
            ? array_map(static fn (mixed $item): string => mb_substr((string) $item, 0, 120), $value)
            : mb_substr((string) $value, 0, 120);
    }

    return $sanitized;
}

function reportAuditFilters(array $query): array
{
    return array_intersect_key(sanitizedAuditQuery($query), array_flip([
        'start_month',
        'end_month',
        'start_date',
        'end_date',
        'category_ids',
        'category_id',
        'vendor_ids',
        'vendor_id',
    ]));
}
