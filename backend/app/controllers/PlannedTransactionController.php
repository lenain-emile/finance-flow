<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Services\PlannedTransactionService;
use FinanceFlow\Models\PlannedTransaction;
use FinanceFlow\Middleware\AuthMiddleware;
use FinanceFlow\Core\Response;
use FinanceFlow\DTOs\PlannedTransaction\{CreatePlannedTransactionRequest, UpdatePlannedTransactionRequest, PlannedTransactionResponse};

/**
 * Controller pour la gestion des transactions récurrentes
 * Suit le même pattern que TransactionController
 */
class PlannedTransactionController
{
    private PlannedTransactionService $plannedTransactionService;
    private AuthMiddleware $authMiddleware;

    public function __construct()
    {
        $this->plannedTransactionService = new PlannedTransactionService();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * POST /api/planned-transactions
     * Créer une nouvelle transaction planifiée
     */
    public function create(): void
    {
        try {
            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            if (!$this->authMiddleware->authenticate()) {
                return;
            }

            if (!AuthMiddleware::rateLimit(20, 1)) {
                return;
            }

            $createRequest = CreatePlannedTransactionRequest::fromArray($data);
            
            $validation = $createRequest->isValid();
            if (!$validation['valid']) {
                Response::validationError($validation['errors']);
                return;
            }

            $userId = AuthMiddleware::getCurrentUserId();
            $plannedTransaction = $this->plannedTransactionService->create($createRequest, $userId);
            
            $response = PlannedTransactionResponse::fromArray($plannedTransaction->toArray());
            Response::success($response->toArray(), 'Transaction planifiée créée avec succès', 201);

        } catch (\Exception $e) {
            error_log("Erreur création planned_transaction: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions
     * Récupérer toutes les transactions planifiées de l'utilisateur
     */
    public function index(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $activeOnly = isset($_GET['active']) && $_GET['active'] === 'true';
            
            $plannedTransactions = $this->plannedTransactionService->getAll($userId, $activeOnly);
            
            $response = array_map(
                fn(PlannedTransaction $pt) => PlannedTransactionResponse::fromArray($pt->toArray())->toArray(),
                $plannedTransactions
            );
            
            Response::success([
                'planned_transactions' => $response,
                'total' => count($response)
            ], 'Transactions planifiées récupérées avec succès');

        } catch (\Exception $e) {
            error_log("Erreur récupération planned_transactions: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions/{id}
     * Récupérer une transaction planifiée spécifique
     */
    public function show(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $plannedTransaction = $this->plannedTransactionService->getById($id, $userId);
            
            $response = PlannedTransactionResponse::fromArray($plannedTransaction->toArray());
            Response::success($response->toArray(), 'Transaction planifiée récupérée avec succès');

        } catch (\Exception $e) {
            error_log("Erreur récupération planned_transaction: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * PUT /api/planned-transactions/{id}
     * Mettre à jour une transaction planifiée
     */
    public function update(int $id): void
    {
        try {
            if (!$this->authMiddleware->authenticate()) {
                return;
            }

            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            $updateRequest = UpdatePlannedTransactionRequest::fromArray($data);
            
            $validation = $updateRequest->isValid();
            if (!$validation['valid']) {
                Response::validationError($validation['errors']);
                return;
            }

            if (!$updateRequest->hasUpdates()) {
                Response::error('Aucune donnée à mettre à jour', 400);
                return;
            }

            $userId = AuthMiddleware::getCurrentUserId();
            $plannedTransaction = $this->plannedTransactionService->update($id, $updateRequest, $userId);
            
            $response = PlannedTransactionResponse::fromArray($plannedTransaction->toArray());
            Response::success($response->toArray(), 'Transaction planifiée mise à jour avec succès');

        } catch (\Exception $e) {
            error_log("Erreur mise à jour planned_transaction: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * DELETE /api/planned-transactions/{id}
     * Supprimer une transaction planifiée
     */
    public function delete(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $deleted = $this->plannedTransactionService->delete($id, $userId);
            
            if ($deleted) {
                Response::success(null, 'Transaction planifiée supprimée avec succès');
            } else {
                Response::error('Erreur lors de la suppression', 500);
            }

        } catch (\Exception $e) {
            error_log("Erreur suppression planned_transaction: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/planned-transactions/{id}/toggle
     * Activer/Désactiver une transaction planifiée
     */
    public function toggle(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $plannedTransaction = $this->plannedTransactionService->toggleActive($id, $userId);
            
            $response = PlannedTransactionResponse::fromArray($plannedTransaction->toArray());
            $status = $plannedTransaction->isActive() ? 'activée' : 'désactivée';
            Response::success($response->toArray(), "Transaction planifiée {$status} avec succès");

        } catch (\Exception $e) {
            error_log("Erreur toggle planned_transaction: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/planned-transactions/{id}/execute
     * Exécuter une transaction planifiée (créer la transaction réelle)
     */
    public function execute(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $result = $this->plannedTransactionService->executeTransaction($id, $userId);
            
            Response::success($result, 'Transaction exécutée avec succès');

        } catch (\Exception $e) {
            error_log("Erreur exécution planned_transaction: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * POST /api/planned-transactions/execute-all
     * Exécuter toutes les transactions dues
     */
    public function executeAll(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $results = $this->plannedTransactionService->executeDueTransactions($userId);
            
            Response::success($results, 'Exécution des transactions planifiées terminée');

        } catch (\Exception $e) {
            error_log("Erreur executeAll planned_transactions: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions/due
     * Récupérer les transactions à exécuter (date dépassée)
     */
    public function due(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $dueTransactions = $this->plannedTransactionService->getDueTransactions($userId);
            
            $response = array_map(
                fn(PlannedTransaction $pt) => PlannedTransactionResponse::fromArray($pt->toArray())->toArray(),
                $dueTransactions
            );
            
            Response::success([
                'due_transactions' => $response,
                'total' => count($response)
            ], 'Transactions dues récupérées avec succès');

        } catch (\Exception $e) {
            error_log("Erreur récupération due planned_transactions: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions/upcoming
     * Récupérer les prochaines transactions planifiées
     */
    public function upcoming(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $days = isset($_GET['days']) ? (int) $_GET['days'] : 30;
            
            $upcomingTransactions = $this->plannedTransactionService->getUpcoming($userId, $days);
            
            $response = array_map(
                fn(PlannedTransaction $pt) => PlannedTransactionResponse::fromArray($pt->toArray())->toArray(),
                $upcomingTransactions
            );
            
            Response::success([
                'upcoming_transactions' => $response,
                'total' => count($response),
                'days' => $days
            ], 'Prochaines transactions planifiées récupérées avec succès');

        } catch (\Exception $e) {
            error_log("Erreur récupération upcoming planned_transactions: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions/incomes
     * Récupérer uniquement les revenus récurrents
     */
    public function incomes(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $incomes = $this->plannedTransactionService->getByType($userId, 'income');
            
            $response = array_map(
                fn(PlannedTransaction $pt) => PlannedTransactionResponse::fromArray($pt->toArray())->toArray(),
                $incomes
            );
            
            Response::success([
                'incomes' => $response,
                'total' => count($response)
            ], 'Revenus récurrents récupérés avec succès');

        } catch (\Exception $e) {
            error_log("Erreur récupération incomes: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions/expenses
     * Récupérer uniquement les dépenses récurrentes
     */
    public function expenses(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $expenses = $this->plannedTransactionService->getByType($userId, 'expense');
            
            $response = array_map(
                fn(PlannedTransaction $pt) => PlannedTransactionResponse::fromArray($pt->toArray())->toArray(),
                $expenses
            );
            
            Response::success([
                'expenses' => $response,
                'total' => count($response)
            ], 'Dépenses récurrentes récupérées avec succès');

        } catch (\Exception $e) {
            error_log("Erreur récupération expenses: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions/projection
     * Calculer la projection mensuelle
     */
    public function projection(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $projection = $this->plannedTransactionService->getMonthlyProjection($userId);
            
            Response::success($projection, 'Projection mensuelle calculée avec succès');

        } catch (\Exception $e) {
            error_log("Erreur projection: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * GET /api/planned-transactions/stats
     * Récupérer les statistiques
     */
    public function stats(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $stats = $this->plannedTransactionService->getStats($userId);
            
            Response::success($stats, 'Statistiques récupérées avec succès');

        } catch (\Exception $e) {
            error_log("Erreur statistiques planned_transactions: " . $e->getMessage());
            Response::error($e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Méthode pour gérer les routes non trouvées
     */
    public function notFound(): void
    {
        Response::error('Endpoint de transaction planifiée non trouvé', 404);
    }
}
