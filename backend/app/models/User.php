<?php

namespace FinanceFlow\Models;

use FinanceFlow\Core\Database;
use PDO;
use Exception;

class User
{
    private PDO $pdo;
    
    // Propriétés de l'utilisateur
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $passwordHash = null;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private ?string $phone = null;
    private ?string $avatar = null;
    private bool $isActive = true;
    private bool $isVerified = false;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct()
    {
        $database = Database::getInstance();
        $this->pdo = $database->getConnection();
    }

    // Getters essentiels seulement
    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function getPasswordHash(): ?string { return $this->passwordHash; }
    public function isVerified(): bool { return $this->isVerified; }

    /**
     * Créer un nouvel utilisateur
     */
    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO user (username, email, password_hash, first_name, last_name, phone, avatar, is_active, is_verified, created_at, updated_at) 
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

            if ($result) {
                $this->id = $this->pdo->lastInsertId();
                $this->loadFromArray($data);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouver un utilisateur par ID
     */
    public function findById(int $id): ?User
    {
        try {
            $sql = "SELECT * FROM user WHERE id = :id AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $user = new User();
                $user->loadFromArray($userData);
                return $user;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erreur recherche utilisateur par ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Trouver un utilisateur par email
     */
    public function findByEmail(string $email): ?User
    {
        try {
            $sql = "SELECT * FROM user WHERE email = :email AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $user = new User();
                $user->loadFromArray($userData);
                return $user;
            }
            
            return null;
        } catch (Exception $e) {
            error_log("Erreur recherche utilisateur par email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mettre à jour l'utilisateur
     */
    public function update(array $data): bool
    {
        if (!$this->id) {
            return false;
        }

        try {
            $fields = [];
            $params = ['id' => $this->id];
            
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
            $sql = "UPDATE user SET " . implode(', ', $fields) . " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            if ($result) {
                $this->loadFromArray($data);
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Erreur mise à jour utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer l'utilisateur (soft delete)
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        try {
            $sql = "UPDATE user SET is_active = 0, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(['id' => $this->id]);
        } catch (Exception $e) {
            error_log("Erreur suppression utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si l'email existe déjà
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM user WHERE email = :email AND is_active = 1";
            $params = ['email' => $email];
            
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Erreur vérification email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si le nom d'utilisateur existe déjà
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM user WHERE username = :username AND is_active = 1";
            $params = ['username' => $username];
            
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            error_log("Erreur vérification username: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convertir l'utilisateur en tableau (sans le mot de passe)
     */
    public function toArray(bool $includePassword = false): array
    {
        $data = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'is_active' => $this->isActive,
            'is_verified' => $this->isVerified,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];

        if ($includePassword) {
            $data['password_hash'] = $this->passwordHash;
        }

        return $data;
    }

    /**
     * Charger les données depuis un tableau
     */
    private function loadFromArray(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->passwordHash = $data['password_hash'] ?? null;
        $this->firstName = $data['first_name'] ?? null;
        $this->lastName = $data['last_name'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->isActive = (bool) ($data['is_active'] ?? true);
        $this->isVerified = (bool) ($data['is_verified'] ?? false);
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
    }
}