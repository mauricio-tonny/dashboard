<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Domain\Audit\AuditLogger;
use App\Domain\Auth\AuthService;
use App\Domain\Auth\Permission;

final class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::VIEW_AUDIT_LOGS)) {
            return new Response('Acesso negado.', 403);
        }

        return Response::view('admin/audit-logs/index', [
            'user' => $auth->user(),
            'logs' => $this->app->make(AuditLogger::class)->latest(100),
        ]);
    }
}
