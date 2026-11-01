<?php

declare(strict_types=1);

namespace Atud\Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function check(mixed $token): bool
    {
        $known = $_SESSION['_csrf'] ?? '';

        return is_string($token) && is_string($known) && $known !== '' && hash_equals($known, $token);
    }

    public static function requireValid(mixed $token): void
    {
        if (self::check($token)) {
            return;
        }

        http_response_code(403);
        View::render('error', [
            'title' => 'Yêu cầu bị từ chối',
            'code' => 403,
            'message' => 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng thử lại.',
        ]);
        exit;
    }
}
