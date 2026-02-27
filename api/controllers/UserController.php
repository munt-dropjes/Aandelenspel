<?php
namespace Controllers;

use Models\DTO\UserCreateRequest;
use Models\DTO\UserManyRequest;
use Models\DTO\UserUpdateRequest;
use models\User;
use Services\AuthService;
use Services\UserService;
use Exception;

class UserController extends Controller
{
    private UserService $userService;
    private AuthService $authService;

    public function __construct() {
        $this->userService = new UserService();
        $this->authService = new AuthService();
    }

    public function getAll() {
        try {
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $role = $_GET['role'] ?? null;

            $request = UserManyRequest::Create($limit, $offset, $role);

            $users = $this->userService->getAllUsers($request);
        } catch (Exception $e){
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }

        if(!empty($users)) {
            $this->respond($users);
        } else {
            $this->respondWithError(204, "Geen gebruikers gevonden.");
        }
    }

    public function getById(int $id) {
        try {
            $user = $this->userService->getById($id);
            if ($user) {
                $this->respond($user);
            } else {
                $this->respondWithError(404, "Gebruiker niet gevonden.");
            }
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function newUser() {
        $currentUser = $this->authService->getCurrentUserFromTokenPayload();
        if ($currentUser->role !== 'admin') {
            $this->respondWithError(403, "Onbevoegd: Alleen stafleden kunnen gebruikers aanmaken.");
        }

        $newUserRequest = $this->requestObjectFromPostedJson(UserCreateRequest::class);

        if (!$newUserRequest->email || !$newUserRequest->password || !$newUserRequest->username){
            $this->respondWithError(400, "Ontbrekende verplichte velden");
        }

        try {
            $newUser = $this->userService->registerUser($newUserRequest);
            $this->respond($newUser);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function updateUser(int $id) {
        try {
            $currentUser = $this->authService->getCurrentUserFromTokenPayload();
            if ($currentUser->role !== 'admin' && $currentUser->id !== $id) {
                $this->respondWithError(403, "Onbevoegd: Je kunt alleen je eigen profiel bijwerken.");
            }

            $inputUser = $this->requestObjectFromPostedJson(UserUpdateRequest::class);

            if ($currentUser->role !== 'admin' && isset($inputUser->role) && $inputUser->role !== $currentUser->role) {
                $this->respondWithError(403, "Onbevoegd: Je kunt je eigen rechten niet verhogen.");
            }

            $this->respond($this->userService->updateUser($id, $inputUser));
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function deleteUser($id) {
        try {
            $currentUser = $this->authService->getCurrentUserFromTokenPayload();
            if ($currentUser->role !== 'admin') {
                $this->respondWithError(403, "Onbevoegd: Alleen stafleden kunnen gebruikers verwijderen.");
            }

            $user = $this->userService->getById($id);
            if (!$user) {
                $this->respondWithError(404, "Gebruiker niet gevonden.");
            }

            $this->userService->deleteUser($id);
            $this->respond(['message' => 'Gebruiker succesvol verwijderd.']);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }
}
