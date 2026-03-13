<?php
namespace Repositories;

use Models\GameSettings;
use Exception;
use PDO;

class GameSettingsRepository extends Repository {

    public function getSettings(): GameSettings {
        $stmt = $this->connection->query("SELECT * FROM game_settings WHERE id = 1");
        $stmt->setFetchMode(PDO::FETCH_CLASS, GameSettings::class);
        return $stmt->fetch();
    }

    public function updateSettings(GameSettings $s): void {
        $sql = "UPDATE game_settings SET state=?, starting_cash=?, total_shares_per_company=?, starting_shares_own=?, starting_shares_cross=?, ai_enabled=?, ai_difficulty=?, npc_bankruptcy_safeguard=? WHERE id=1";
        $this->connection->prepare($sql)->execute([
            $s->state, $s->starting_cash, $s->total_shares_per_company,
            $s->starting_shares_own, $s->starting_shares_cross,
            (int)$s->ai_enabled, $s->ai_difficulty, $s->npc_bankruptcy_safeguard
        ]);
    }

    /**
     * @throws Exception
     */
    public function resetGameToSetup(): void {
        try {
            $this->connection->beginTransaction();
            $this->connection->exec("UPDATE game_settings SET state = 'SETUP' WHERE id = 1");
            $this->connection->exec("DELETE FROM users WHERE role != 'admin'");
            $this->connection->exec("DELETE FROM companies");
            $this->connection->commit();
        } catch (Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw new Exception("Reset mislukt in database: " . $e->getMessage(), 500);
        }
    }

    /**
     * @throws Exception
     */
    public function buildNewGame(GameSettings $settings, array $companiesData): void {
        try {
            $this->connection->beginTransaction();

            $this->connection->exec("DELETE FROM users WHERE role != 'admin'");
            $this->connection->exec("DELETE FROM companies");

            $this->updateSettings($settings);

            $stmtComp = $this->connection->prepare("INSERT INTO companies (name, color, cash, is_npc) VALUES (?, ?, ?, 0)");
            $stmtUser = $this->connection->prepare("INSERT INTO users (company_id, username, email, password, role) VALUES (?, ?, ?, ?, 'user')");

            $companyIds = [];
            foreach ($companiesData as $cData) {
                $stmtComp->execute([$cData['name'], $cData['color'], $settings->starting_cash]);
                $cid = $this->connection->lastInsertId();
                $companyIds[] = $cid;
                $stmtUser->execute([$cid, $cData['username'], $cData['email'], $cData['hash']]);
            }

            if ($settings->ai_enabled) {
                $this->connection->prepare("INSERT INTO companies (name, color, cash, is_npc) VALUES (?, ?, ?, 1)")
                    ->execute(["De Staf", "#E53935", $settings->starting_cash]);
                $companyIds[] = $this->connection->lastInsertId();
            }

            $stmtShare = $this->connection->prepare("INSERT INTO shares (company_id, owner_id, amount) VALUES (?, ?, ?)");
            foreach ($companyIds as $targetId) {
                $bankShares = $settings->total_shares_per_company;

                foreach ($companyIds as $ownerId) {
                    $amt = ($targetId === $ownerId) ? $settings->starting_shares_own : $settings->starting_shares_cross;
                    if ($amt > 0) {
                        $stmtShare->execute([$targetId, $ownerId, $amt]);
                        $bankShares -= $amt;
                    }
                }

                if ($bankShares > 0) {
                    $stmtShare->execute([$targetId, null, $bankShares]);
                }
            }

            $this->connection->commit();
        } catch (Exception $e) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            throw new Exception("Starten mislukt in database: " . $e->getMessage(), 500);
        }
    }
}
