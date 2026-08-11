<?php

declare(strict_types=1);

use App\Core\Database;
use App\Domain\Shopping\ShoppingRepository;
use App\Domain\System\DiscordNotificationRepository;
use App\Domain\System\DiscordNotifier;
use App\Domain\System\Tasks\EnsureNextMarketListTask;
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
$task = new EnsureNextMarketListTask($shoppingRepository, $discordNotifier);
$result = $task->run();

echo $result->message . "\n";
exit($result->success ? 0 : 1);
