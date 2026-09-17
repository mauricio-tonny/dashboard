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
use App\Domain\Finance\FamilyProfileRepository;

final class FamilyProfileController extends Controller
{
    public function index(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $repository = $this->app->make(FamilyProfileRepository::class);
        $session = $this->app->make(Session::class);

        return Response::view('system/family-profile/index', [
            'user' => $auth->user(),
            'success' => $session->pullFlash('success'),
            'error' => $session->pullFlash('error'),
            'settings' => $repository->settings(),
            'members' => $repository->members(),
            'pets' => $repository->pets(),
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $region = $this->allowed((string) $request->input('region'), array_keys($this->regions()), 'brasil');
        $residenceArea = $this->allowed((string) $request->input('residence_area'), ['urban', 'rural'], 'urban');
        $notes = trim((string) $request->input('notes')) ?: null;
        $this->app->make(FamilyProfileRepository::class)->saveSettings($region, $residenceArea, $notes, $auth->user()?->id);
        $this->audit('family_profile_settings_saved', 'family_profile_settings', 1, ['region' => $region, 'residence_area' => $residenceArea]);
        $this->flash('success', 'Perfil familiar atualizado.');

        return Response::redirect('/system/family-profile');
    }

    public function saveMember(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $data = [
            'name' => mb_strtoupper(trim((string) $request->input('name'))),
            'sex' => $this->allowed((string) $request->input('sex'), ['female', 'male', 'other'], 'other'),
            'birth_date' => $this->dateOrNull((string) $request->input('birth_date')),
            'role' => trim((string) $request->input('role')) ?: null,
            'income_weight' => $this->percent((string) $request->input('income_weight')),
        ];

        if ($data['name'] === '') {
            $this->flash('error', 'Informe o nome do membro da familia.');
            return Response::redirect('/system/family-profile');
        }

        $id = $this->app->make(FamilyProfileRepository::class)->saveMember($this->nullableInt($request->input('id')), $data, $auth->user()?->id);
        $this->audit('family_member_saved', 'family_member', $id, ['name' => $data['name']]);
        $this->flash('success', 'Membro da familia salvo.');

        return Response::redirect('/system/family-profile');
    }

    public function toggleMember(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $active = (string) $request->input('active') === '1';

        if ($id > 0) {
            $this->app->make(FamilyProfileRepository::class)->setMemberActive($id, $active);
            $this->audit($active ? 'family_member_enabled' : 'family_member_disabled', 'family_member', $id);
            $this->flash('success', $active ? 'Membro reativado.' : 'Membro desativado.');
        }

        return Response::redirect('/system/family-profile');
    }

    public function savePet(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $data = [
            'name' => mb_strtoupper(trim((string) $request->input('name'))),
            'type' => mb_strtoupper(trim((string) $request->input('type'))),
            'birth_date' => $this->dateOrNull((string) $request->input('birth_date')),
        ];

        if ($data['name'] === '' || $data['type'] === '') {
            $this->flash('error', 'Informe nome e tipo do pet.');
            return Response::redirect('/system/family-profile');
        }

        $id = $this->app->make(FamilyProfileRepository::class)->savePet($this->nullableInt($request->input('id')), $data, $auth->user()?->id);
        $this->audit('family_pet_saved', 'family_pet', $id, ['name' => $data['name'], 'type' => $data['type']]);
        $this->flash('success', 'Pet salvo.');

        return Response::redirect('/system/family-profile');
    }

    public function togglePet(Request $request): Response
    {
        $auth = $this->authorize();

        if ($auth instanceof Response) {
            return $auth;
        }

        $id = (int) $request->input('id');
        $active = (string) $request->input('active') === '1';

        if ($id > 0) {
            $this->app->make(FamilyProfileRepository::class)->setPetActive($id, $active);
            $this->audit($active ? 'family_pet_enabled' : 'family_pet_disabled', 'family_pet', $id);
            $this->flash('success', $active ? 'Pet reativado.' : 'Pet desativado.');
        }

        return Response::redirect('/system/family-profile');
    }

    private function authorize(): AuthService|Response
    {
        $auth = $this->app->make(AuthService::class);

        if (!$auth->check()) {
            return Response::redirect('/login');
        }

        if (!$auth->user()?->can(Permission::MANAGE_FAMILY_PROFILE)) {
            return new Response('Acesso negado.', 403);
        }

        return $auth;
    }

    private function regions(): array
    {
        return [
            'brasil' => 'Brasil',
            'norte' => 'Norte',
            'nordeste' => 'Nordeste',
            'sudeste' => 'Sudeste',
            'sul' => 'Sul',
            'centro_oeste' => 'Centro-Oeste',
        ];
    }

    private function allowed(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private function dateOrNull(string $value): ?string
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable ? $date->format('Y-m-d') : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function percent(string $value): float
    {
        $normalized = str_replace(',', '.', trim($value));

        return is_numeric($normalized) ? max(0.0, min(100.0, (float) $normalized)) : 0.0;
    }

    private function flash(string $key, string $message): void
    {
        $this->app->make(Session::class)->flash($key, $message);
    }

    private function audit(string $action, string $entityType, ?int $entityId = null, array $metadata = []): void
    {
        $auth = $this->app->make(AuthService::class);
        $this->app->make(AuditLogger::class)->log($action, $entityType, $entityId, $auth->user(), $metadata);
    }
}
