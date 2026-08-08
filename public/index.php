<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\Admin\AuditLogController;
use App\Controllers\DashboardController;
use App\Controllers\EntryController;
use App\Core\App;
use App\Core\Database;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\UserRepository;
use App\Domain\Audit\AuditLogger;
use App\Domain\Finance\FinanceService;
use App\Infrastructure\Auth\DatabaseUserRepository;
use App\Infrastructure\Finance\ExcelFinanceRepository;
use App\Support\Env;

require_once dirname(__DIR__) . '/src/Support/helpers.php';

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
$authService = new AuthService($userRepository, $session);
$auditLogger = new AuditLogger($database);
$financeRepository = new ExcelFinanceRepository($_ENV['EXCEL_FILE'] ?? dirname(__DIR__) . '/storage/financeiro.xlsx');
$financeService = new FinanceService($financeRepository);

$authService->enforceIdleTimeout((int) ($_ENV['SESSION_IDLE_TIMEOUT_MINUTES'] ?? 15) * 60, $auditLogger);

$app = new App([
    Database::class => $database,
    Session::class => $session,
    Request::class => $request,
    UserRepository::class => $userRepository,
    AuthService::class => $authService,
    AuditLogger::class => $auditLogger,
    FinanceService::class => $financeService,
]);

$router = new Router($app);

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/admin/audit-logs', [AuditLogController::class, 'index']);

$router->get('/entries/create', [EntryController::class, 'create']);
$router->post('/entries', [EntryController::class, 'store']);

$response = $router->dispatch($request);
$response->send();
