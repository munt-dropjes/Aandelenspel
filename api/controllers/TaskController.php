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
                $this->respondWithError(403, "Unauthorized: Alleen stafleden kunnen taken voltooien.");
                return;
            }

            $request = $this->requestObjectFromPostedJson(TaskCompleteRequest::class);

            if (!isset($request->company_id) || !isset($request->task_id) || !isset($request->success)) {
                $this->respondWithError(400, "Missing required fields");
            }

            $response = $this->taskService->completeTask($request);
            $this->respond($response);
        } catch (Exception $e) {
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }
}
