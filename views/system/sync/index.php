<?php
$title = 'Tempo de sincronização';
$enabled = ((int) ($syncSettings['sync_is_enabled'] ?? 0)) === 1;
$interval = (int) ($syncSettings['sync_interval_minutes'] ?? 30);
$startHour = (int) ($syncSettings['sync_start_hour'] ?? 8);
$endHour = (int) ($syncSettings['sync_end_hour'] ?? 18);
$formatDateTime = static function (?string $value): string {
    if ($value === null || trim($value) === '') {
        return '-';
    }

    return (new DateTimeImmutable($value))->format('d/m/Y H:i:s');
};
$formatStatus = static fn (?string $status): string => match ($status) {
    'success' => 'Sucesso',
    'failed' => 'Falha',
    'skipped' => 'Ignorada',
    'running' => 'Em execução',
    default => $status === null || $status === '' ? '-' : $status,
};
$statusClass = static fn (?string $status): string => match ($status) {
    'success' => 'notice-success',
    'failed' => 'notice-error',
    'skipped' => 'notice-warning',
    default => '',
};
$lastRun = $runs[0] ?? null;
$intervalOptions = [
    15 => 'A cada 15 minutos',
    30 => 'A cada 30 minutos',
    60 => 'A cada 1 hora',
    120 => 'A cada 2 horas',
    240 => 'A cada 4 horas',
    300 => 'A cada 5 horas',
];
ob_start();
?>
<section class="page-hero">
    <div class="page-hero-content">
        <span class="page-hero-icon bi bi-clock-history"></span>
        <div>
            <span class="badge">Sistema</span>
            <h1>Tempo de sincronização</h1>
            <p class="muted">Acompanhe a importação da planilha e limite execuções à janela em que o servidor fica ligado.</p>
        </div>
    </div>
</section>

<?php if ($success): ?>
    <div class="notice notice-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="notice notice-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<section class="report-summary-grid">
    <div class="report-summary-card">
        <small>Status da rotina</small>
        <strong><?= $enabled ? 'Ativa' : 'Desligada' ?></strong>
    </div>
    <div class="report-summary-card">
        <small>Última sincronização</small>
        <strong><?= htmlspecialchars($formatDateTime($task['last_run_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div class="report-summary-card">
        <small>Próxima tentativa</small>
        <strong><?= htmlspecialchars($formatDateTime($task['next_run_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div class="report-summary-card">
        <small>Último resultado</small>
        <strong><?= htmlspecialchars($formatStatus($task['last_status'] ?? null), ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-sliders"></span>Janela permitida</h2>
    <form method="post" action="/system/sync" class="form-grid">
        <label>
            Status
            <select name="sync_is_enabled">
                <option value="0" <?= !$enabled ? 'selected' : '' ?>>Desligado</option>
                <option value="1" <?= $enabled ? 'selected' : '' ?>>Ativado</option>
            </select>
        </label>
        <label>
            Intervalo
            <select name="sync_interval_minutes">
                <?php foreach ($intervalOptions as $minutes => $label): ?>
                    <option value="<?= $minutes ?>" <?= $interval === $minutes ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            Hora inicial
            <select name="sync_start_hour">
                <?php for ($hour = 0; $hour <= 23; $hour++): ?>
                    <option value="<?= $hour ?>" <?= $startHour === $hour ? 'selected' : '' ?>><?= str_pad((string) $hour, 2, '0', STR_PAD_LEFT) ?>:00</option>
                <?php endfor; ?>
            </select>
        </label>
        <label>
            Hora final
            <select name="sync_end_hour">
                <?php for ($hour = 1; $hour <= 24; $hour++): ?>
                    <option value="<?= $hour ?>" <?= $endHour === $hour ? 'selected' : '' ?>><?= str_pad((string) $hour, 2, '0', STR_PAD_LEFT) ?>:00</option>
                <?php endfor; ?>
            </select>
        </label>
        <div class="form-actions">
            <button type="submit"><span class="bi bi-save"></span>Salvar sincronização</button>
        </div>
    </form>
    <p class="muted">A rotina roda somente de segunda a sexta e considera a hora final como limite exclusivo. Exemplo: 08:00 até 18:00 permite execuções antes das 18:00.</p>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-activity"></span>Status detalhado</h2>
    <?php if ($task === null): ?>
        <p class="muted">A task `spreadsheet.import` ainda não foi registrada pelo scheduler. Ela será criada na próxima execução do cron quando a sincronização estiver ativa.</p>
    <?php else: ?>
        <div class="settings-list">
            <div class="settings-item">
                <strong><?= htmlspecialchars((string) $task['name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <small>
                    Intervalo atual: <?= (int) $task['interval_minutes'] ?> minutos
                    | Falhas consecutivas: <?= (int) $task['consecutive_failures'] ?>
                    | Mensagem: <?= htmlspecialchars((string) ($task['last_message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                </small>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="card section-card">
    <h2 class="section-title"><span class="bi bi-journal-text"></span>Logs técnicos</h2>
    <?php if ($runs === []): ?>
        <p class="muted">Nenhuma execução registrada ainda.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Início</th>
                        <th>Status</th>
                        <th>Duração</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($runs as $run): ?>
                        <?php $metadata = json_decode((string) ($run['metadata'] ?? ''), true) ?: []; ?>
                        <tr>
                            <td><?= htmlspecialchars($formatDateTime($run['started_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="notice <?= htmlspecialchars($statusClass($run['status'] ?? null), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($formatStatus($run['status'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= $run['duration_ms'] === null ? '-' : number_format(((int) $run['duration_ms']) / 1000, 2, ',', '.') . 's' ?></td>
                            <td>
                                <?= htmlspecialchars((string) ($run['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (($metadata['reason'] ?? '') === 'outside_allowed_window'): ?>
                                    <br><span class="muted">Motivo técnico: execução fora da janela permitida.</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
$content = (string) ob_get_clean();
require base_path('views/layout.php');
