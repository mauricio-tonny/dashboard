<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public function __construct(
        private string $content,
        private int $status = 200,
        private array $headers = []
    ) {
        $this->headers = array_merge([
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ], $this->headers);
    }

    public static function view(string $template, array $data = [], int $status = 200): self
    {
        return new self(view($template, $data), $status);
    }

    public static function redirect(string $location): self
    {
        return new self('', 302, ['Location' => $location]);
    }

    public function send(): void
    {
        http_response_code($this->status);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $this->content;
    }
}
