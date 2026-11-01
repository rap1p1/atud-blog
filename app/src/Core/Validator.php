<?php

declare(strict_types=1);

namespace Atud\Core;

class Validator
{
    public static function email(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false && strlen($value) <= 190;
    }

    public static function username(string $value): bool
    {
        return preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $value) === 1;
    }

    public static function length(string $value, int $min, int $max): bool
    {
        $length = strlen($value);

        return $length >= $min && $length <= $max;
    }

    public static function password(string $value): bool
    {
        return strlen($value) >= 10 && strlen($value) <= 128;
    }

    public static function integerId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        return null;
    }
}
