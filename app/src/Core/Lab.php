<?php

declare(strict_types=1);

namespace Atud\Core;

class Lab
{
    public const MODE_VULNERABLE = 'vulnerable';
    public const MODE_FIXED = 'fixed';

    public static function mode(): string
    {
        $mode = strtolower(trim((string) Config::get('APP_MODE', self::MODE_FIXED)));

        return $mode === self::MODE_VULNERABLE ? self::MODE_VULNERABLE : self::MODE_FIXED;
    }

    public static function isVulnerable(): bool
    {
        return self::mode() === self::MODE_VULNERABLE;
    }

    public static function label(): string
    {
        return self::isVulnerable() ? 'CÓ LỖI' : 'ĐÃ VÁ';
    }
}
