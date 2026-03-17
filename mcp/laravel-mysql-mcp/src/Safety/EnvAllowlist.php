<?php

declare(strict_types=1);

namespace LaravelMysqlMcp\Safety;

use Illuminate\Support\Facades\Config;

final class EnvAllowlist
{
    /**
     * @return string[]
     */
    public static function envKeys(): array
    {
        return [
            'APP_ENV',
            'APP_DEBUG',
            'APP_NAME',
            'APP_URL',
            'DB_CONNECTION',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'CACHE_DRIVER',
            'QUEUE_CONNECTION',
            'SESSION_DRIVER',
        ];
    }

    /**
     * @return string[]
     */
    public static function configKeys(): array
    {
        return [
            'app.env',
            'app.debug',
            'app.name',
            'app.url',
            'app.timezone',
            'database.default',
            'database.connections.mysql.host',
            'database.connections.mysql.port',
            'database.connections.mysql.database',
            'cache.default',
            'queue.default',
            'session.driver',
        ];
    }

    /**
     * @param string[] $keys
     *
     * @return array<string, mixed>
     */
    public static function sanitizeEnv(array $keys = []): array
    {
        $allowed = array_flip(self::envKeys());
        $keys = empty($keys) ? self::envKeys() : $keys;

        $result = [];
        foreach ($keys as $key) {
            if (!isset($allowed[$key])) {
                continue;
            }

            $value = getenv($key);
            if ($value === false) {
                $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
            }

            $result[$key] = $value;
        }

        ksort($result);

        return $result;
    }

    /**
     * @param string[] $keys
     *
     * @return array<string, mixed>
     */
    public static function sanitizeConfig(array $keys = []): array
    {
        $allowed = array_flip(self::configKeys());
        $keys = empty($keys) ? self::configKeys() : $keys;

        $result = [];
        foreach ($keys as $key) {
            if (!isset($allowed[$key])) {
                continue;
            }

            $result[$key] = Config::get($key);
        }

        ksort($result);

        return $result;
    }
}