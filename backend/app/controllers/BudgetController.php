<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Services\BudgetService;
use FinanceFlow\Models\Budget;
use FinanceFlow\Middleware\AuthMiddleware;
use FinanceFlow\Core\Response;
use FinanceFlow\DTOs\Budget\{CreateBudgetRequest, UpdateBudgetRequest, BudgetResponse};

/**
 * Controller pour la gestion des budgets
 * Gère les endpoints CRUD et les fonctionnalités avancées (usage, alertes, statistiques)
 */
class BudgetController
{
    private BudgetService $budgetService;
    private AuthMiddleware $authMiddleware;

    public function __construct()
    {
        $this->budgetService = new BudgetService();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * POST /api/budgets
     * Créer un nouveau budget
     */
    public function create(): void
    {
        try {
            // Validation du JSON
            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            // Authentification
            if (!$this->authMiddleware->authenticate()) {
                return;
            }

            // Rate limiting
            if (!AuthMiddleware::rateLimit(20, 1)) {
                return;
            }

            // Créer le DTO
            $createRequest = CreateBudgetRequest::fromArray($data);
            
            // Validation du DTO
            $validation = $createRequest->isValid();
            if (!$validation['valid']) {
                Response::validationError($validation['errors']);
                return;
            }

            $userId = AuthMiddleware::getCurrentUserId();
            $budget = $this->budgetService->createBudget($createRequest, $userId);
            
            // Convertir en DTO de réponse
            $budgetResponse = BudgetResponse::fromArray($budget->toArray());
            Response::success($budgetResponse->toArray(), 'Budget créé avec succès', 201);
        } catch (\Exception $e) {
            error_log("Erreur création budget: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/budgets
     * Récupérer tous les budgets de l'utilisateur
     */
    public function index(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            
            // Vérifier si on veut les détails avec usage
            $withUsage = isset($_GET['with_usage']) && $_GET['with_usage'] === 'true';
            
            if ($withUsage) {
                $startDate = $_GET['start_date'] ?? null;
                $endDate = $_GET['end_date'] ?? null;
                
                $budgets = $this->budgetService->getAllBudgetsWithUsage($userId, $startDate, $endDate);
                
                // Convertir en DTOs
                $budgetsResponse = array_map(
                    fn($budget) => BudgetResponse::fromArray($budget)->toArray(),
                    $budgets
                );
            } else {
                $budgets = $this->budgetService->getUserBudgets($userId);
                
                // Convertir les objets Budget en tableaux puis en DTOs
                $budgetsResponse = array_map(
                    fn(Budget $budget) => BudgetResponse::fromArray($budget->toArray())->toArray(),
                    $budgets
                );
            }
            
            Response::success($budgetsResponse, 'Budgets récupérés avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération budgets: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/budgets/{id}
     * Récupérer un budget spécifique
     */
    public function show(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            
            // Vérifier si on veut les détails avec usage
            $withUsage = isset($_GET['with_usage']) && $_GET['with_usage'] === 'true';
            
            if ($withUsage) {
                $startDate = $_GET['start_date'] ?? null;
                $endDate = $_GET['end_date'] ?? null;
                
                $budgetData = $this->budgetService->getBudgetWithUsage($id, $userId, $startDate, $endDate);
                $budgetResponse = BudgetResponse::fromArray($budgetData);
            } else {
                $budget = $this->budgetService->getBudget($id, $userId);
                $budgetResponse = BudgetResponse::fromArray($budget->toArray());
            }
            
            Response::success($budgetResponse->toArray(), 'Budget récupéré avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération budget: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * PUT /api/budgets/{id}
     * Mettre à jour un budget
     */
    public function update(int $id): void
    {
        try {
            // Authentification
            if (!$this->authMiddleware->authenticate()) {
                return;
            }

            // Validation du JSON
            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            // Créer le DTO de mise à jour
            $updateRequest = UpdateBudgetRequest::fromArray($data);
            
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
            $budget = $this->budgetService->updateBudget($id, $updateRequest, $userId);
            
            // Convertir en DTO de réponse
            $budgetResponse = BudgetResponse::fromArray($budget->toArray());
            Response::success($budgetResponse->toArray(), 'Budget mis à jour avec succès');
        } catch (\Exception $e) {
            error_log("Erreur mise à jour budget: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * DELETE /api/budgets/{id}
     * Supprimer un budget
     */
    public function delete(int $id): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $deleted = $this->budgetService->deleteBudget($id, $userId);
            
            if ($deleted) {
                Response::success(null, 'Budget supprimé avec succès');
            } else {
                Response::error('Erreur lors de la suppression du budget', 500);
            }
        } catch (\Exception $e) {
            error_log("Erreur suppression budget: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/budgets/alerts
     * Récupérer les budgets en alerte (dépassés ou proches)
     */
    public function alerts(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $warningBudgets = $this->budgetService->getWarningBudgets($userId, $startDate, $endDate);
            
            // Convertir en DTOs
            $budgetsResponse = array_map(
                fn($budget) => BudgetResponse::fromArray($budget)->toArray(),
                $warningBudgets
            );
            
            Response::success([
                'count' => count($budgetsResponse),
                'budgets' => $budgetsResponse
            ], 'Alertes budgets récupérées avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération alertes: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/budgets/exceeded
     * Récupérer les budgets dépassés
     */
    public function exceeded(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $exceededBudgets = $this->budgetService->getExceededBudgets($userId, $startDate, $endDate);
            
            // Convertir en DTOs
            $budgetsResponse = array_map(
                fn($budget) => BudgetResponse::fromArray($budget)->toArray(),
                $exceededBudgets
            );
            
            Response::success([
                'count' => count($budgetsResponse),
                'budgets' => $budgetsResponse
            ], 'Budgets dépassés récupérés avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération budgets dépassés: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/budgets/stats
     * Récupérer les statistiques globales des budgets
     */
    public function stats(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            $stats = $this->budgetService->getBudgetStatistics($userId, $startDate, $endDate);
            
            Response::success($stats, 'Statistiques budgets récupérées avec succès');
        } catch (\Exception $e) {
            error_log("Erreur récupération statistiques budgets: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * POST /api/budgets/check-impact
     * Vérifier l'impact d'une transaction sur un budget
     */
    public function checkImpact(): void
    {
        try {
            // Validation du JSON
            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            // Authentification
            if (!$this->authMiddleware->authenticate()) {
                return;
            }

            // Valider les paramètres requis
            if (!isset($data['amount']) || !is_numeric($data['amount'])) {
                Response::error('Le montant est requis', 400);
                return;
            }

            $userId = AuthMiddleware::getCurrentUserId();
            $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;
            $amount = (float) $data['amount'];

            $impact = $this->budgetService->checkBudgetImpact($userId, $categoryId, $amount);
            
            Response::success($impact, 'Vérification effectuée avec succès');
        } catch (\Exception $e) {
            error_log("Erreur vérification impact budget: " . $e->getMessage());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }
}
