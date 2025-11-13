<?php

namespace FinanceFlow\Services;

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
    
    public function __construct(string $table = null) 
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
    public function getAll(array $conditions = [], int $limit = null, int $offset = 0): array
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
     * Supprimer un enregistrement par ID
     */
    public function delete(int $id): bool
    {
        try {
            // Vérifier s'il y a une colonne is_active pour soft delete
            if ($this->hasColumn('is_active')) {
                return $this->softDelete($id);
            } else {
                return $this->hardDelete($id);
            }
        } catch (Exception $e) {
            error_log("Erreur delete dans {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Soft delete (marquer comme inactif)
     */
    protected function softDelete(int $id): bool
    {
        try {
            $updateFields = ['is_active = 0'];
            
            if ($this->hasColumn('updated_at')) {
                $updateFields[] = 'updated_at = NOW()';
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $updateFields) . " WHERE {$this->primaryKey} = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $id]);
            
        } catch (Exception $e) {
            error_log("Erreur softDelete dans {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Hard delete (suppression physique)
     */
    protected function hardDelete(int $id): bool
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $id]);
            
        } catch (Exception $e) {
            error_log("Erreur hardDelete dans {$this->table}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Compter les enregistrements
     */
    public function count(array $conditions = []): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    $whereClause[] = "$field = :$field";
                    $params[$field] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return (int) $stmt->fetchColumn();
            
        } catch (Exception $e) {
            error_log("Erreur count dans {$this->table}: " . $e->getMessage());
            return 0;
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
    
    /**
     * Vérifier si un enregistrement existe
     */
    public function exists(array $conditions): bool
    {
        return $this->count($conditions) > 0;
    }
}