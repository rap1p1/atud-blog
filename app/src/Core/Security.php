<?php

declare(strict_types=1);

namespace Atud\Core;

class Security
{
    public static function sendHeaders(): void
    {
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, max-age=0');

        if (Lab::isVulnerable()) {
            return;
        }

        header('Content-Security-Policy: default-src \'self\'; script-src \'self\'; style-src \'self\'; img-src \'self\' data:; font-src \'self\'; connect-src \'self\'; base-uri \'none\'; form-action \'self\'; frame-ancestors \'none\'; object-src \'none\'');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: same-origin');
        header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
        header('Cross-Origin-Opener-Policy: same-origin');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function randomToken(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public static function safeEqual(string $known, string $given): bool
    {
        return hash_equals($known, $given);
    }
}
