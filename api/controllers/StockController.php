<?php

namespace Controllers;

use Exception;
use Models\DTO\StockTradeRequest;
use Services\AuthService;
use Services\StockService;

class StockController extends Controller
{
    private StockService  $stockService;
    private AuthService $authService;

    public function __construct()
    {
        $this->stockService = new StockService();
        $this->authService = new AuthService();
    }

    public function getBankStocks(){
        try{
            $this->respond($this->stockService->getBankStocks());
        } catch (Exception $e){
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function getCompanyStocks(int $companyId){
        try {
            if (!$companyId) {
                $this->respondWithError(400, "Company ID is required");
            }
            $this->respond($this->stockService->getCompanyStocks($companyId));
        } catch (Exception $e){
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function getAllStocks(){
        try {
            $this->respond($this->stockService->getAllStocks());
        } catch (Exception $e){
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }

    public function trade(){
        try {
            $user = $this->authService->getCurrentUserFromTokenPayload();
            $request = $this->requestObjectFromPostedJson(StockTradeRequest::class);

            if ($user->role !== 'admin' && $user->company_id !== $request->buyer_id) {
                $this->respondWithError(403, "Unauthorized: Je kunt alleen aandelen kopen voor je eigen patrouille.");
                return;
            }

            if (!isset($request->buyer_id, $request->stock_company_id, $request->amount)) {
                $this->respondWithError(400, "Missing fields");
            }

            $this->stockService->tradeStock($request);
            $this->respond(["message" => "Trade executed successfully"]);
        } catch (Exception $e){
            $this->respondWithError($e->getCode() ?: 500, $e->getMessage());
        }
    }
}
