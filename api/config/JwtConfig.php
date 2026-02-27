<?php

namespace Config;

class JwtConfig {
    public static function getSecret(): string {
        return getenv('JWT_SECRET') ?: 'default_secret_for_dev';
    }

    public static function getIssuer(): string {
        return getenv('JWT_ISSUER') ?: 'http://localhost/api';
    }

    public static function getExpireTime(): int {
        $expire = getenv('JWT_EXPIRE_TIME');
        return $expire !== false ? (int)$expire : 3600;
    }

    public static function getAlgo(): string {
        return getenv('JWT_ALGO') ?: 'HS256';
    }
}