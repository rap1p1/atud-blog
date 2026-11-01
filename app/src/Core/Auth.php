<?php

declare(strict_types=1);

namespace Atud\Core;

class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }

        self::$loaded = true;
        $id = $_SESSION['uid'] ?? null;
        if (!is_int($id) && !ctype_digit((string) $id)) {
            return null;
        }

        $user = Database::one(
            'SELECT id, username, email, role, is_active, created_at FROM users WHERE id = ? LIMIT 1',
            [(int) $id]
        );

        if ($user === null || (int) $user['is_active'] !== 1) {
            return null;
        }

        self::$user = $user;

        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        if (!Lab::isVulnerable()) {
            Session::regenerate();
            unset($_SESSION['_csrf']);
        }

        $_SESSION['uid'] = (int) $user['id'];
        $_SESSION['role'] = (string) $user['role'];
        $_SESSION['login_at'] = time();

        self::$user = null;
        self::$loaded = false;
    }

    public static function logout(): void
    {
        if (Lab::isVulnerable()) {
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => '/',
                'secure' => false,
                'httponly' => false,
                'samesite' => 'Lax',
            ]);

            return;
        }

        Session::destroy();
        self::$user = null;
        self::$loaded = false;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();

        return $user !== null && $user['role'] === 'admin';
    }

    public static function requireLogin(): array
    {
        $user = self::user();
        if ($user !== null) {
            return $user;
        }

        flash('warning', 'Bạn cần đăng nhập để thực hiện thao tác này.');
        redirect('/login');
    }

    public static function requireAdmin(): array
    {
        $user = self::requireLogin();
        if ($user['role'] === 'admin') {
            return $user;
        }

        http_response_code(403);
        View::render('error', [
            'title' => 'Không đủ quyền',
            'code' => 403,
            'message' => 'Tài khoản hiện tại không có quyền truy cập trang quản trị.',
        ]);
        exit;
    }
}
