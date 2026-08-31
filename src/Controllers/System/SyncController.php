<?php

declare(strict_types=1);

namespace App\Controllers\System;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;
use App\Domain\Auth\Role;
use App\Domain\System\DiscordNotifier;
use App\Domain\System\SchedulerRepository;
use App\Domain\System\ScheduledTaskResult;
use App\Domain\System\SpreadsheetSettingsRepository;
use App\Domain\System\Tasks\ImportSpreadsheetTask;

final class SyncController extends Controller
{
    private const TASK_CODE = 'spreadsheet.import';

    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $scheduler = $this->app->make(SchedulerRepository::class);

        return Response::view('system/sync/index', [
            'user' => $auth->user(),
            'success' => $this->app->make(Session::class)->pullFlash('success'),
            'error' => $this->app->make(Session::class)->pullFlash('error'),
            'syncSettings' => $this->app->make(SpreadsheetSettingsRepository::class)->syncSettings(),
            'task' => $scheduler->task(self::TASK_CODE),
            'runs' => $scheduler->latestRuns(self::TASK_CODE, 40),
        ]);
    }

    public function save(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $enabled = (string) $request->input('sync_is_enabled') === '1';
        $intervalMinutes = (int) $request->input('sync_interval_minutes', 30);
        $startHour = (int) $request->input('sync_start_hour', 8);
        $endHour = (int) $request->input('sync_end_hour', 18);

        if (!in_array($intervalMinutes, [15, 30, 60, 120, 240, 300], true)) {
            $this->flash('error', 'Selecione um intervalo de sincronização válido.');
            return Response::redirect('/system/sync');
        }

        if ($startHour < 0 || $startHour > 23 || $endHour < 1 || $endHour > 24 || $startHour >= $endHour) {
            $this->flash('error', 'Informe uma janela de sincronização válida.');
            return Response::redirect('/system/sync');
        }

        $this->app->make(SpreadsheetSettingsRepository::class)->saveSyncSettings($enabled, $intervalMinutes, $startHour, $endHour);
        $this->app->make(SchedulerRepository::class)->configureTask(
            self::TASK_CODE,
            'Importar planilha financeira',
            $intervalMinutes,
            $enabled,
            new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
        );
        $this->app->make(AuditLogger::class)->log('spreadsheet_sync_settings_updated', 'spreadsheet_settings', 1, $auth->user(), [
            'sync_is_enabled' => $enabled,
            'sync_interval_minutes' => $intervalMinutes,
            'sync_start_hour' => $startHour,
            'sync_end_hour' => $endHour,
        ]);
        $this->flash('success', 'Configurações de sincronização salvas.');

        return Response::redirect('/system/sync');
    }

    public function runNow(Request $request): Response
    {
        $auth = $this->authorizeAdmin();

        if ($auth instanceof Response) {
            return $auth;
        }

        if ((string) $request->input('confirm_manual_sync') !== '1') {
            $this->flash('error', 'Confirme a sincronização manual antes de executar.');
            return Response::redirect('/system/sync');
        }

        $settings = $this->app->make(SpreadsheetSettingsRepository::class)->syncSettings();
        $intervalMinutes = max(5, (int) ($settings['sync_interval_minutes'] ?? 30));
        $repository = $this->app->make(SchedulerRepository::class);
        $task = new ImportSpreadsheetTask(
            base_path(),
            $intervalMinutes,
            $this->app->make(DiscordNotifier::class),
            $settings,
            true
        );
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $repository->configureTask(
            self::TASK_CODE,
            $task->name(),
            $intervalMinutes,
            ((int) ($settings['sync_is_enabled'] ?? 0)) === 1,
            $now
        );

        $token = bin2hex(random_bytes(16));

        if (!$repository->acquireManualLock(self::TASK_CODE, $token, $now)) {
            $this->flash('error', 'Já existe uma sincronização em execução. Aguarde a finalização antes de iniciar outra.');
            return Response::redirect('/system/sync');
        }

        $startedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $runId = $repository->startRun(self::TASK_CODE, $startedAt);

        try {
            $result = $task->run();
        } catch (\Throwable $exception) {
            $result = ScheduledTaskResult::failure($exception->getMessage(), [
                'exception' => $exception::class,
            ]);
        }

        $metadata = [
            ...$result->metadata,
            'trigger' => 'manual',
            'triggered_by_user_id' => $auth->user()?->id,
        ];
        $result = new ScheduledTaskResult($result->success, $result->message, $metadata, $result->status);
        $finishedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $repository->finishRun($runId, $result, $startedAt, $finishedAt);
        $repository->releaseTask(self::TASK_CODE, $token, $task, $result, $finishedAt);
        $this->app->make(AuditLogger::class)->log('spreadsheet_sync_manual_run', 'scheduled_task', null, $auth->user(), [
            'status' => $result->status,
            'message' => $result->message,
        ]);
        $this->flash($result->success ? 'success' : 'error', $result->message);

        return Response::redirect('/system/sync');
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_SPREADSHEET_URL)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function authorizeAdmin(): AuthService|Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        if (!$auth->user()?->hasRole(Role::ADMIN)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function flash(string $type, string $message): void
    {
        $this->app->make(Session::class)->flash($type, $message);
    }
}
