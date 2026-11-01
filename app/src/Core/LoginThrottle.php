<?php

declare(strict_types=1);

namespace Atud\Core;

class LoginThrottle
{
    private const WINDOW_MINUTES = 15;
    private const MAX_FAILURES = 5;

    public static function isLocked(string $username, string $ip): bool
    {
        if (Lab::isVulnerable()) {
            return false;
        }

        return Database::count(
            'SELECT COUNT(*) FROM login_attempts
             WHERE success = 0
               AND created_at >= DATE_SUB(NOW(), INTERVAL ' . self::WINDOW_MINUTES . ' MINUTE)
               AND (username = ? OR ip = ?)',
            [$username, $ip]
        ) >= self::MAX_FAILURES;
    }

    public static function record(string $username, string $ip, bool $success): void
    {
        Database::run(
            'INSERT INTO login_attempts (username, ip, success) VALUES (?, ?, ?)',
            [substr($username, 0, 50), substr($ip, 0, 45), $success ? 1 : 0]
        );
    }

    public static function clearFailures(string $username, string $ip): void
    {
        if (Lab::isVulnerable()) {
            return;
        }

        Database::run(
            'DELETE FROM login_attempts WHERE success = 0 AND username = ? AND ip = ?',
            [$username, $ip]
        );
    }
}
