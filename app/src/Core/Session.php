<?php

declare(strict_types=1);

namespace Atud\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $vulnerable = Lab::isVulnerable();
        $sessionDir = STORAGE_PATH . '/sessions/' . Lab::mode();
        if (!is_dir($sessionDir)) {
            @mkdir($sessionDir, 0770, true);
        }
        if (is_dir($sessionDir) && is_writable($sessionDir)) {
            ini_set('session.save_path', $sessionDir);
        }

        $cookie = [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => !$vulnerable,
            'samesite' => $vulnerable ? 'Lax' : 'Strict',
        ];

        session_name('ATUDBLOG_SID');
        session_set_cookie_params($cookie);
        ini_set('session.use_strict_mode', $vulnerable ? '0' : '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');

        session_start();
    }

    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }

        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?: 'Lax',
            ]);
        }

        session_destroy();
    }
}
