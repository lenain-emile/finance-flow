<?php

namespace FinanceFlow\Core;

use FinanceFlow\Core\Database;
use PDO;
use Exception;

/**
 * Classe abstraite Repository pour les opérations CRUD communes
 */
abstract class Repository 
{
    protected PDO $pdo;
    protected string $table;
    protected string $primaryKey = 'id';
    
    public function __construct(?string $table = null) 
    {
        $database = Database::getInstance();
        $this->pdo = $database->getConnection();
        $this->table = $table ?? $this->getTableName();
    }
    
    /**
     * Méthode abstraite pour obtenir le nom de la table
     * Doit être implémentée par chaque repository enfant
     */
    abstract protected function getTableName(): string;
    
    /**
     * Récupérer tous les enregistrements
     */
    public function getAll(array $conditions = [], ?int $limit = null, int $offset = 0): array
    {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $params = [];
            
            // Ajouter les conditions WHERE
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    $whereClause[] = "$field = :$field";
                    $params[$field] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            // Ajouter LIMIT et OFFSET
            if ($limit) {
                $sql .= " LIMIT :limit OFFSET :offset";
                $params['limit'] = $limit;
                $params['offset'] = $offset;
            }
            
            $stmt = $this->pdo->prepare($sql);
            
            // Bind des paramètres avec leurs types
            foreach ($params as $key => $value) {
                if (in_array($key, ['limit', 'offset'])) {
                    $stmt->bindValue(":$key", (int) $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(":$key", $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getAll dans {$this->table}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Trouver un enregistrement par son ID
     */
    public function find(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (Exception $e) {
            error_log("Erreur find dans {$this->table}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver un enregistrement par condition
     */
    public function findBy(array $conditions): ?array
    {
        try {
            $whereClause = [];
            $params = [];
            
            foreach ($conditions as $field => $value) {
                $whereClause[] = "$field = :$field";
                $params[$field] = $value;
            }
            
            $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereClause) . " LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
            
        } catch (Exception $e) {
            error_log("Erreur findBy dans {$this->table}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Trouver un enregistrement par condition (alias de findBy)
     */
    public function findOneBy(array $conditions): ?array
    {
        return $this->findBy($conditions);
    }
    
    /**
     * Trouver tous les enregistrements par condition
     */
    public function findAllBy(array $conditions, array $orderBy = []): array
    {
        try {
            $whereClause = [];
            $params = [];
            
            foreach ($conditions as $field => $value) {
                $whereClause[] = "$field = :$field";
                $params[$field] = $value;
            }
            
            $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $whereClause);
            
            // Ajouter ORDER BY si spécifié
            if (!empty($orderBy)) {
                $orderClauses = [];
                foreach ($orderBy as $field => $direction) {
                    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                    $orderClauses[] = "$field $direction";
                }
                $sql .= " ORDER BY " . implode(', ', $orderClauses);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur findAllBy dans {$this->table}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Créer un nouvel enregistrement
     */
    public function create(array $data): ?int
    {
        try {
            $fields = array_keys($data);
            $placeholders = array_map(fn($field) => ":$field", $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($data);
            
            return $result ? (int) $this->pdo->lastInsertId() : null;
            
        } catch (Exception $e) {
            error_log("Erreur create dans {$this->table}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Mettre à jour un enregistrement
     */
    public function update(int $id, array $data): bool
    {
        try {
            if (empty($data)) {
                return false;
            }
            
            $fields = [];
            $params = ['id' => $id];
            
            foreach ($data as $field => $value) {
                $fields[] = "$field = :$field";
                $params[$field] = $value;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = :id";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Erreur update dans {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un enregistrement
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (Exception $e) {
            error_log("Erreur delete dans {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si une colonne existe dans la table
     */
    protected function hasColumn(string $columnName): bool
    {
        try {
            $sql = "SHOW COLUMNS FROM {$this->table} LIKE :column";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['column' => $columnName]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
}
