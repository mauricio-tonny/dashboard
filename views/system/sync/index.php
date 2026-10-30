<?php
$title = 'Sincronização';
$enabled = ((int) ($syncSettings['sync_is_enabled'] ?? 0)) === 1;
$canRunManualSync = $user->hasRole(\App\Domain\Auth\Role::ADMIN);
$interval = (int) ($syncSettings['sync_interval_minutes'] ?? 30);
$startHour = (int) ($syncSettings['sync_start_hour'] ?? 8);
$endHour = (int) ($syncSettings['sync_end_hour'] ?? 18);
$sourceTimezone = new DateTimeZone('UTC');
$displayTimezone = new DateTimeZone('America/Sao_Paulo');
$parseSchedulerDate = static function (?string $value) use ($sourceTimezone, $displayTimezone): ?DateTimeImmutable {
    if ($value === null || trim($value) === '') {
        return null;
    }

    $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, $sourceTimezone)
        ?: new DateTimeImmutable($value, $sourceTimezone);

    return $date->setTimezone($displayTimezone);
};
$formatDateTime = static function (?string $value) use ($parseSchedulerDate): string {
    $date = $parseSchedulerDate($value);

    return $date === null ? '-' : $date->format('d/m/Y H:i:s');
};
$formatDateTimeObject = static fn (?DateTimeImmutable $date): string => $date === null ? '-' : $date->format('d/m/Y H:i:s');
$formatBytes = static function (?int $bytes): string {
    if ($bytes === null) {
        return '-';
    }

    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 2, ',', '.') . ' MB';
    }

    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    }

    return $bytes . ' B';
};
$formatStatus = static fn (?string $status): string => match ($status) {
    'success' => 'Sucesso',
    'failed' => 'Falha',
    'skipped' => 'Ignorada',
    'running' => 'Em execução',
    default => $status === null || $status === '' ? '-' : $status,
};
$formatWindowsStatus = static fn (?string $status): string => match ($status) {
    'uploaded' => 'Arquivo enviado',
    'downloaded' => 'Arquivo baixado',
    'conflict' => 'Conflito',
    'locked' => 'Arquivo em uso',
    'unchanged' => 'Sem alterações',
    'failed' => 'Falha',
    'skipped' => 'Ignorada',
    default => $status === null || $status === '' ? '-' : $status,
};
$statusClass = static fn (?string $status): string => match ($status) {
    'success' => 'notice-success',
    'failed' => 'notice-error',
    'skipped' => 'notice-warning',
    default => '',
};
$windowsStatusClass = static fn (?string $status): string => match ($status) {
    'uploaded', 'downloaded', 'unchanged' => 'notice-success',
    'conflict' => 'notice-warning',
    'locked' => 'notice-warning',
    'failed' => 'notice-error',
    'skipped' => 'notice-warning',
    default => '',
};
$lastRun = $runs[0] ?? null;
$lastWindowsEvent = $windowsEvents[0] ?? null;
$now = new DateTimeImmutable('now', $displayTimezone);
$lastRunAt = $parseSchedulerDate($task['last_run_at'] ?? null);
$fileReceivedAt = $parseSchedulerDate($spreadsheetFile['updated_at'] ?? null);
$windowsEventAt = $parseSchedulerDate($lastWindowsEvent['occurred_at'] ?? null);
$lastImportAt = $parseSchedulerDate($lastRun['finished_at'] ?? $task['last_run_at'] ?? null);
$fileExists = (bool) ($spreadsheetFile['exists'] ?? false);
$windowsStatus = (string) ($lastWindowsEvent['status'] ?? '');
$eventIndicatesNewUpload = $windowsStatus === 'uploaded';
$fileIsAfterImport = (
    $eventIndicatesNewUpload
    && $windowsEventAt !== null
    && ($lastImportAt === null || $windowsEventAt->getTimestamp() > $lastImportAt->getTimestamp())
) || (
    $fileReceivedAt !== null
    && ($lastImportAt === null || $fileReceivedAt->getTimestamp() > $lastImportAt->getTimestamp())
);
$flowStatus = $fileExists && !$fileIsAfterImport && $lastImportAt !== null ? 'success' : 'warning';
$flowStatusLabel = match (true) {
    $windowsStatus === 'failed' => 'Falha no Windows',
    !$fileExists => 'Aguardando arquivo',
    $fileIsAfterImport => 'Pendente de importação',
    $lastImportAt !== null => 'Em dia',
    default => 'Aguardando importação',
};
$flowDelayText = '-';

