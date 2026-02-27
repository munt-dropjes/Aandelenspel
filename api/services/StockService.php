<?php

namespace Services;

use Exception;
use Models\DTO\StockTradeRequest;
use Repositories\StockRepository;

class StockService
{
    private StockRepository $stockRepo;
    private CompanyService $companyService;

    public function __construct(){
        $this->stockRepo = new StockRepository();
        $this->companyService = new CompanyService();
    }

    /**
     * @throws Exception
     */
    public function getBankStocks(): array {
        $portfolio = $this->stockRepo->getPortfolio(null);
        return $this->applyRealValuation($portfolio);
    }

    /**
     * @throws Exception
     */
    public function getCompanyStocks(int $companyId): array {
        $portfolio = $this->stockRepo->getPortfolio($companyId);
        return $this->applyRealValuation($portfolio);
    }

    public function getAllStocks(): array {
        return $this->stockRepo->getAllShares();
    }

    /**
     * @throws Exception
     */
    public function tradeStock(StockTradeRequest $request): void
    {
        if ($request->amount <= 0)
            throw new Exception("Aantal moet groter zijn dan nul.", 400);
        if ($request->buyer_id === $request->seller_id)
            throw new Exception("Koper en verkoper mogen niet hetzelfde zijn.", 400);

        $stockCompany = $this->companyService->getCompanyModelById($request->stock_company_id);
        if (!$stockCompany) throw new Exception("Aandelenbedrijf niet gevonden.", 404);
        $request->stock_company_name = $stockCompany->name;

        $buyer = $this->companyService->getCompanyModelById($request->buyer_id);
        if (!$buyer) throw new Exception("Koper niet gevonden.", 404);
        $request->buyer_name = $buyer->name;

        if ($request->seller_id !== null) {
            $seller = $this->companyService->getCompanyModelById($request->seller_id);
            if (!$seller) throw new Exception("Verkoper niet gevonden.", 404);

            $sellerStockAmount = $this->stockRepo->getShareAmount($request->stock_company_id, $request->seller_id);
            if ($sellerStockAmount < $request->amount) {
                throw new Exception("Verkoper heeft onvoldoende aandelen.", 400);
            }
        } else {
            // Seller is the Bank
            $bankStockAmount = $this->stockRepo->getShareAmount($request->stock_company_id, null);
            if ($bankStockAmount < $request->amount) {
                throw new Exception("De Bank heeft onvoldoende aandelen op voorraad.", 400);
            }
        }

        // Logic check
        $totalCost = $stockCompany->stock_price * $request->amount;
        if ($buyer->cash < $totalCost) {
            throw new Exception("Koper heeft onvoldoende saldo.", 400);
        }

        $this->stockRepo->executeTrade($request, $totalCost, $seller, $buyer);
    }

    /**
     * @throws Exception
     */
    private function applyRealValuation(array $portfolio): array {
        $calculatedCompanies = $this->companyService->getAllCompanyModels();

        // Create a lookup map: [company_id => correct_price]
        $priceMap = [];
        foreach ($calculatedCompanies as $c) {
            $priceMap[$c->id] = $c->stock_price;
        }

        foreach ($portfolio as $stock) {
            if (isset($priceMap[$stock->company_id])) {
                $stock->current_price = $priceMap[$stock->company_id];
            }
        }

        return $portfolio;
    }
}
