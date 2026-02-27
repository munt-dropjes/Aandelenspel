<?php
namespace Services;

use Models\DTO\TransactionCreateRequest;
use Models\DTO\TransactionManyRequest;
use Models\DTO\TransactionTransferRequest;
use Models\User;
use Repositories\CompanyRepository;
use Exception;
use Repositories\TransactionRepository;

class TransactionService {
    private TransactionRepository $transactionRepo;
    private CompanyService $companyService;

    public function __construct() {
        $this->transactionRepo = new TransactionRepository();
        $this->companyService = new CompanyService();
    }

    /**
     * @throws Exception
     */
    public function processTransaction(TransactionCreateRequest $request): void {
        if ($request->amount == 0) {
            throw new Exception("Bedrag mag niet nul zijn.", 400);
        }

        if (empty($request->description)) {
            throw new Exception("Omschrijving is verplicht.", 400);
        }

        $company = $this->companyService->getCompanyModelById($request->company_id);
        if (!$company) {
            throw new Exception("Bedrijf niet gevonden.", 404);
        }

        if ($company->cash < abs($request->amount)) {
            throw new Exception("Onvoldoende saldo.", 400);
        }

        $success = $this->transactionRepo->create($request);
        if (!$success) {
            throw new Exception("Transactie kon niet worden verwerkt.", 500);
        }
    }

    /**
     * @throws Exception
     */
    public function getTransactionHistory(TransactionManyRequest $request): array {
        if (isset($request->user->role) && $request->user->role === 'admin') {
            return $this->transactionRepo->getAll($request);
        }

        if (isset($request->user->company_id) && $request->user->company_id) {
            return $this->transactionRepo->getByCompany($request);
        }

        return [];
    }

    /**
     * @throws Exception
     */
    public function transfer(TransactionTransferRequest $request, User $user): void {
        if ($request->amount <= 0) {
            throw new Exception("Bedrag moet groter zijn dan nul.", 400);
        }
        if ($request->sender_id === $request->receiver_id) {
            throw new Exception("Zender en ontvanger mogen niet hetzelfde zijn.", 400);
        }

        if ($user->role !== 'admin' && $user->company_id !== $request->sender_id) {
            throw new Exception("Onbevoegd: Je kunt alleen geld overmaken vanaf je eigen bedrijf.", 403);
        }

        $sender = $this->companyService->getCompanyModelById($request->sender_id);
        if (!$sender) throw new Exception("Verzender niet gevonden.", 404);

        $receiver = $this->companyService->getCompanyModelById($request->receiver_id);
        if (!$receiver) throw new Exception("Ontvanger niet gevonden.", 404);

        if ($sender->cash < $request->amount) {
            throw new Exception("Onvoldoende saldo. Je hebt slechts ƒ " . $sender->cash, 400);
        }

        $senderDesc = "Aan {$receiver->name}: {$request->description}";
        $receiverDesc = "Van {$sender->name}: {$request->description}";

        $this->transactionRepo->executeTransfer($request, $senderDesc, $receiverDesc);
    }
}
