<?php

namespace Controllers;

use Exception;
use Services\AuthService;
use Services\GameService;

class GameController extends Controller {
    private GameService $gameService;
    private AuthService $authService;

    public function __construct() {
        $this->gameService = new GameService();
        $this->authService = new AuthService();
    }

    public function getSettings(): void
    {
        try {
            $this->respond($this->gameService->getSettings());
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function reset(): void
    {
        try {
            $user = $this->authService->getCurrentUserFromTokenPayload();
            if ($user->role !== 'admin') throw new Exception("Onbevoegd.", 403);

            $this->gameService->resetGame();
            $this->respond(["message" => "Game is gewist en gereset naar SETUP modus."]);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function start(): void
    {
        try {
            $user = $this->authService->getCurrentUserFromTokenPayload();
            if ($user->role !== 'admin') throw new Exception("Onbevoegd.", 403);

            $json = file_get_contents('php://input');
            $payload = json_decode($json);

            $credentials = $this->gameService->startGame($payload);
            $this->respond(["credentials" => $credentials]);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }
}
