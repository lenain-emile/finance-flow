<?php

namespace FinanceFlow\Repositories;

use FinanceFlow\Core\Repository;
use FinanceFlow\Models\Transaction;
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
     * Utilise la méthode générique du Repository parent
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
     * Utilise la méthode générique du Repository parent
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
     * Supprimer une transaction
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (Exception $e) {
            error_log("Erreur suppression transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer une transaction par ID et user_id (sécurité)
     */
    public function findByIdAndUserId(int $id, int $userId): ?Transaction
    {
        $data = $this->findBy(['id' => $id, 'user_id' => $userId]);
        return $data ? Transaction::fromArray($data) : null;
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
     * Récupérer les transactions avec filtres et tri avancés
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $filters Filtres à appliquer :
     *   - category_id: int
     *   - sub_category_id: int
     *   - account_id: int
     *   - min_amount: float
     *   - max_amount: float
     *   - start_date: string
     *   - end_date: string
     *   - search: string (titre ou description)
     * @param string $sortBy Colonne de tri (date, amount, title, category_id)
     * @param string $sortOrder Ordre de tri (ASC ou DESC)
     * @param int|null $limit Limite de résultats
     * @param int $offset Offset pour pagination
     * @return array
     */
    public function getFilteredAndSorted(
        int $userId,
        array $filters = [],
        string $sortBy = 'date',
        string $sortOrder = 'DESC',
        ?int $limit = null,
        int $offset = 0
    ): array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id";
            $params = ['user_id' => $userId];
            
            // Construire les conditions de filtrage
            if (!empty($filters['category_id'])) {
                $sql .= " AND category_id = :category_id";
                $params['category_id'] = (int) $filters['category_id'];
            }
            
            if (!empty($filters['sub_category_id'])) {
                $sql .= " AND sub_category_id = :sub_category_id";
                $params['sub_category_id'] = (int) $filters['sub_category_id'];
            }
            
            if (!empty($filters['account_id'])) {
                $sql .= " AND account_id = :account_id";
                $params['account_id'] = (int) $filters['account_id'];
            }
            
            if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
                $sql .= " AND amount >= :min_amount";
                $params['min_amount'] = (float) $filters['min_amount'];
            }
            
            if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
                $sql .= " AND amount <= :max_amount";
                $params['max_amount'] = (float) $filters['max_amount'];
            }
            
            if (!empty($filters['start_date'])) {
                $sql .= " AND date >= :start_date";
                $params['start_date'] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $sql .= " AND date <= :end_date";
                $params['end_date'] = $filters['end_date'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (title LIKE :search OR description LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            // Validation et ajout du tri
            $allowedSortColumns = ['date', 'amount', 'title', 'category_id', 'sub_category_id', 'created_at'];
            $sortBy = in_array($sortBy, $allowedSortColumns) ? $sortBy : 'date';
            $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
            
            $sql .= " ORDER BY {$sortBy} {$sortOrder}";
            
            // Ajout de la pagination
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $params['limit'] = $limit;
                $params['offset'] = $offset;
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind des paramètres avec types appropriés
            foreach ($params as $key => $value) {
                if (in_array($key, ['limit', 'offset', 'category_id', 'sub_category_id', 'account_id', 'user_id'])) {
                    $stmt->bindValue(":$key", (int) $value, \PDO::PARAM_INT);
                } elseif (in_array($key, ['min_amount', 'max_amount'])) {
                    $stmt->bindValue(":$key", (float) $value, \PDO::PARAM_STR);
                } else {
                    $stmt->bindValue(":$key", $value, \PDO::PARAM_STR);
                }
            }
            
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Convertir les tableaux en objets Transaction
            return array_map(fn($data) => Transaction::fromArray($data), $results);
            
        } catch (Exception $e) {
            error_log("Erreur getFilteredAndSorted: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compter les transactions avec filtres
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $filters Mêmes filtres que getFilteredAndSorted
     * @return int
     */
    public function countFiltered(int $userId, array $filters = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id";
            $params = ['user_id' => $userId];
            
            // Construire les mêmes conditions que getFilteredAndSorted
            if (!empty($filters['category_id'])) {
                $sql .= " AND category_id = :category_id";
                $params['category_id'] = (int) $filters['category_id'];
            }
            
            if (!empty($filters['sub_category_id'])) {
                $sql .= " AND sub_category_id = :sub_category_id";
                $params['sub_category_id'] = (int) $filters['sub_category_id'];
            }
            
            if (!empty($filters['account_id'])) {
                $sql .= " AND account_id = :account_id";
                $params['account_id'] = (int) $filters['account_id'];
            }
            
            if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
                $sql .= " AND amount >= :min_amount";
                $params['min_amount'] = (float) $filters['min_amount'];
            }
            
            if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
                $sql .= " AND amount <= :max_amount";
                $params['max_amount'] = (float) $filters['max_amount'];
            }
            
            if (!empty($filters['start_date'])) {
                $sql .= " AND date >= :start_date";
                $params['start_date'] = $filters['start_date'];
            }
            
            if (!empty($filters['end_date'])) {
                $sql .= " AND date <= :end_date";
                $params['end_date'] = $filters['end_date'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (title LIKE :search OR description LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
            
        } catch (Exception $e) {
            error_log("Erreur countFiltered: " . $e->getMessage());
            return 0;
        }
    }
}