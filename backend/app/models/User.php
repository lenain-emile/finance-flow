<?php

namespace FinanceFlow\Models;

/**
 * Entité User - représente un utilisateur sans logique de base de données
 * La logique de base de données est maintenant dans UserRepository
 */
class User
{
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

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getUsername(): ?string { return $this->username; }
    public function getEmail(): ?string { return $this->email; }
    public function getPasswordHash(): ?string { return $this->passwordHash; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getPhone(): ?string { return $this->phone; }
    public function getAvatar(): ?string { return $this->avatar; }
    public function isActive(): bool { return $this->isActive; }
    public function isVerified(): bool { return $this->isVerified; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    // Setters
    public function setId(?int $id): self { $this->id = $id; return $this; }
    public function setUsername(?string $username): self { $this->username = $username; return $this; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }
    public function setPasswordHash(?string $passwordHash): self { $this->passwordHash = $passwordHash; return $this; }
    public function setFirstName(?string $firstName): self { $this->firstName = $firstName; return $this; }
    public function setLastName(?string $lastName): self { $this->lastName = $lastName; return $this; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    public function setAvatar(?string $avatar): self { $this->avatar = $avatar; return $this; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }
    public function setIsVerified(bool $isVerified): self { $this->isVerified = $isVerified; return $this; }
    public function setCreatedAt(?string $createdAt): self { $this->createdAt = $createdAt; return $this; }
    public function setUpdatedAt(?string $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    /**
     * Convertir l'utilisateur en tableau (sans le mot de passe par défaut)
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
    public function loadFromArray(array $data): self
    {
        $this->id = $data['id'] ?? null;
        
        // Mapper les champs de la vraie table vers notre modèle
        $this->username = $data['name'] ?? $data['username'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->passwordHash = $data['password'] ?? $data['password_hash'] ?? null;
        
        // Les autres champs n'existent pas dans la vraie table
        $this->firstName = $data['first_name'] ?? null;
        $this->lastName = $data['last_name'] ?? null;
        $this->phone = $data['phone'] ?? null;
        $this->avatar = $data['avatar'] ?? null;
        $this->isActive = (bool) ($data['is_active'] ?? true);
        $this->isVerified = (bool) ($data['is_verified'] ?? false);
        $this->createdAt = $data['created_at'] ?? null;
        $this->updatedAt = $data['updated_at'] ?? null;
        
        return $this;
    }

    /**
     * Créer un objet User depuis un tableau de données
     */
    public static function fromArray(array $data): self
    {
        return (new self())->loadFromArray($data);
    }

    /**
     * Obtenir le nom complet
     */
    public function getFullName(): string
    {
        $parts = array_filter([$this->firstName, $this->lastName]);
        return implode(' ', $parts) ?: $this->username;
    }

    /**
     * Vérifier si l'utilisateur a un nom complet
     */
    public function hasFullName(): bool
    {
        return !empty($this->firstName) || !empty($this->lastName);
    }

    /**
     * Obtenir l'initiales
     */
    public function getInitials(): string
    {
        if ($this->hasFullName()) {
            $first = $this->firstName ? strtoupper($this->firstName[0]) : '';
            $last = $this->lastName ? strtoupper($this->lastName[0]) : '';
            return $first . $last;
        }
        
        return $this->username ? strtoupper(substr($this->username, 0, 2)) : 'U';
    }
}