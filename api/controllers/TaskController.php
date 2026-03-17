<?php

namespace Controllers;

use Exception;
use Models\DTO\TaskCompleteRequest;
use Services\AuthService;
use Services\TaskService;

class TaskController extends Controller
{
    private TaskService $taskService;
    private AuthService $authService;

    public function __construct(){
        $this->taskService = new TaskService();
        $this->authService = new AuthService();
    }

    public function getAll() {
        try {
            $this->respond($this->taskService->getAllTasks());
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function complete(){
        try {
            $user = $this->authService->getCurrentUserFromTokenPayload();
            if ($user->role !== 'admin') {
                $this->respondWithError(403, "Onbevoegd: Alleen stafleden kunnen taken voltooien.");
            }

            $request = $this->requestObjectFromPostedJson(TaskCompleteRequest::class);

            if (!isset($request->company_id) || !isset($request->task_id) || !isset($request->success)) {
                $this->respondWithError(400, "Ontbrekende verplichte velden.");
            }

            $response = $this->taskService->completeTask($request);
            $this->respond($response);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function import() {
        try {
            $user = $this->authService->getCurrentUserFromTokenPayload();
            if ($user->role !== 'admin') throw new Exception("Onbevoegd.", 403);

            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!isset($data['categories']) || !is_array($data['categories'])) {
                $this->respondWithError(400, "Ongeldig dataformaat. Categorieën ontbreken.");
                return;
            }

            $this->taskService->importTasks($data['categories']);
            $this->respond(["message" => "Opdrachten succesvol geïmporteerd!"]);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function deleteAll() {
        try {
            $user = $this->authService->getCurrentUserFromTokenPayload();
            if ($user->role !== 'admin') throw new Exception("Onbevoegd.", 403);

            $this->taskService->deleteAllTasks();
            $this->respond(["message" => "Alle opdrachten zijn gewist."]);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }
}
