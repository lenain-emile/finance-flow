<?php

namespace FinanceFlow\DTOs\User;

use FinanceFlow\Models\User;

/**
 * DTO pour les réponses GET d'utilisateur
 * Représente les données utilisateur qui seront retournées au client
 */
class UserResponse
{
    private array $tokens = [];

    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $phone,
        public readonly ?string $avatar,
        public readonly bool $isActive,
        public readonly bool $isVerified,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    /**
     * Créer depuis un objet User
     */
    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->getId() ?? 0,
            username: $user->getUsername() ?? '',
            email: $user->getEmail() ?? '',
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            phone: $user->getPhone(),
            avatar: $user->getAvatar(),
            isActive: $user->isActive(),
            isVerified: $user->isVerified(),
            createdAt: $user->getCreatedAt() ?? date('Y-m-d H:i:s'),
            updatedAt: $user->getUpdatedAt() ?? date('Y-m-d H:i:s')
        );
    }

    /**
     * Créer depuis un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            username: $data['username'],
            email: $data['email'],
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            phone: $data['phone'] ?? null,
            avatar: $data['avatar'] ?? null,
            isActive: (bool) $data['is_active'],
            isVerified: (bool) $data['is_verified'],
            createdAt: $data['created_at'],
            updatedAt: $data['updated_at']
        );
    }

    /**
     * Définir les tokens d'authentification
     */
    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }

    /**
     * Convertir en array pour réponse JSON
     */
    public function toArray(): array
    {
        $array = [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'full_name' => $this->getFullName(),
            'initials' => $this->getInitials(),
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'is_active' => $this->isActive,
            'is_verified' => $this->isVerified,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];

        // Ajouter les tokens si ils existent
        if (!empty($this->tokens)) {
            $array = array_merge($array, $this->tokens);
        }

        return $array;
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
     * Obtenir les initiales
     */
    public function getInitials(): string
    {
        if ($this->firstName || $this->lastName) {
            $first = $this->firstName ? strtoupper($this->firstName[0]) : '';
            $last = $this->lastName ? strtoupper($this->lastName[0]) : '';
            return $first . $last;
        }
        
        return strtoupper(substr($this->username, 0, 2));
    }

    /**
     * Version minimale pour les listes
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'full_name' => $this->getFullName(),
            'initials' => $this->getInitials(),
            'avatar' => $this->avatar,
            'is_verified' => $this->isVerified
        ];
    }
}