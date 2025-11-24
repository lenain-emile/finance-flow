<?php

namespace FinanceFlow\Services;

use FinanceFlow\Repositories\UserRepository;

class ValidationService
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Valider les données d'inscription
     */
    public function validateUserRegistration(array $data): array
    {
        return $this->validateUserData($data, false);
    }

    /**
     * Valider les données de mise à jour
     */
    public function validateUserUpdate(array $data, int $userId): array
    {
        return $this->validateUserData($data, true, $userId);
    }

    /**
     * Valider les données utilisateur
     */
    private function validateUserData(array $data, bool $isUpdate = false, ?int $userId = null): array
    {
        $errors = [];

        // Validation username
        if (!$isUpdate || isset($data['username'])) {
            $errors = array_merge($errors, $this->validateUsername($data['username'] ?? '', $userId));
        }

        // Validation email
        if (!$isUpdate || isset($data['email'])) {
            $errors = array_merge($errors, $this->validateEmail($data['email'] ?? '', $userId));
        }

        // Validation password
        if (!$isUpdate || isset($data['password'])) {
            $errors = array_merge($errors, $this->validatePassword($data['password'] ?? '', $isUpdate));
        }

        // Validation des champs optionnels
        $errors = array_merge($errors, $this->validateOptionalFields($data));

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Valider uniquement l'unicité du nom d'utilisateur
     * (Les autres validations sont dans CreateUserRequest/UpdateUserRequest)
     */
    private function validateUsername(string $username, ?int $excludeUserId = null): array
    {
        $errors = [];

        if (!empty($username) && $this->userRepository->usernameExists($username, $excludeUserId)) {
            $errors['username'] = 'Ce nom d\'utilisateur est déjà pris';
        }

        return $errors;
    }

    /**
     * Valider uniquement l'unicité de l'email
     * (Les autres validations sont dans CreateUserRequest/UpdateUserRequest)
     */
    private function validateEmail(string $email, ?int $excludeUserId = null): array
    {
        $errors = [];

        if (!empty($email) && $this->userRepository->emailExists($email, $excludeUserId)) {
            $errors['email'] = 'Cette adresse email est déjà utilisée';
        }

        return $errors;
    }

    /**
     * Validation mot de passe simplifiée
     * (Les validations de format sont dans CreateUserRequest/UpdateUserRequest)
     */
    private function validatePassword(string $password, bool $isUpdate): array
    {
        // Plus de validation ici - les DTOs s'en chargent
        return [];
    }

    private function validateOptionalFields(array $data): array
    {
        $errors = [];

        if (isset($data['first_name']) && strlen($data['first_name']) > 50) {
            $errors['first_name'] = 'Le prénom ne peut pas dépasser 50 caractères';
        }

        if (isset($data['last_name']) && strlen($data['last_name']) > 50) {
            $errors['last_name'] = 'Le nom ne peut pas dépasser 50 caractères';
        }

        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^[+]?[0-9\s\-\(\)]{8,20}$/', $data['phone'])) {
                $errors['phone'] = 'Format de téléphone invalide';
            }
        }

        return $errors;
    }


}