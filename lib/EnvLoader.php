<?php

class EnvLoader
{
    private static array $variables = [];
    private static bool $loaded = false;

    public static function load(string $filePath = null): void
    {
        if (self::$loaded) {
            return;
        }

        $filePath = $filePath ?? self::getDefaultPath();

        if (!file_exists($filePath)) {
            throw new Exception("Файл окружения не найден: {$filePath}");
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            $value = self::parseValue($value);

            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }

            self::$variables[$key] = $value;
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        return self::$variables[$key] ?? $_ENV[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        if (!self::$loaded) {
            self::load();
        }

        return array_key_exists($key, self::$variables) || array_key_exists($key, $_ENV);
    }

    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }

        return array_merge($_ENV, self::$variables);
    }

    public static function set(string $key, mixed $value): void
    {
        self::$variables[$key] = $value;
        $_ENV[$key] = $value;
    }

    private static function parseValue(string $value): mixed
    {
        if ($value === 'true' || $value === 'TRUE') {
            return true;
        }

        if ($value === 'false' || $value === 'FALSE') {
            return false;
        }

        if ($value === 'null' || $value === 'NULL') {
            return null;
        }

        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        if (preg_match('/^["\'](.*)["\']$/', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    private static function getDefaultPath(): string
    {
        $possiblePaths = [
            __DIR__ . '/../.env',
            __DIR__ . '/../.env.local',
            __DIR__ . '/../.env.production',
            __DIR__ . '/../.env.development'
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        throw new Exception('Файл окружения не найден в стандартных местах');
    }

    public static function getDatabaseDsn(): string
    {
        $host = self::get('DB_HOST', 'localhost');
        $name = self::get('DB_NAME');
        $charset = self::get('DB_CHARSET', 'utf8mb4');

        return "mysql:host={$host};dbname={$name};charset={$charset}";
    }

    public static function getDatabaseCredentials(): array
    {
        return [
            'dsn' => self::getDatabaseDsn(),
            'username' => self::get('DB_USER'),
            'password' => self::get('DB_PASSWORD')
        ];
    }

    public static function isProduction(): bool
    {
        return self::get('APP_ENV', 'production') === 'production';
    }
}
