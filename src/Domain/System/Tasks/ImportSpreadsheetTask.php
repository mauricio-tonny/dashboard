<?php

declare(strict_types=1);

namespace App\Domain\System\Tasks;

use App\Domain\System\DiscordNotifier;
use App\Domain\System\ScheduledTask;
use App\Domain\System\ScheduledTaskResult;

final class ImportSpreadsheetTask implements ScheduledTask
{
    public function __construct(
        private string $projectRoot,
        private int $intervalMinutes,
        private DiscordNotifier $discordNotifier
    ) {
    }

    public function code(): string
    {
        return 'spreadsheet.import';
    }

    public function name(): string
    {
        return 'Importar planilha financeira';
    }

    public function intervalMinutes(): int
    {
        return max(5, $this->intervalMinutes);
    }

    public function run(): ScheduledTaskResult
    {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->projectRoot . '/bin/import_spreadsheet.php') . ' --apply';
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes, $this->projectRoot);

        if (!is_resource($process)) {
            return ScheduledTaskResult::failure('Nao foi possivel iniciar a importacao da planilha.');
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $output = trim((string) $stdout);
        $error = trim((string) $stderr);
        $metadata = [
            'exit_code' => $exitCode,
            'stdout_tail' => $this->tail($output),
            'stderr_tail' => $this->tail($error),
        ];

        if ($exitCode !== 0) {
            return ScheduledTaskResult::failure('Falha ao importar a planilha pelo scheduler.', $metadata);
        }

        $processedEntries = $this->extractOutputInt($output, 'Lancamentos processados');
        $newEntries = $this->extractOutputInt($output, 'Lancamentos novos');
        $changedEntries = $this->extractOutputInt($output, 'Lancamentos alterados');
        $importedEntries = $this->extractImportedEntries($output);
        $metadata['entries_processed'] = $processedEntries;
        $metadata['entries_new'] = $newEntries;
        $metadata['entries_changed'] = $changedEntries;
        $metadata['entries_persisted'] = $importedEntries;

        if (($importedEntries ?? 0) > 0) {
            $this->discordNotifier->spreadsheetImportChanged(
                $processedEntries ?? $importedEntries,
                $newEntries ?? 0,
                $changedEntries ?? 0
            );
        } else {
            $this->discordNotifier->spreadsheetImportUnchanged($processedEntries ?? 0);
        }

        $message = $importedEntries === null
            ? 'Planilha importada pelo scheduler.'
            : "Planilha importada pelo scheduler: {$importedEntries} lancamentos gravados/atualizados.";

        return ScheduledTaskResult::success($message, $metadata);
    }

    private function extractImportedEntries(string $output): ?int
    {
        return $this->extractOutputInt($output, 'Lancamentos gravados/atualizados');
    }

    private function extractOutputInt(string $output, string $label): ?int
    {
        $pattern = '/^' . preg_quote($label, '/') . ':\s*(\d+)/mi';

        if (preg_match($pattern, $this->normalizeAscii($output), $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function tail(string $value, int $limit = 2000): string
    {
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, -$limit);
    }

    private function normalizeAscii(string $value): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted === false ? $value : $converted;
    }
}
