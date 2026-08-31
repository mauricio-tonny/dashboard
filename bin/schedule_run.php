<?php

declare(strict_types=1);

use App\Core\Database;
use App\Domain\Shopping\ShoppingRepository;
use App\Domain\System\DiscordNotificationRepository;
use App\Domain\System\DiscordNotifier;
use App\Domain\System\Scheduler;
use App\Domain\System\SchedulerRepository;
use App\Domain\System\SpreadsheetSettingsRepository;
use App\Domain\System\Tasks\EnsureNextMarketListTask;
use App\Domain\System\Tasks\ImportSpreadsheetTask;
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

$database = new Database();
$shoppingRepository = new ShoppingRepository($database);
$discordNotifier = new DiscordNotifier(new DiscordNotificationRepository($database));
$spreadsheetSettingsRepository = new SpreadsheetSettingsRepository($database);
$syncSettings = $spreadsheetSettingsRepository->syncSettings();
$tasks = [
    'market.ensure_next_list' => new EnsureNextMarketListTask($shoppingRepository, $discordNotifier),
];

if ((int) ($syncSettings['sync_is_enabled'] ?? 0) === 1) {
    $intervalMinutes = max(5, (int) ($syncSettings['sync_interval_minutes'] ?? 30));
    $tasks['spreadsheet.import'] = new ImportSpreadsheetTask(dirname(__DIR__), $intervalMinutes, $discordNotifier, $syncSettings);
}

$scheduler = new Scheduler(new SchedulerRepository($database), $tasks);
$results = $scheduler->runDue(new DateTimeImmutable());

if ($results === []) {
    echo "Nenhuma tarefa pendente.\n";
    exit(0);
}

foreach ($results as $result) {
    echo "[{$result['status']}] {$result['code']}: {$result['message']}\n";
}
