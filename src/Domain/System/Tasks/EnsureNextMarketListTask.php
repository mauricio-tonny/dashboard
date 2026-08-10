<?php

declare(strict_types=1);

namespace App\Domain\System\Tasks;

use App\Domain\Shopping\ShoppingRepository;
use App\Domain\System\DiscordNotifier;
use App\Domain\System\ScheduledTask;
use App\Domain\System\ScheduledTaskResult;
use DateTimeImmutable;

final class EnsureNextMarketListTask implements ScheduledTask
{
    public function __construct(
        private ShoppingRepository $shoppingRepository,
        private DiscordNotifier $discordNotifier
    ) {
    }

    public function code(): string
    {
        return 'market.ensure_next_list';
    }

    public function name(): string
    {
        return 'Garantir lista de mercado do próximo mês';
    }

    public function intervalMinutes(): int
    {
        return 1440;
    }

    public function run(): ScheduledTaskResult
    {
        $result = $this->shoppingRepository->findOrCreateMarketListWithStatus($this->shoppingRepository->nextMonth(), null);
        $monthLabel = (new DateTimeImmutable($result['reference_month']))->format('m/Y');

        if ($result['created']) {
            $this->discordNotifier->marketListCreated($monthLabel, true);

            return ScheduledTaskResult::success("Lista de mercado {$monthLabel} criada.", [
                'reference_month' => $result['reference_month'],
                'created' => true,
            ]);
        }

        return ScheduledTaskResult::success("Lista de mercado {$monthLabel} já existia.", [
            'reference_month' => $result['reference_month'],
            'created' => false,
        ]);
    }
}
