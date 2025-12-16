<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Services\TransactionService;
use FinanceFlow\Models\Transaction;
use FinanceFlow\Middleware\AuthMiddleware;
use FinanceFlow\Core\Response;
use FinanceFlow\DTOs\Transaction\{CreateTransactionRequest, UpdateTransactionRequest, TransactionResponse};

/**
 * Controller pour la gestion des transactions
 * Suit le même pattern que UserController
 */
class TransactionController
{
    private TransactionService $transactionService;
    private AuthMiddleware $authMiddleware;

    public function __construct()
    {
        $this->transactionService = new TransactionService();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * POST /api/transactions
     * Créer une nouvelle transaction
     */
    public function create(): void
    {
        try {
            // Validation du JSON avec middleware (comme UserController)
            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            // Authentification avec middleware
            if (!$this->authMiddleware->authenticate()) {
                return;
            }

            // Rate limiting pour création transaction
            if (!AuthMiddleware::rateLimit(20, 1)) { // 20 transactions par minute max
                return;
            }

            // Créer le DTO depuis les données reçues (comme UserController)
            $createRequest = CreateTransactionRequest::fromArray($data);
            
            // Validation du DTO
            $validation = $createRequest->isValid();
            if (!$validation['valid']) {
                Response::validationError($validation['errors']);
                return;
            }

            $userId = AuthMiddleware::getCurrentUserId();
            $transaction = $this->transactionService->createTransaction($createRequest, $userId);
            
            // Convertir l'objet Transaction en tableau puis en DTO
            $transactionResponse = TransactionResponse::fromArray($transaction->toArray());
            Response::success($transactionResponse->toArray(), 'Transaction créée avec succès', 201);
        } catch (\Exception $e) {
            error_log("Erreur création transaction: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/transactions
     * Récupérer toutes les transactions de l'utilisateur
     */
    public function index(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            
            // Récupérer les paramètres de filtrage depuis la query string
            $filters = [];
            
            if (isset($_GET['category_id']) && is_numeric($_GET['category_id'])) {
                $filters['category_id'] = (int)$_GET['category_id'];
            }
            
            if (isset($_GET['sub_category_id']) && is_numeric($_GET['sub_category_id'])) {
                $filters['sub_category_id'] = (int)$_GET['sub_category_id'];
            }
            
            if (isset($_GET['account_id']) && is_numeric($_GET['account_id'])) {
                $filters['account_id'] = (int)$_GET['account_id'];
            }
            
            if (isset($_GET['min_amount']) && is_numeric($_GET['min_amount'])) {
                $filters['min_amount'] = (float)$_GET['min_amount'];
            }
            
            if (isset($_GET['max_amount']) && is_numeric($_GET['max_amount'])) {
                $filters['max_amount'] = (float)$_GET['max_amount'];
            }
            
            if (isset($_GET['start_date']) && !empty($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            
            if (isset($_GET['end_date']) && !empty($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            // Paramètres de tri
            $sortBy = $_GET['sort_by'] ?? 'date';
            $sortOrder = $_GET['sort_order'] ?? 'DESC';
            
            // Paramètres de pagination
            $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            // Si des filtres ou tri personnalisés sont appliqués, utiliser la nouvelle méthode
            if (!empty($filters) || $sortBy !== 'date' || $sortOrder !== 'DESC') {
                $result = $this->transactionService->getFilteredAndSortedTransactions(
                    $userId,
                    $filters,
                    $sortBy,
                    $sortOrder,
                    $limit,
                    $offset
                );
                
                Response::success([
                    'transactions' => $result['transactions'],
                    'total' => $result['total'],
                    'filters' => $result['filters_applied'],
                    'sort' => $result['sort'],
                    'pagination' => $result['pagination']
                ], 'Transactions filtrées récupérées avec succès');
            } else {
                // Comportement par défaut sans filtres avancés
                // getUserTransactions retourne déjà des tableaux (pas des objets Transaction)
                $transactions = $this->transactionService->getUserTransactions($userId, $limit, $offset);
                
                Response::success([
                    'transactions' => $transactions,
                    'total' => count($transactions)
                ], 'Transactions récupérées avec succès');
            }
        } catch (\Exception $e) {
            error_log("Erreur récupération transactions: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/transactions/{id}
     * Récupérer une transaction spécifique
     */
    public function show(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $transaction = $this->transactionService->getTransaction($id, $userId);
            Response::success($transaction->toArray(), 'Transaction récupérée avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération transaction: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * PUT /api/transactions/{id}
     * Mettre à jour une transaction
     */
    public function update(int $id): void
    {
        try {
            // Authentification avec middleware
            if (!$this->authMiddleware->authenticate()) {
                return;
            }

            // Validation du JSON
            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            // Créer le DTO de mise à jour (comme UserController)
            $updateRequest = UpdateTransactionRequest::fromArray($data);
            
            // Validation du DTO
            $validation = $updateRequest->isValid();
            if (!$validation['valid']) {
                Response::validationError($validation['errors']);
                return;
            }

            // Vérifier qu'il y a des données à mettre à jour
            if (!$updateRequest->hasUpdates()) {
                Response::error('Aucune donnée à mettre à jour', 400);
                return;
            }

            $userId = AuthMiddleware::getCurrentUserId();
            $transaction = $this->transactionService->updateTransaction($id, $updateRequest, $userId);
            
            // Convertir l'objet Transaction en tableau puis en DTO
            $transactionResponse = TransactionResponse::fromArray($transaction->toArray());
            Response::success($transactionResponse->toArray(), 'Transaction mise à jour avec succès');
        } catch (\Exception $e) {
            error_log("Erreur mise à jour transaction: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * DELETE /api/transactions/{id}
     * Supprimer une transaction
     */
    public function delete(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $deleted = $this->transactionService->deleteTransaction($id, $userId);
            
            if ($deleted) {
                Response::success(null, 'Transaction supprimée avec succès');
            } else {
                Response::error('Erreur lors de la suppression', 500);
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression transaction: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/transactions/recent
     * Récupérer les dernières transactions
     */
    public function recent(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            
            $transactions = $this->transactionService->getRecentTransactions($userId, $limit);
            
            // Convertir les objets Transaction en tableaux
            $transactionsArray = array_map(fn(Transaction $t) => $t->toArray(), $transactions);
            
            Response::success($transactionsArray, 'Transactions récentes récupérées avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération transactions récentes: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/transactions/stats
     * Récupérer les statistiques des transactions
     */
    public function stats(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $stats = $this->transactionService->getTransactionStats($userId);
            Response::success($stats, 'Statistiques récupérées avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération statistiques: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/transactions/total
     * Calculer le total des transactions
     */
    public function total(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $total = $this->transactionService->getTotalAmount($userId, $startDate, $endDate);
            
            Response::success([
                'total' => $total,
                'period' => $startDate && $endDate ? "du $startDate au $endDate" : 'toutes périodes'
            ], 'Total calculé avec succès');
        } catch (\Exception $e) {
            error_log("Erreur calcul total: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * Méthode pour gérer les routes non trouvées
     */
    public function notFound(): void
    {
        Response::error('Endpoint de transaction non trouvé', 404);
    }
}