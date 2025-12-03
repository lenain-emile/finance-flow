<?php

namespace FinanceFlow\Repositories;

use FinanceFlow\Core\Repository;
use FinanceFlow\Models\PlannedTransaction;
use PDO;
use Exception;

/**
 * Repository pour les transactions récurrentes/planifiées
 * Hérite des méthodes communes de Repository (comme TransactionRepository)
 */
class PlannedTransactionRepository extends Repository
{
    /**
     * Champs autorisés pour les mises à jour
     */
    private const ALLOWED_UPDATE_FIELDS = [
        'title', 'description', 'amount', 'operation_type', 'frequency',
        'next_date', 'interest_rate', 'duration', 'duration_unit',
        'category_id', 'sub_category_id', 'account_id', 'active'
    ];

    /**
     * Nom de la table
     */
    protected function getTableName(): string
    {
        return 'planned_transaction';
    }
    
    /**
     * Créer une nouvelle transaction planifiée
     */
    public function create(array $data): ?int
    {
        try {
            $dbData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'operation_type' => $data['operation_type'],
                'frequency' => $data['frequency'],
                'next_date' => $data['next_date'],
                'interest_rate' => $data['interest_rate'] ?? null,
                'duration' => $data['duration'] ?? null,
                'duration_unit' => $data['duration_unit'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'sub_category_id' => $data['sub_category_id'] ?? null,
                'user_id' => $data['user_id'],
                'account_id' => $data['account_id'] ?? null,
                'active' => isset($data['active']) ? (int) $data['active'] : 1
            ];
            
            return parent::create($dbData);
            
        } catch (Exception $e) {
            error_log("Erreur création planned_transaction: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Mettre à jour une transaction planifiée
     */
    public function update(int $id, array $data): bool
    {
        try {
            $dbData = array_intersect_key($data, array_flip(self::ALLOWED_UPDATE_FIELDS));
            
            if (empty($dbData)) {
                return false;
            }
            
            // Convertir active en int pour MySQL
            if (isset($dbData['active'])) {
                $dbData['active'] = (int) $dbData['active'];
            }
            
            return parent::update($id, $dbData);
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour planned_transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer une transaction planifiée par ID et user_id (sécurité)
     */
    public function findByIdAndUserId(int $id, int $userId): ?PlannedTransaction
    {
        $data = $this->findOneBy(['id' => $id, 'user_id' => $userId]);
        return $data ? PlannedTransaction::fromArray($data) : null;
    }
    
    /**
     * Récupérer les transactions planifiées avec filtres
     * Méthode centralisée pour éviter la duplication de code
     * 
     * @param array $filters Filtres disponibles:
     *   - user_id: int (requis)
     *   - active_only: bool
     *   - operation_type: string ('income'|'expense')
     *   - due_before: string (date Y-m-d)
     *   - due_after: string (date Y-m-d)
     *   - category_id: int
     * @return PlannedTransaction[]
     */
    private function getFiltered(array $filters): array
    {
        try {
            $conditions = [];
            $params = [];
            
            // User ID (obligatoire pour la sécurité)
            if (isset($filters['user_id'])) {
                $conditions[] = "user_id = :user_id";
                $params['user_id'] = $filters['user_id'];
            }
            
            // Active only
            if (!empty($filters['active_only'])) {
                $conditions[] = "active = 1";
            }
            
            // Operation type
            if (!empty($filters['operation_type'])) {
                $conditions[] = "operation_type = :operation_type";
                $params['operation_type'] = $filters['operation_type'];
            }
            
            // Due before (next_date <= date)
            if (!empty($filters['due_before'])) {
                $conditions[] = "next_date <= :due_before";
                $params['due_before'] = $filters['due_before'];
            }
            
            // Due after (next_date >= date)
            if (!empty($filters['due_after'])) {
                $conditions[] = "next_date >= :due_after";
                $params['due_after'] = $filters['due_after'];
            }
            
            // Category
            if (!empty($filters['category_id'])) {
                $conditions[] = "category_id = :category_id";
                $params['category_id'] = $filters['category_id'];
            }
            
            $sql = "SELECT * FROM {$this->table}";
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }
            $sql .= " ORDER BY next_date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => PlannedTransaction::fromArray($data), $results);
            
        } catch (Exception $e) {
            error_log("Erreur getFiltered planned_transaction: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer toutes les transactions planifiées d'un utilisateur
     * @return PlannedTransaction[]
     */
    public function getAllByUserId(int $userId, bool $activeOnly = false): array
    {
        return $this->getFiltered([
            'user_id' => $userId,
            'active_only' => $activeOnly
        ]);
    }
    
    /**
     * Récupérer les transactions planifiées à exécuter (date <= aujourd'hui)
     * @return PlannedTransaction[]
     */
    public function getDueTransactions(?int $userId = null): array
    {
        $filters = [
            'active_only' => true,
            'due_before' => date('Y-m-d')
        ];
        
        if ($userId !== null) {
            $filters['user_id'] = $userId;
        }
        
        return $this->getFiltered($filters);
    }
    
    /**
     * Récupérer les prochaines transactions planifiées
     * @return PlannedTransaction[]
     */
    public function getUpcoming(int $userId, int $days = 30): array
    {
        try {
            $today = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime("+{$days} days"));
            
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id 
                    AND active = 1 
                    AND next_date BETWEEN :today AND :end_date
                    ORDER BY next_date ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'today' => $today,
                'end_date' => $endDate
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return array_map(fn($data) => PlannedTransaction::fromArray($data), $results);
            
        } catch (Exception $e) {
            error_log("Erreur getUpcoming: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer par type d'opération (income/expense)
     * @return PlannedTransaction[]
     */
    public function getByOperationType(int $userId, string $operationType, bool $activeOnly = true): array
    {
        return $this->getFiltered([
            'user_id' => $userId,
            'operation_type' => $operationType,
            'active_only' => $activeOnly
        ]);
    }
    
    /**
     * Récupérer par catégorie
     * @return PlannedTransaction[]
     */
    public function getByCategory(int $userId, int $categoryId, bool $activeOnly = true): array
    {
        return $this->getFiltered([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'active_only' => $activeOnly
        ]);
    }
    
    /**
     * Calculer le total mensuel prévu (revenus - dépenses)
     * Optimisé avec une seule requête SQL
     */
    public function calculateMonthlyProjection(int $userId): array
    {
        try {
            $sql = "SELECT 
                        operation_type,
                        frequency,
                        SUM(amount) as total_amount
                    FROM {$this->table} 
                    WHERE user_id = :user_id AND active = 1
                    GROUP BY operation_type, frequency";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $monthlyIncome = 0.0;
            $monthlyExpense = 0.0;
            
            foreach ($results as $row) {
                $monthlyAmount = $this->convertToMonthlyAmount(
                    (float) $row['total_amount'],
                    $row['frequency']
                );
                
                if ($row['operation_type'] === 'income') {
                    $monthlyIncome += $monthlyAmount;
                } else {
                    $monthlyExpense += $monthlyAmount;
                }
            }
            
            return [
                'monthly_income' => round($monthlyIncome, 2),
                'monthly_expense' => round($monthlyExpense, 2),
                'monthly_balance' => round($monthlyIncome - $monthlyExpense, 2)
            ];
            
        } catch (Exception $e) {
            error_log("Erreur calculateMonthlyProjection: " . $e->getMessage());
            return [
                'monthly_income' => 0.0,
                'monthly_expense' => 0.0,
                'monthly_balance' => 0.0
            ];
        }
    }
    
    /**
     * Convertir un montant en équivalent mensuel selon la fréquence
     */
    private function convertToMonthlyAmount(float $amount, string $frequency): float
    {
        return match ($frequency) {
            'daily' => $amount * 30,
            'weekly' => $amount * 4.33, // 52 semaines / 12 mois
            'monthly' => $amount,
            'yearly' => $amount / 12,
            default => $amount
        };
    }
    
    /**
     * Mettre à jour la prochaine date d'exécution
     */
    public function updateNextDate(int $id, string $nextDate): bool
    {
        return $this->update($id, ['next_date' => $nextDate]);
    }
    
    /**
     * Activer/Désactiver une transaction planifiée
     */
    public function setActive(int $id, bool $active): bool
    {
        return $this->update($id, ['active' => $active]);
    }
    
    /**
     * Compter les transactions planifiées avec filtres optionnels
     */
    public function countByUserId(int $userId, bool $activeOnly = false, ?string $operationType = null): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id";
            $params = ['user_id' => $userId];
            
            if ($activeOnly) {
                $sql .= " AND active = 1";
            }
            
            if ($operationType !== null) {
                $sql .= " AND operation_type = :operation_type";
                $params['operation_type'] = $operationType;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
            
        } catch (Exception $e) {
            error_log("Erreur countByUserId: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Récupérer les statistiques agrégées en une seule requête
     */
    public function getAggregatedStats(int $userId): array
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN active = 1 THEN 1 ELSE 0 END) as active_count,
                        SUM(CASE WHEN active = 0 THEN 1 ELSE 0 END) as inactive_count,
                        SUM(CASE WHEN active = 1 AND next_date <= :today THEN 1 ELSE 0 END) as due_count,
                        SUM(CASE WHEN active = 1 AND operation_type = 'income' THEN 1 ELSE 0 END) as income_count,
                        SUM(CASE WHEN active = 1 AND operation_type = 'expense' THEN 1 ELSE 0 END) as expense_count
                    FROM {$this->table} 
                    WHERE user_id = :user_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'today' => date('Y-m-d')
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_count' => (int) ($result['total'] ?? 0),
                'active_count' => (int) ($result['active_count'] ?? 0),
                'inactive_count' => (int) ($result['inactive_count'] ?? 0),
                'due_count' => (int) ($result['due_count'] ?? 0),
                'income_count' => (int) ($result['income_count'] ?? 0),
                'expense_count' => (int) ($result['expense_count'] ?? 0)
            ];
            
        } catch (Exception $e) {
            error_log("Erreur getAggregatedStats: " . $e->getMessage());
            return [
                'total_count' => 0,
                'active_count' => 0,
                'inactive_count' => 0,
                'due_count' => 0,
                'income_count' => 0,
                'expense_count' => 0
            ];
        }
    }
}
