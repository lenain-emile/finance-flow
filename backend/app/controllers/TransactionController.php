<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Services\TransactionService;
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
            
            // Convertir la réponse en DTO
            $transactionResponse = TransactionResponse::fromArray($transaction);
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
            
            // Paramètres de pagination optionnels
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            // Paramètres de recherche optionnels
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $transactions = $this->transactionService->searchTransactions($userId, $_GET['search']);
                $message = 'Résultats de recherche récupérés avec succès';
            } elseif (isset($_GET['start_date']) && isset($_GET['end_date'])) {
                $transactions = $this->transactionService->getTransactionsByDateRange(
                    $userId, 
                    $_GET['start_date'], 
                    $_GET['end_date']
                );
                $message = 'Transactions par période récupérées avec succès';
            } else {
                $transactions = $this->transactionService->getUserTransactions($userId, $limit, $offset);
                $message = 'Transactions récupérées avec succès';
            }

            Response::success([
                'transactions' => $transactions,
                'total' => count($transactions)
            ], $message);
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
            Response::success($transaction, 'Transaction récupérée avec succès');
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
            
            // Convertir la réponse en DTO
            $transactionResponse = TransactionResponse::fromArray($transaction);
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
            Response::success($transactions, 'Transactions récentes récupérées avec succès');
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