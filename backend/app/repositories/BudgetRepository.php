<?php

namespace FinanceFlow\Repositories;

use FinanceFlow\Core\Repository;
use FinanceFlow\Models\Budget;
use Exception;
use PDO;

/**
 * Repository pour les budgets
 * Hérite des méthodes communes de Repository et ajoute les spécificités budgets
 */
class BudgetRepository extends Repository
{
    /**
     * Nom de la table budget
     */
    protected function getTableName(): string
    {
        return 'budget';
    }
    
    /**
     * Récupérer un budget par ID et user_id (sécurité)
     * Utilise findOneBy() du parent Repository
     */
    public function findByIdAndUserId(int $id, int $userId): ?Budget
    {
        $data = $this->findOneBy(['id' => $id, 'user_id' => $userId]);
        return $data ? Budget::fromArray($data) : null;
    }
    
    /**
     * Récupérer tous les budgets d'un utilisateur
     * Utilise findAllBy() du parent Repository
     */
    public function findByUserId(int $userId): array
    {
        try {
            $results = $this->findAllBy(['user_id' => $userId], ['id' => 'DESC']);
            
            // Convertir les tableaux en objets Budget
            return array_map(fn($data) => Budget::fromArray($data), $results);
            
        } catch (Exception $e) {
            error_log("Erreur findByUserId: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les budgets avec les noms de catégories (LEFT JOIN)
     * Méthode spécifique car nécessite un JOIN
     */
    public function findByUserIdWithCategoryNames(int $userId): array
    {
        try {
            $sql = "SELECT b.*, c.name as category_name 
                    FROM {$this->table} b
                    LEFT JOIN category c ON b.category_id = c.id
                    WHERE b.user_id = :user_id
                    ORDER BY b.id DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur findByUserIdWithCategoryNames: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer un budget par catégorie et utilisateur
     * Utilise findOneBy() du parent sauf pour category_id IS NULL
     */
    public function findByUserIdAndCategoryId(int $userId, ?int $categoryId): ?Budget
    {
        try {
            if ($categoryId === null) {
                // findOneBy ne gère pas IS NULL, donc SQL manuel pour ce cas
                $sql = "SELECT * FROM {$this->table} 
                        WHERE user_id = :user_id AND category_id IS NULL
                        LIMIT 1";
                
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['user_id' => $userId]);
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result ? Budget::fromArray($result) : null;
            } else {
                // Utilise findOneBy du parent
                $data = $this->findOneBy(['user_id' => $userId, 'category_id' => $categoryId]);
                return $data ? Budget::fromArray($data) : null;
            }
            
        } catch (Exception $e) {
            error_log("Erreur findByUserIdAndCategoryId: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer un budget avec son utilisation (dépenses)
     * 
     * @param int $budgetId ID du budget
     * @param int $userId ID de l'utilisateur (sécurité)
     * @param string|null $startDate Date de début (pour filtrer les transactions)
     * @param string|null $endDate Date de fin (pour filtrer les transactions)
     * @return array|null
     */
    public function getBudgetWithUsage(
        int $budgetId, 
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): ?array {
        try {
            // Récupérer le budget avec le nom de la catégorie
            $sql = "SELECT b.*, c.name as category_name 
                    FROM {$this->table} b
                    LEFT JOIN category c ON b.category_id = c.id
                    WHERE b.id = :budget_id AND b.user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'budget_id' => $budgetId,
                'user_id' => $userId
            ]);
            
            $budgetData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$budgetData) {
                return null;
            }
            
            // Calculer les dépenses pour cette catégorie
            $spentAmount = $this->calculateSpentAmount(
                $userId,
                $budgetData['category_id'],
                $startDate,
                $endDate
            );
            
            // Ajouter les informations d'utilisation
            $budgetData['spent_amount'] = $spentAmount;
            $budgetData['remaining_amount'] = $budgetData['max_amount'] - $spentAmount;
            $budgetData['usage_percentage'] = $budgetData['max_amount'] > 0 
                ? ($spentAmount / $budgetData['max_amount']) * 100 
                : 0;
            
            // Déterminer le statut
            if ($budgetData['usage_percentage'] >= 100) {
                $budgetData['status'] = 'exceeded';
            } elseif ($budgetData['usage_percentage'] >= 80) {
                $budgetData['status'] = 'warning';
            } else {
                $budgetData['status'] = 'ok';
            }
            
            return $budgetData;
            
        } catch (Exception $e) {
            error_log("Erreur getBudgetWithUsage: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer tous les budgets d'un utilisateur avec leur utilisation
     */
    public function getAllBudgetsWithUsage(
        int $userId,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        try {
            // Utilise findByUserIdWithCategoryNames pour récupérer les budgets avec JOIN
            $budgets = $this->findByUserIdWithCategoryNames($userId);
            
            // Ajouter les informations d'utilisation pour chaque budget
            foreach ($budgets as &$budget) {
                $spentAmount = $this->calculateSpentAmount(
                    $userId,
                    $budget['category_id'],
                    $startDate,
                    $endDate
                );
                
                $budget['spent_amount'] = $spentAmount;
                $budget['remaining_amount'] = $budget['max_amount'] - $spentAmount;
                $budget['usage_percentage'] = $budget['max_amount'] > 0 
                    ? ($spentAmount / $budget['max_amount']) * 100 
                    : 0;
                
                // Déterminer le statut
                if ($budget['usage_percentage'] >= 100) {
                    $budget['status'] = 'exceeded';
                } elseif ($budget['usage_percentage'] >= 80) {
                    $budget['status'] = 'warning';
                } else {
                    $budget['status'] = 'ok';
                }
            }
            
            return $budgets;
            
        } catch (Exception $e) {
            error_log("Erreur getAllBudgetsWithUsage: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtenir les dates du mois en cours (ou d'une date de référence)
     * 
     * @param string|null $referenceDate Date de référence (format Y-m-d), null = aujourd'hui
     * @return array ['start' => 'Y-m-d', 'end' => 'Y-m-d']
     */
    private function getCurrentMonthDates(?string $referenceDate = null): array
    {
        $date = $referenceDate ? new \DateTime($referenceDate) : new \DateTime();
        
        return [
            'start' => $date->format('Y-m-01'), // Premier jour du mois
            'end' => $date->format('Y-m-t')     // Dernier jour du mois
        ];
    }
    
    /**
     * Calculer le montant dépensé pour une catégorie
     * 
     * @param int $userId ID de l'utilisateur
     * @param int|null $categoryId ID de la catégorie (null = toutes catégories)
     * @param string|null $startDate Date de début
     * @param string|null $endDate Date de fin
     * @return float
     */
    private function calculateSpentAmount(
        int $userId,
        ?int $categoryId,
        ?string $startDate = null,
        ?string $endDate = null
    ): float {
        try {
            $sql = "SELECT COALESCE(SUM(amount), 0) as total 
                    FROM transaction 
                    WHERE user_id = :user_id";
            
            $params = ['user_id' => $userId];
            
            if ($categoryId !== null) {
                $sql .= " AND category_id = :category_id";
                $params['category_id'] = $categoryId;
            }
            
            if ($startDate) {
                $sql .= " AND date >= :start_date";
                $params['start_date'] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND date <= :end_date";
                $params['end_date'] = $endDate;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float) ($result['total'] ?? 0);
            
        } catch (Exception $e) {
            error_log("Erreur calculateSpentAmount: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Vérifier si un budget existe déjà pour une catégorie
     */
    public function existsForCategory(int $userId, ?int $categoryId, ?int $excludeBudgetId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                    WHERE user_id = :user_id";
            
            $params = ['user_id' => $userId];
            
            if ($categoryId === null) {
                $sql .= " AND category_id IS NULL";
            } else {
                $sql .= " AND category_id = :category_id";
                $params['category_id'] = $categoryId;
            }
            
            if ($excludeBudgetId !== null) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeBudgetId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int) $result['count']) > 0;
            
        } catch (Exception $e) {
            error_log("Erreur existsForCategory: " . $e->getMessage());
            return false;
        }
    }
}