if ($fileReceivedAt !== null && $lastImportAt !== null) {
    $diffMinutes = (int) floor(abs($lastImportAt->getTimestamp() - $fileReceivedAt->getTimestamp()) / 60);
    $flowDelayText = $diffMinutes < 1 ? 'menos de 1 minuto' : $diffMinutes . ' minuto(s)';
}
$windowsStepComplete = in_array($windowsStatus, ['uploaded', 'downloaded', 'unchanged'], true) || ($lastWindowsEvent === null && $fileExists);
$windowsStepTitle = $lastWindowsEvent === null
    ? ($fileExists ? 'Arquivo disponível' : 'Sem evento registrado')
    : $formatWindowsStatus($windowsStatus);
$windowsStepMessage = $lastWindowsEvent === null
    ? ($fileExists ? 'Ainda sem callback do Windows; usando a cópia existente no servidor como referência.' : (string) ($spreadsheetFile['error'] ?? 'Aguardando envio da planilha.'))
    : (string) ($lastWindowsEvent['message'] ?? 'Evento recebido do sincronizador Windows.');
$staleThresholdMinutes = max(120, $interval * 3);
$isWeekday = (int) $now->format('N') <= 5;
$currentHour = (int) $now->format('G');
$insideAllowedWindow = $isWeekday && $currentHour >= $startHour && $currentHour < $endHour;
$minutesSinceLastRun = $lastRunAt === null ? null : (int) floor(max(0, $now->getTimestamp() - $lastRunAt->getTimestamp()) / 60);
$syncIsStale = $enabled && ($lastRunAt === null || ($insideAllowedWindow && $minutesSinceLastRun !== null && $minutesSinceLastRun > $staleThresholdMinutes));
$staleAlertText = $lastRunAt === null
    ? 'A rotina está ativa, mas ainda não há nenhuma sincronização registrada.'
    : sprintf(
        'A última sincronização registrada foi há %d minuto(s), acima do limite de atenção de %d minuto(s).',
        $minutesSinceLastRun,
        $staleThresholdMinutes
    );
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
            <h1>Sincronização</h1>
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

