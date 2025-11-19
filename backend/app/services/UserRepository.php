<?php

namespace FinanceFlow\Services;

use FinanceFlow\Models\User;
use Exception;

/**
 * Repository pour les utilisateurs
 * Hérite des méthodes communes de Repository et ajoute les spécificités utilisateur
 */
class UserRepository extends Repository
{
    /**
     * Nom de la table utilisateur
     */
    protected function getTableName(): string
    {
        return 'user';
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function create(array $data): ?int
    {
        try {
            // Adapter aux colonnes existantes de la table
            $sql = "INSERT INTO {$this->table} (name, email, password) 
                    VALUES (:name, :email, :password)";
            
            $stmt = $this->pdo->prepare($sql);
            
            // Utiliser 'name' au lieu de 'username' et 'password' au lieu de 'password_hash'
            $name = $data['username'] ?? $data['name'] ?? '';
            $password = $data['password_hash'] ?? $data['password'] ?? '';
            
            $result = $stmt->execute([
                'name' => $name,
                'email' => $data['email'],
                'password' => $password
            ]);

            return $result ? (int) $this->pdo->lastInsertId() : null;
            
        } catch (Exception $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            throw $e; // Re-throw pour debugging
        }
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function update(int $id, array $data): bool
    {
        try {
            $fields = [];
            $params = ['id' => $id];
            
            // Adapter aux colonnes existantes
            $allowedFields = ['name', 'email', 'password'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
            
            // Mapper les anciens champs vers les nouveaux
            if (array_key_exists('username', $data)) {
                $fields[] = "name = :name";
                $params['name'] = $data['username'];
            }
            if (array_key_exists('password_hash', $data)) {
                $fields[] = "password = :password";
                $params['password'] = $data['password_hash'];
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE {$this->primaryKey} = :id";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy(['email' => $email]);
    }
    
    /**
     * Trouver un utilisateur par nom d'utilisateur
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy(['name' => $username]);
    }
    
    /**
     * Trouver un utilisateur par ID
     */
    public function findActiveById(int $id): ?array
    {
        return $this->findBy(['id' => $id]);
    }
    
    /**
     * Vérifier si l'email existe déjà
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $conditions = ['email' => $email];
        
        if ($excludeId) {
            // Pour les exclusions, on fait une requête custom
            try {
                $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email AND id != :exclude_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['email' => $email, 'exclude_id' => $excludeId]);
                return $stmt->fetchColumn() > 0;
            } catch (Exception $e) {
                error_log("Erreur vérification email: " . $e->getMessage());
                return false;
            }
        }
        
        return $this->exists($conditions);
    }
    
    /**
     * Vérifier si le nom d'utilisateur existe déjà
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $conditions = ['name' => $username];
        
        if ($excludeId) {
            // Pour les exclusions, on fait une requête custom
            try {
                $sql = "SELECT COUNT(*) FROM {$this->table} WHERE name = :name AND id != :exclude_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['name' => $username, 'exclude_id' => $excludeId]);
                return $stmt->fetchColumn() > 0;
            } catch (Exception $e) {
                error_log("Erreur vérification username: " . $e->getMessage());
                return false;
            }
        }
        
        return $this->exists($conditions);
    }
    
    /**
     * Récupérer tous les utilisateurs
     */
    public function getAllActive(?int $limit = null, int $offset = 0): array
    {
        return $this->getAll([], $limit, $offset);
    }
    
    /**
     * Compter les utilisateurs
     */
    public function countActive(): int
    {
        return $this->count([]);
    }
    
    /**
     * Convertir les données en objet User (pour compatibilité)
     */
    public function toUserObject(array $userData): User
    {
        return User::fromArray($userData);
    }
    
    /**
     * Trouver un utilisateur et le retourner comme objet User
     */
    public function findUserObjectById(int $id): ?User
    {
        $userData = $this->findActiveById($id);
        return $userData ? $this->toUserObject($userData) : null;
    }
    
    /**
     * Trouver un utilisateur par email et le retourner comme objet User
     */
    public function findUserObjectByEmail(string $email): ?User
    {
        $userData = $this->findByEmail($email);
        return $userData ? $this->toUserObject($userData) : null;
    }
}