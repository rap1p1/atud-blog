<?php

declare(strict_types=1);

use Atud\Core\Lab;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        if ($path === '') {
            return '/';
        }

        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path): never
    {
        header('Location: ' . url($path), true, 302);
        exit;
    }
}

if (!function_exists('back')) {
    function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && str_starts_with($referer, (string) ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? ''))) {
            header('Location: ' . $referer, true, 302);
            exit;
        }

        redirect($fallback);
    }
}

if (!function_exists('method_is')) {
    function method_is(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }
}

if (!function_exists('input')) {
    function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}

if (!function_exists('query')) {
    function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('take_flashes')) {
    function take_flashes(): array
    {
        $items = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $items;
    }
}

if (!function_exists('format_datetime')) {
    function format_datetime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date('d/m/Y H:i', $timestamp);
    }
}

if (!function_exists('excerpt')) {
    function excerpt(string $text, int $length = 180): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if (strlen($plain) <= $length) {
            return $plain;
        }

        return rtrim(substr($plain, 0, $length)) . '...';
    }
}

if (!function_exists('client_ip')) {
    function client_ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return substr((string) $ip, 0, 45);
    }
}

if (!function_exists('is_vulnerable')) {
    function is_vulnerable(): bool
    {
        return Lab::isVulnerable();
    }
}
