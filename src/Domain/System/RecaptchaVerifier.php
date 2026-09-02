<?php

declare(strict_types=1);

namespace App\Domain\System;

final class RecaptchaVerifier
{
    public function verify(string $token, string $expectedAction, string $remoteIp, array $settings): array
    {
        if (!($settings['recaptcha_is_ready'] ?? false)) {
            return ['success' => true, 'score' => null, 'skipped' => true];
        }

        $secretKey = (string) ($settings['recaptcha_secret_key'] ?? '');

        if ($token === '' || $secretKey === '') {
            return ['success' => false, 'score' => null, 'error' => 'missing_token'];
        }

        $payload = http_build_query([
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);
        $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);

        if (!is_string($response) || $response === '') {
            return ['success' => false, 'score' => null, 'error' => 'verification_unavailable'];
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return ['success' => false, 'score' => null, 'error' => 'invalid_response'];
        }

        $score = isset($data['score']) ? (float) $data['score'] : null;
        $minScore = (float) ($settings['recaptcha_min_score'] ?? 0.5);
        $action = (string) ($data['action'] ?? '');
        $success = (bool) ($data['success'] ?? false)
            && $action === $expectedAction
            && $score !== null
            && $score >= $minScore;

        return [
            'success' => $success,
            'score' => $score,
            'action' => $action,
            'hostname' => $data['hostname'] ?? null,
            'error_codes' => $data['error-codes'] ?? [],
        ];
    }
}
