<?php

namespace FinanceFlow\Services;

use Exception;

/**
 * Repository pour les transactions
 * Hérite des méthodes communes de Repository et ajoute les spécificités transactions
 */
class TransactionRepository extends Repository
{
    /**
     * Nom de la table transaction
     */
    protected function getTableName(): string
    {
        return 'transaction';
    }
    
    /**
     * Créer une nouvelle transaction
     */
    public function create(array $data): ?int
    {
        try {
            $sql = "INSERT INTO {$this->table} (title, description, amount, date, location, category_id, sub_category_id, user_id, account_id) 
                    VALUES (:title, :description, :amount, :date, :location, :category_id, :sub_category_id, :user_id, :account_id)";
            
            $stmt = $this->pdo->prepare($sql);
            
            $result = $stmt->execute([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'date' => $data['date'],
                'location' => $data['location'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'sub_category_id' => $data['sub_category_id'] ?? null,
                'user_id' => $data['user_id'],
                'account_id' => $data['account_id'] ?? null
            ]);

            return $result ? (int) $this->pdo->lastInsertId() : null;
            
        } catch (Exception $e) {
            error_log("Erreur création transaction: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Mettre à jour une transaction
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = ['id' => $id];
            
            $allowedFields = ['title', 'description', 'amount', 'date', 'location', 'category_id', 'sub_category_id', 'account_id'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = :id";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les transactions d'un utilisateur
     */
    public function getByUserId(int $userId, ?int $limit = null, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY date DESC";
            
            if ($limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }
            
            $stmt = $this->pdo->prepare($sql);
            $params = ['user_id' => $userId];
            
            if ($limit) {
                $params['limit'] = $limit;
                $params['offset'] = $offset;
            }
            
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Erreur récupération transactions utilisateur: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer une transaction par ID et user_id (sécurité)
     */
    public function findByIdAndUserId(int $id, int $userId): ?array
    {
        return $this->findBy(['id' => $id, 'user_id' => $userId]);
    }
    
    /**
     * Compter les transactions d'un utilisateur
     */
    public function countByUserId(int $userId): int
    {
        return $this->count(['user_id' => $userId]);
    }
    
    /**
     * Récupérer les transactions par plage de dates
     */
    public function getByDateRange(int $userId, string $startDate, string $endDate): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id 
                    AND date BETWEEN :start_date AND :end_date 
                    ORDER BY date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Erreur récupération transactions par date: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les transactions par catégorie
     */
    public function getByCategoryId(int $userId, int $categoryId): array
    {
        return $this->getAll(['user_id' => $userId, 'category_id' => $categoryId]);
    }
    
    /**
     * Calculer le total des transactions d'un utilisateur
     */
    public function getTotalAmount(int $userId, ?string $startDate = null, ?string $endDate = null): float
    {
        try {
            $sql = "SELECT SUM(amount) as total FROM {$this->table} WHERE user_id = :user_id";
            $params = ['user_id' => $userId];
            
            if ($startDate && $endDate) {
                $sql .= " AND date BETWEEN :start_date AND :end_date";
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return (float) ($result['total'] ?? 0);
            
        } catch (Exception $e) {
            error_log("Erreur calcul total transactions: " . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Récupérer les dernières transactions d'un utilisateur
     */
    public function getRecentTransactions(int $userId, int $limit = 10): array
    {
        return $this->getByUserId($userId, $limit, 0);
    }
    
    /**
     * Rechercher des transactions par titre ou description
     */
    public function searchTransactions(int $userId, string $searchTerm): array
    {
        try {
            $sql = "SELECT * FROM {$this->table} 
                    WHERE user_id = :user_id 
                    AND (title LIKE :search OR description LIKE :search)
                    ORDER BY date DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'search' => '%' . $searchTerm . '%'
            ]);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            error_log("Erreur recherche transactions: " . $e->getMessage());
            return [];
        }
    }
}