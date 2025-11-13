<?php

namespace FinanceFlow\DTOs\User;

/**
 * DTO pour les requêtes POST de création d'utilisateur
 */
class CreateUserRequest
{
    public function __construct(
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $firstName = null,
        public readonly ?string $lastName = null,
        public readonly ?string $phone = null
    ) {}

    /**
     * Créer depuis un array (validation basique)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            username: $data['username'] ?? '',
            email: $data['email'] ?? '',
            password: $data['password'] ?? '',
            firstName: $data['first_name'] ?? null,
            lastName: $data['last_name'] ?? null,
            phone: $data['phone'] ?? null
        );
    }

    /**
     * Convertir en array pour traitement
     */
    public function toArray(): array
    {
        return [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone
        ];
    }

    /**
     * Validation basique des champs requis
     */
    public function isValid(): array
    {
        $errors = [];

        if (empty($this->username)) {
            $errors['username'] = 'Le nom d\'utilisateur est requis';
        }

        if (empty($this->email)) {
            $errors['email'] = 'L\'email est requis';
        }

        if (empty($this->password)) {
            $errors['password'] = 'Le mot de passe est requis';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}