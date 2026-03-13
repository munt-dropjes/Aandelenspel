<?php
namespace Services;

use Exception;
use Models\GameSettings;
use Repositories\GameSettingsRepository;

class GameService {
    private GameSettingsRepository $settingsRepo;

    public function __construct() {
        $this->settingsRepo = new GameSettingsRepository();
    }

    public function getSettings(): GameSettings {
        return $this->settingsRepo->getSettings();
    }

    /**
     * @throws Exception
     */
    public function resetGame(): void {
        $this->settingsRepo->resetGameToSetup();
    }

    /**
     * @throws Exception
     */
    public function startGame(object $payload): array {
        $settings = $this->settingsRepo->getSettings();
        $numCompanies = count($payload->companies);

        $crossTotal = $payload->starting_shares_cross * ($numCompanies - 1);
        if (($payload->starting_shares_own + $crossTotal) > $payload->total_shares_per_company) {
            throw new Exception("Wiskunde fout: Er worden meer aandelen uitgedeeld dan er in totaal beschikbaar zijn!", 400);
        }

        $settings->state = 'ACTIVE';
        $settings->starting_cash = $payload->starting_cash;
        $settings->total_shares_per_company = $payload->total_shares_per_company;
        $settings->starting_shares_own = $payload->starting_shares_own;
        $settings->starting_shares_cross = $payload->starting_shares_cross;
        $settings->ai_enabled = $payload->ai_enabled;
        $settings->ai_difficulty = $payload->ai_difficulty;
        $settings->npc_bankruptcy_safeguard = $payload->npc_bankruptcy_safeguard;

        $companiesData = [];
        $credentials = [];

        // Generate Human Players
        foreach ($payload->companies as $c) {
            $pin = rand(1000, 9999);
            $username = str_replace(' ', '', $c->name);
            $hash = password_hash((string)$pin, PASSWORD_DEFAULT);
            $email = strtolower($username) . "@game.local";

            $companiesData[] = [
                'name' => $c->name,
                'color' => $c->color,
                'username' => $username,
                'email' => $email,
                'hash' => $hash
            ];

            $credentials[] = [
                'bedrijf' => $c->name,
                'gebruiker' => $username,
                'wachtwoord' => $pin
            ];
        }

        // Format NPC Data
        $npcsData = [];
        if (isset($payload->npcs) && is_array($payload->npcs)) {
            foreach ($payload->npcs as $npc) {
                $npcsData[] = [
                    'name' => $npc->name,
                    'color' => $npc->color
                ];
            }
        }

        // Send both to the Repository to build the database
        $this->settingsRepo->buildNewGame($settings, $companiesData, $npcsData);

        return $credentials;
    }
}
