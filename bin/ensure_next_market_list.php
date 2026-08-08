<?php

declare(strict_types=1);

use App\Core\Database;
use App\Domain\Shopping\ShoppingRepository;
use App\Domain\System\DiscordNotificationRepository;
use App\Domain\System\DiscordNotifier;
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

$database = new Database();
$shoppingRepository = new ShoppingRepository($database);
$discordNotifier = new DiscordNotifier(new DiscordNotificationRepository($database));

$result = $shoppingRepository->findOrCreateMarketListWithStatus($shoppingRepository->nextMonth(), null);

if ($result['created']) {
    $monthLabel = (new DateTimeImmutable($result['reference_month']))->format('m/Y');
    $discordNotifier->marketListCreated($monthLabel, true);
    echo "Lista de mercado {$monthLabel} criada.\n";
    exit(0);
}

echo "Lista do proximo mes ja existia.\n";
