<?php
namespace Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Config\JwtConfig;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Models\DTO\UserLoginRequest;
use Models\User;
use Repositories\UserRepository;

class AuthService {
    private UserRepository $userRepository;

    function __construct() {
        $this->userRepository = new UserRepository();
    }

    /**
     * Validates the Bearer token and returns the decoded payload.
     */
    public function validateToken(): object {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? '';

        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->abort(401, 'Token niet gevonden. Je bent niet ingelogd.');
        }

        $jwt = $matches[1];

        try {
            return JWT::decode($jwt, new Key(JwtConfig::getSecret(), JwtConfig::getAlgo()));
        } catch (Exception $e) {
            $this->abort(401, 'Ongeldig Token: Sessie is verlopen of corrupt.');
        }
    }

    /**
     * @throws Exception
     */
    public function login (UserLoginRequest $userRequest): User {
        if (!isset($userRequest->username) || !isset($userRequest->password)) {
            throw new Exception("Ontbrekende verplichte velden.", 400);
        }

        try {
            $user = $this->userRepository->findByUsername($userRequest->username);

            if (!$user) {
                throw new Exception("Ongeldige gebruikersnaam.", 401);
            }

            if (!password_verify($userRequest->password, $user->password)) {
                throw new Exception("Ongeldig wachtwoord.", 401);
            }

            return $user;
        } catch (Exception $e) {
            if ($e->getCode() === 401 || $e->getCode() === 400) throw $e;
            throw new Exception('Interne serverfout: ' . $e->getMessage(), 500);
        }
    }

    public function getCurrentUserFromTokenPayload(): User
    {
        $payload = $this->validateToken();
        $jwtData = $payload->data;

        $user = new User();
        $user->id = $jwtData->id;
        $user->username = $jwtData->username;
        $user->role = $jwtData->role;
        $user->company_id = isset($jwtData->company_id) ? (int)$jwtData->company_id : null;
        return $user;
    }

    #[NoReturn]
    private function abort(int $code, string $message): void {
        http_response_code($code);
        echo json_encode(['errorMessage' => $message]);
        exit;
    }
}
