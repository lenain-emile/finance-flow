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
            $sql = "INSERT INTO {$this->table} (username, email, password_hash, first_name, last_name, phone, avatar, is_active, is_verified, created_at, updated_at) 
                    VALUES (:username, :email, :password_hash, :first_name, :last_name, :phone, :avatar, :is_active, :is_verified, NOW(), NOW())";
            
            $stmt = $this->pdo->prepare($sql);
            
            $result = $stmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'password_hash' => $data['password_hash'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'avatar' => $data['avatar'] ?? null,
                'is_active' => $data['is_active'] ?? 1,
                'is_verified' => $data['is_verified'] ?? 0
            ]);

            return $result ? (int) $this->pdo->lastInsertId() : null;
            
        } catch (Exception $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            return null;
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
            
            $allowedFields = ['username', 'email', 'password_hash', 'first_name', 'last_name', 'phone', 'avatar', 'is_verified', 'is_active'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $fields[] = "$field = :$field";
                    $params[$field] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                return false;
            }
            
            $fields[] = "updated_at = NOW()";
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
        return $this->findBy(['email' => $email, 'is_active' => 1]);
    }
    
    /**
     * Trouver un utilisateur par nom d'utilisateur
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findBy(['username' => $username, 'is_active' => 1]);
    }
    
    /**
     * Trouver un utilisateur actif par ID
     */
    public function findActiveById(int $id): ?array
    {
        return $this->findBy(['id' => $id, 'is_active' => 1]);
    }
    
    /**
     * Vérifier si l'email existe déjà
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $conditions = ['email' => $email, 'is_active' => 1];
        
        if ($excludeId) {
            // Pour les exclusions, on fait une requête custom
            try {
                $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email AND is_active = 1 AND id != :exclude_id";
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
        $conditions = ['username' => $username, 'is_active' => 1];
        
        if ($excludeId) {
            // Pour les exclusions, on fait une requête custom
            try {
                $sql = "SELECT COUNT(*) FROM {$this->table} WHERE username = :username AND is_active = 1 AND id != :exclude_id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['username' => $username, 'exclude_id' => $excludeId]);
                return $stmt->fetchColumn() > 0;
            } catch (Exception $e) {
                error_log("Erreur vérification username: " . $e->getMessage());
                return false;
            }
        }
        
        return $this->exists($conditions);
    }
    
    /**
     * Récupérer tous les utilisateurs actifs
     */
    public function getAllActive(int $limit = null, int $offset = 0): array
    {
        return $this->getAll(['is_active' => 1], $limit, $offset);
    }
    
    /**
     * Compter les utilisateurs actifs
     */
    public function countActive(): int
    {
        return $this->count(['is_active' => 1]);
    }
    
    /**
     * Marquer un email comme vérifié
     */
    public function markEmailAsVerified(int $userId): bool
    {
        return $this->update($userId, ['is_verified' => 1]);
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