<?php

namespace FinanceFlow\Services;

use FinanceFlow\Repositories\BudgetRepository;
use FinanceFlow\Models\Budget;
use FinanceFlow\DTOs\Budget\{CreateBudgetRequest, UpdateBudgetRequest, BudgetResponse};
use Exception;

/**
 * Service pour la gestion des budgets
 * Gère la logique métier liée aux budgets et leur relation avec les transactions
 */
class BudgetService
{
    private BudgetRepository $budgetRepository;

    public function __construct()
    {
        $this->budgetRepository = new BudgetRepository();
    }

    /**
     * Créer un nouveau budget
     */
    public function createBudget(CreateBudgetRequest $request, int $userId): Budget
    {
        try {
            // Validation du DTO
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Vérifier qu'un budget n'existe pas déjà pour cette catégorie
            if ($this->budgetRepository->existsForCategory($userId, $request->category_id)) {
                throw new Exception('Un budget existe déjà pour cette catégorie', 409);
            }

            // Préparer les données
            $budgetData = [
                'max_amount' => $request->max_amount,
                'category_id' => $request->category_id,
                'user_id' => $userId,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date
            ];

            $budgetId = $this->budgetRepository->create($budgetData);

            if (!$budgetId) {
                throw new Exception('Erreur lors de la création du budget', 500);
            }

            // Récupérer le budget créé
            $budget = $this->budgetRepository->findByIdAndUserId($budgetId, $userId);
            if (!$budget) {
                throw new Exception('Budget créé mais impossible de le récupérer', 500);
            }

            return $budget;

        } catch (Exception $e) {
            error_log("Erreur création budget: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer un budget par ID
     */
    public function getBudget(int $id, int $userId): Budget
    {
        try {
            $budget = $this->budgetRepository->findByIdAndUserId($id, $userId);
            if (!$budget) {
                throw new Exception('Budget non trouvé', 404);
            }
            return $budget;
        } catch (Exception $e) {
            error_log("Erreur récupération budget: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer tous les budgets d'un utilisateur
     */
    public function getUserBudgets(int $userId): array
    {
        return $this->budgetRepository->findByUserId($userId);
    }

    /**
     * Mettre à jour un budget
     */
    public function updateBudget(int $id, UpdateBudgetRequest $request, int $userId): Budget
    {
        try {
            // Vérifier que le budget existe et appartient à l'utilisateur
            $existingBudget = $this->budgetRepository->findByIdAndUserId($id, $userId);
            if (!$existingBudget) {
                throw new Exception('Budget non trouvé', 404);
            }

            // Validation du DTO
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Vérifier qu'il y a des données à mettre à jour
            if (!$request->hasUpdates()) {
                throw new Exception('Aucune donnée à mettre à jour', 400);
            }

            // Si on change la catégorie, vérifier qu'un budget n'existe pas déjà
            if ($request->category_id !== null) {
                if ($this->budgetRepository->existsForCategory($userId, $request->category_id, $id)) {
                    throw new Exception('Un budget existe déjà pour cette catégorie', 409);
                }
            }

            // Convertir le DTO en array pour le repository
            $updateData = $request->toArray();
            $updated = $this->budgetRepository->update($id, $updateData);
            
            if (!$updated) {
                throw new Exception('Erreur lors de la mise à jour du budget', 500);
            }

            // Récupérer le budget mis à jour
            return $this->budgetRepository->findByIdAndUserId($id, $userId);

        } catch (Exception $e) {
            error_log("Erreur mise à jour budget: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer un budget
     */
    public function deleteBudget(int $id, int $userId): bool
    {
        try {
            // Vérifier que le budget existe et appartient à l'utilisateur
            $existingBudget = $this->budgetRepository->findByIdAndUserId($id, $userId);
            if (!$existingBudget) {
                throw new Exception('Budget non trouvé', 404);
            }

            return $this->budgetRepository->delete($id);

        } catch (Exception $e) {
            error_log("Erreur suppression budget: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer un budget avec son utilisation
     * 
     * @param int $id ID du budget
     * @param int $userId ID de l'utilisateur
     * @param string|null $startDate Date de début (null = mois en cours)
     * @param string|null $endDate Date de fin (null = mois en cours)
     * @return array Budget avec informations d'utilisation
     */
    public function getBudgetWithUsage(
        int $id, 
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            // Par défaut, utiliser le mois en cours
            if ($startDate === null || $endDate === null) {
                $currentMonth = $this->getCurrentMonthDates();
                $startDate = $currentMonth['start'];
                $endDate = $currentMonth['end'];
            }

            $budgetData = $this->budgetRepository->getBudgetWithUsage($id, $userId, $startDate, $endDate);
            
            if (!$budgetData) {
                throw new Exception('Budget non trouvé', 404);
            }

            return $budgetData;

        } catch (Exception $e) {
            error_log("Erreur getBudgetWithUsage: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer tous les budgets avec leur utilisation
     * 
     * @param int $userId ID de l'utilisateur
     * @param string|null $startDate Date de début (null = mois en cours)
     * @param string|null $endDate Date de fin (null = mois en cours)
     * @return array Liste des budgets avec utilisation
     */
    public function getAllBudgetsWithUsage(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            // Par défaut, utiliser le mois en cours
            if ($startDate === null || $endDate === null) {
                $currentMonth = $this->getCurrentMonthDates();
                $startDate = $currentMonth['start'];
                $endDate = $currentMonth['end'];
            }

            return $this->budgetRepository->getAllBudgetsWithUsage($userId, $startDate, $endDate);

        } catch (Exception $e) {
            error_log("Erreur getAllBudgetsWithUsage: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les budgets dépassés
     */
    public function getExceededBudgets(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $allBudgets = $this->getAllBudgetsWithUsage($userId, $startDate, $endDate);
            
            return array_filter($allBudgets, function($budget) {
                return isset($budget['status']) && $budget['status'] === 'exceeded';
            });

        } catch (Exception $e) {
            error_log("Erreur getExceededBudgets: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les budgets en alerte (>= 80%)
     */
    public function getWarningBudgets(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $allBudgets = $this->getAllBudgetsWithUsage($userId, $startDate, $endDate);
            
            return array_filter($allBudgets, function($budget) {
                return isset($budget['status']) && 
                       ($budget['status'] === 'warning' || $budget['status'] === 'exceeded');
            });

        } catch (Exception $e) {
            error_log("Erreur getWarningBudgets: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Vérifier si une transaction va dépasser un budget
     * 
     * @param int $userId ID de l'utilisateur
     * @param int|null $categoryId ID de la catégorie de la transaction
     * @param float $amount Montant de la transaction
     * @return array ['will_exceed' => bool, 'budget' => array|null, 'message' => string]
     */
    public function checkBudgetImpact(
        int $userId,
        ?int $categoryId,
        float $amount
    ): array {
        try {
            // Si pas de catégorie, pas de vérification de budget
            if ($categoryId === null) {
                return [
                    'will_exceed' => false,
                    'budget' => null,
                    'message' => 'Aucune catégorie associée'
                ];
            }

            // Chercher un budget pour cette catégorie
            $budget = $this->budgetRepository->findByUserIdAndCategoryId($userId, $categoryId);
            
            if (!$budget) {
                return [
                    'will_exceed' => false,
                    'budget' => null,
                    'message' => 'Aucun budget défini pour cette catégorie'
                ];
            }

            // Récupérer les informations d'utilisation
            $budgetWithUsage = $this->budgetRepository->getBudgetWithUsage(
                $budget->getId(),
                $userId,
                date('Y-m-01'),
                date('Y-m-t')
            );

            $currentSpent = $budgetWithUsage['spent_amount'];
            $maxAmount = $budgetWithUsage['max_amount'];
            $newTotal = $currentSpent + $amount;
            $newPercentage = ($newTotal / $maxAmount) * 100;

            // Déterminer si le budget sera dépassé
            $willExceed = $newTotal > $maxAmount;
            $willBeInWarning = $newPercentage >= 80 && !$willExceed;

            $message = '';
            if ($willExceed) {
                $excess = $newTotal - $maxAmount;
                $message = sprintf(
                    'Cette transaction dépassera le budget de %.2f€ (%.1f%% du budget)',
                    $excess,
                    $newPercentage
                );
            } elseif ($willBeInWarning) {
                $message = sprintf(
                    'Attention : cette transaction portera l\'utilisation du budget à %.1f%%',
                    $newPercentage
                );
            } else {
                $message = sprintf(
                    'Budget OK : %.1f%% d\'utilisation après cette transaction',
                    $newPercentage
                );
            }

            return [
                'will_exceed' => $willExceed,
                'will_be_in_warning' => $willBeInWarning,
                'budget' => $budgetWithUsage,
                'current_spent' => $currentSpent,
                'new_total' => $newTotal,
                'new_percentage' => round($newPercentage, 2),
                'message' => $message
            ];

        } catch (Exception $e) {
            error_log("Erreur checkBudgetImpact: " . $e->getMessage());
            return [
                'will_exceed' => false,
                'budget' => null,
                'message' => 'Erreur lors de la vérification du budget'
            ];
        }
    }

    /**
     * Obtenir les statistiques globales des budgets
     */
    public function getBudgetStatistics(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            $budgets = $this->getAllBudgetsWithUsage($userId, $startDate, $endDate);
            
            $totalBudget = 0;
            $totalSpent = 0;
            $exceededCount = 0;
            $warningCount = 0;
            $okCount = 0;

            foreach ($budgets as $budget) {
                $totalBudget += $budget['max_amount'];
                $totalSpent += $budget['spent_amount'];
                
                switch ($budget['status']) {
                    case 'exceeded':
                        $exceededCount++;
                        break;
                    case 'warning':
                        $warningCount++;
                        break;
                    case 'ok':
                        $okCount++;
                        break;
                }
            }

            $totalPercentage = $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0;

            return [
                'total_budgets' => count($budgets),
                'total_budget_amount' => $totalBudget,
                'total_spent' => $totalSpent,
                'total_remaining' => $totalBudget - $totalSpent,
                'total_percentage' => round($totalPercentage, 2),
                'exceeded_count' => $exceededCount,
                'warning_count' => $warningCount,
                'ok_count' => $okCount,
                'period' => [
                    'start_date' => $startDate ?? date('Y-m-01'),
                    'end_date' => $endDate ?? date('Y-m-t')
                ]
            ];

        } catch (Exception $e) {
            error_log("Erreur getBudgetStatistics: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtenir les dates de début et fin du mois en cours
     * Helper pour le mode "toujours mensuel par défaut"
     * 
     * @return array ['start' => 'Y-m-01', 'end' => 'Y-m-t']
     */
    private function getCurrentMonthDates(): array
    {
        return [
            'start' => date('Y-m-01'),
            'end' => date('Y-m-t')
        ];
    }
}
