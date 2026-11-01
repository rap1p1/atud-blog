<?php

declare(strict_types=1);

namespace Atud\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function pdo(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Config::require('DB_HOST'),
            Config::get('DB_PORT', '3306'),
            Config::require('DB_NAME')
        );

        try {
            self::$connection = new PDO(
                $dsn,
                Config::require('DB_USER'),
                Config::require('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false,
                ]
            );
        } catch (PDOException $exception) {
            throw new \RuntimeException('Không thể kết nối cơ sở dữ liệu.', 0, $exception);
        }

        if (!Lab::isVulnerable()) {
            self::$connection->exec('SET SESSION max_execution_time = 2000');
        }

        return self::$connection;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return $row === false ? null : $row;
    }

    public static function all(string $sql, array $params = []): array
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public static function run(string $sql, array $params = []): void
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);
    }

    public static function lastId(): int
    {
        return (int) self::pdo()->lastInsertId();
    }

    public static function count(string $sql, array $params = []): int
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn();
    }
}
