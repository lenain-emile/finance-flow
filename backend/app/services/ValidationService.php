<?php

namespace FinanceFlow\Services;

use FinanceFlow\Services\UserRepository;

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

    private function validateUsername(string $username, ?int $excludeUserId = null): array
    {
        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Le nom d\'utilisateur est requis';
        } elseif (strlen($username) < 3) {
            $errors['username'] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors['username'] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et tirets bas';
        } elseif ($this->userRepository->usernameExists($username, $excludeUserId)) {
            $errors['username'] = 'Ce nom d\'utilisateur est déjà pris';
        }

        return $errors;
    }

    private function validateEmail(string $email, ?int $excludeUserId = null): array
    {
        $errors = [];

        if (empty($email)) {
            $errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format d\'email invalide';
        } elseif ($this->userRepository->emailExists($email, $excludeUserId)) {
            $errors['email'] = 'Cette adresse email est déjà utilisée';
        }

        return $errors;
    }

    private function validatePassword(string $password, bool $isUpdate): array
    {
        $errors = [];

        if (!$isUpdate && empty($password)) {
            $errors['password'] = 'Le mot de passe est requis';
        } elseif (!empty($password) && strlen($password) < 8) {
            $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        return $errors;
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