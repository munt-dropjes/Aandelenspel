<?php

namespace Config;

class DatabaseConfig
{
    public static function getType(): string {
        return getenv('DB_TYPE') ?: 'mysql';
    }

    public static function getServerName(): string {
        // This resolves to the 'mysql' service in your docker-compose network
        return getenv('DB_SERVER') ?: 'mysql';
    }

    public static function getUsername(): string {
        return getenv('DB_USER') ?: 'developer';
    }

    public static function getPassword(): string {
        return getenv('DB_PASS') ?: 'secret123';
    }

    public static function getDatabase(): string {
        return getenv('DB_NAME') ?: 'developmentdb';
    }
}