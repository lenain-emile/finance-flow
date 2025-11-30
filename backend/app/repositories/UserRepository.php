<?php

namespace FinanceFlow\Repositories;

use FinanceFlow\Core\Repository;
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
     * Wrapper qui mappe username->name et password_hash->password avant d'appeler parent::create
     */
    public function create(array $data): ?int
    {
        try {
            // Mapper les noms de champs DTO vers les colonnes DB
            $dbData = [
                'name' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password_hash']
            ];
            
            return parent::create($dbData);
            
        } catch (Exception $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Mettre à jour un utilisateur
     * Wrapper qui mappe username->name et password_hash->password avant d'appeler parent::update
     */
    public function update(int $id, array $data): bool
    {
        try {
            $dbData = [];
            
            // Mapper les noms de champs DTO vers les colonnes DB
            if (array_key_exists('username', $data)) {
                $dbData['name'] = $data['username'];
            }
            
            if (array_key_exists('password_hash', $data)) {
                $dbData['password'] = $data['password_hash'];
            }
            
            if (array_key_exists('email', $data)) {
                $dbData['email'] = $data['email'];
            }
            
            if (empty($dbData)) {
                return false;
            }
            
            return parent::update($id, $dbData);
            
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
        return $this->findOneBy(['email' => $email]);
    }
    
    /**
     * Trouver un utilisateur par nom d'utilisateur
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findOneBy(['name' => $username]);
    }
    
    /**
     * Trouver un utilisateur par ID
     */
    public function findActiveById(int $id): ?array
    {
        return $this->findOneBy(['id' => $id]);
    }
    
    /**
     * Vérifier si l'email existe déjà
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId) {
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
        
        return $this->findOneBy(['email' => $email]) !== null;
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
        
        return $this->findOneBy(['name' => $username]) !== null;
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