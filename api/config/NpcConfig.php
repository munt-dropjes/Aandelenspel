<?php

namespace Config;

use Repositories\GameSettingsRepository;

class NpcConfig
{
    private const ROLL_MAX = 100;
    private const MULTIPLIER = 1000;
    private const DESC_SUBSIDY = "Subsidie ontvangen door De Staf";
    private const DESC_EXPENSE = "Onverwachte materiaalkosten voor De Staf";

    private static ?object $settings = null;

    private static function getSettings(): object {
        if (self::$settings === null) {
            $repo = new GameSettingsRepository();
            self::$settings = $repo->getSettings();
        }
        return self::$settings;
    }

    public static function isEnabled(): bool {
        return (bool) self::getSettings()->ai_enabled;
    }

    public static function isGameActive(): bool {
        return self::getSettings()->state === 'ACTIVE';
    }

    public static function getDifficulty(): int {
        return (int) self::getSettings()->ai_difficulty;
    }

    public static function getMinBankruptcySafeguard(): int {
        return (int) self::getSettings()->npc_bankruptcy_safeguard;
    }

    public static function getThresholdIdle(): int {
        return match(self::getDifficulty()) {
            1 => 80,
            3 => 50,
            default => 70
        };
    }

    public static function getThresholdSubsidy(): int {
        return match(self::getDifficulty()) {
            1 => 95,
            3 => 60,
            default => 85
        };
    }

    public static function getVultureBuyAmount(): int {
        return (self::getDifficulty() === 3) ? 10 : 5;
    }

    public static function calculateRandomSubsidy(): int {
        return rand(5, 30) * self::MULTIPLIER;
    }

    public static function calculateRandomExpense(): int {
        return -(rand(2, 15) * self::MULTIPLIER);
    }

    public static function getRollMax(): int { return self::ROLL_MAX; }
    public static function getDescSubsidy(): string { return self::DESC_SUBSIDY; }
    public static function getDescExpense(): string { return self::DESC_EXPENSE; }
}
