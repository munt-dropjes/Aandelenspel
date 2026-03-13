<?php

namespace Models;

class GameSettings {
    public int $id;
    public string $state;
    public int $starting_cash;
    public int $total_shares_per_company;
    public int $starting_shares_own;
    public int $starting_shares_cross;
    public bool $ai_enabled;
    public int $ai_difficulty;
    public int $npc_bankruptcy_safeguard;
}
