<?php

namespace FinanceFlow\DTOs\User;

/**
 * DTO pour les requêtes PUT de mise à jour d'utilisateur
 */
class UpdateUserRequest
{
    public function __construct(
        public readonly ?string $username = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $phone = null,
        public readonly ?string $avatar = null
    ) {}

    /**
     * Créer depuis un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['username'] ?? null,
            email: $data['email'] ?? null,
            password: $data['password'] ?? null,
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            phone: $data['phone'] ?? null,
            avatar: $data['avatar'] ?? null
        );
    }

    /**
     * Convertir en array pour traitement (exclude null values)
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->username !== null) $data['username'] = $this->username;
        if ($this->email !== null) $data['email'] = $this->email;
        if ($this->password !== null) $data['password'] = $this->password;
        if ($this->firstName !== null) $data['first_name'] = $this->firstName;
        if ($this->lastName !== null) $data['last_name'] = $this->lastName;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->avatar !== null) $data['avatar'] = $this->avatar;

        return $data;
    }

    /**
     * Vérifier s'il y a des données à mettre à jour
     */
    public function hasUpdates(): bool
    {
        return !empty($this->toArray());
    }

    /**
     * Validation basique
     */
    public function isValid(): array
    {
        $errors = [];

        // L'email doit être valide s'il est fourni
        if ($this->email !== null && !filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide';
        }

        // Le mot de passe doit avoir une longueur minimale s'il est fourni
        if ($this->password !== null && strlen($this->password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}