<?php if ($syncIsStale): ?>
    <div class="notice notice-warning sync-health-alert">
        <span class="bi bi-exclamation-triangle"></span>
        <div>
            <strong>Sincronização possivelmente atrasada.</strong>
            <span><?= htmlspecialchars($staleAlertText, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
<?php endif; ?>

<section class="card section-card sync-flow-card">
    <div class="sync-flow-header">
        <div>
            <h2 class="section-title"><span class="bi bi-diagram-3"></span>Fluxo da planilha</h2>
            <p class="muted">Compare a cópia disponível no servidor com a importação interna. O Windows só envia novamente quando a planilha muda ou quando a execução é forçada.</p>
        </div>
        <span class="sync-flow-badge sync-flow-badge-<?= htmlspecialchars($flowStatus, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($flowStatusLabel, ENT_QUOTES, 'UTF-8') ?>
        </span>
    </div>

    <div class="sync-flow-steps">
        <div class="sync-flow-step <?= $windowsStepComplete ? 'is-complete' : 'is-warning' ?>">
            <span class="sync-flow-icon bi bi-cloud-arrow-up"></span>
            <small>1. Windows / OneDrive</small>
            <strong><?= htmlspecialchars($windowsStepTitle, ENT_QUOTES, 'UTF-8') ?></strong>
            <span>
                <?= htmlspecialchars($windowsStepMessage, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($windowsEventAt !== null): ?>
                    <br>Último evento: <?= htmlspecialchars($formatDateTimeObject($windowsEventAt), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
        </div>
        <div class="sync-flow-step <?= $fileExists ? 'is-complete' : 'is-warning' ?>">
            <span class="sync-flow-icon bi bi-file-earmark-spreadsheet"></span>
            <small>2. Arquivo no servidor</small>
            <strong><?= htmlspecialchars($formatDateTimeObject($fileReceivedAt), ENT_QUOTES, 'UTF-8') ?></strong>
            <span>
                <?= htmlspecialchars($formatBytes($spreadsheetFile['size_bytes'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                <?php if (!empty($spreadsheetFile['hash'])): ?>
                    | Hash: <?= htmlspecialchars(substr((string) $spreadsheetFile['hash'], 0, 10), ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </span>
        </div>
        <div class="sync-flow-step <?= $lastImportAt !== null && !$fileIsAfterImport ? 'is-complete' : 'is-warning' ?>">
            <span class="sync-flow-icon bi bi-database-check"></span>
            <small>3. Importação no banco</small>
            <strong><?= htmlspecialchars($formatDateTimeObject($lastImportAt), ENT_QUOTES, 'UTF-8') ?></strong>
            <span><?= htmlspecialchars((string) ($lastRun['message'] ?? $task['last_message'] ?? 'Ainda não há importação registrada.'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <div class="sync-flow-detail">
        <span class="bi bi-hourglass-split"></span>
        <strong>Diferença entre arquivo e importação:</strong>
        <span><?= htmlspecialchars($flowDelayText, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <p class="muted sync-flow-note">Observação: o agendador do Windows compara o hash da planilha local e só faz upload quando encontra alteração. Por isso, ausência de novo envio não significa falha se o arquivo atual já estiver igual ao último enviado.</p>

    <details class="sync-flow-events">
        <summary>
            <span><span class="bi bi-pc-display-horizontal"></span>Eventos recentes do Windows</span>
            <small><?= count($windowsEvents) ?> registro(s)</small>
        </summary>

        <?php if ($windowsEvents === []): ?>
            <p class="muted">Nenhum callback do sincronizador Windows registrado ainda. A timeline continuará usando o arquivo recebido no servidor como fallback.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Horário</th>
                            <th>Status</th>
                            <th>Máquina</th>
                            <th>Arquivo</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($windowsEvents as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($formatDateTime($event['occurred_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <span class="notice <?= htmlspecialchars($windowsStatusClass($event['status'] ?? null), ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($formatWindowsStatus($event['status'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars((string) ($event['machine_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?= htmlspecialchars($formatBytes(isset($event['file_size']) ? (int) $event['file_size'] : null), ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($event['file_hash'])): ?>
                                        <br><span class="muted">Hash: <?= htmlspecialchars(substr((string) $event['file_hash'], 0, 10), ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string) ($event['message'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </details>
</section>

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

<?php if ($canRunManualSync): ?>
    <section class="card section-card">
        <h2 class="section-title"><span class="bi bi-play-circle"></span>Sincronização manual</h2>
        <p class="muted">Use esta ação para buscar a planilha do servidor e atualizar o sistema imediatamente. Ela não envia alterações do sistema para a planilha.</p>
        <form method="post" action="/system/sync/run" onsubmit="return confirm('Confirmar sincronização manual da planilha agora?');">
            <input type="hidden" name="confirm_manual_sync" value="1">
            <button type="submit"><span class="bi bi-download"></span>Buscar planilha agora</button>
        </form>
    </section>
    <section class="card section-card">
        <h2 class="section-title"><span class="bi bi-upload"></span>Enviar alterações para a planilha</h2>
        <p class="muted">Esta ação publica na planilha as alterações feitas no sistema. Use somente quando quiser enviar a fila pendente.</p>
        <p><strong><?= count($pendingWritebacks ?? []) ?></strong> alteração(ões) aguardando envio.</p>
        <form method="post" action="/system/sync/writeback" onsubmit="return confirm('Confirmar o envio das alterações pendentes para a planilha?');">
            <input type="hidden" name="confirm_writeback" value="1">
            <button type="submit" <?= empty($pendingWritebacks ?? []) ? 'disabled' : '' ?>><span class="bi bi-upload"></span>Enviar alterações agora</button>
        </form>
    </section>
<?php endif; ?>

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
    <p class="muted">A rotina roda somente de segunda a sexta no fuso America/Sao_Paulo e considera a hora final como limite exclusivo. Exemplo: 08:00 até 18:00 permite execuções antes das 18:00.</p>
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
