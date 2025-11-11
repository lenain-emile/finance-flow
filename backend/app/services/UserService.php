<?php

namespace FinanceFlow\Services;

use FinanceFlow\Models\User;
use FinanceFlow\Services\{AuthService, ValidationService};
use FinanceFlow\DTOs\ServiceResponse;

class UserService
{
    private AuthService $authService;
    private User $userModel;
    private ValidationService $validator;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userModel = new User();
        $this->validator = new ValidationService();
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function createUser(array $data): array
    {
        try {
            // Validation des données
            $validation = $this->validator->validateUserRegistration($data);
            if (!$validation['valid']) {
                return ServiceResponse::validationError($validation['errors']);
            }

            // Vérifications d'unicité
            if ($this->userModel->emailExists($data['email'])) {
                return ServiceResponse::error('Cette adresse email est déjà utilisée');
            }

            if ($this->userModel->usernameExists($data['username'])) {
                return ServiceResponse::error('Ce nom d\'utilisateur est déjà pris');
            }

            // Créer l'utilisateur
            $userData = $this->prepareUserDataForCreation($data);
            $userId = $this->userModel->create($userData);

            if (!$userId) {
                return ServiceResponse::error('Erreur lors de la création de l\'utilisateur');
            }

            // Récupérer l'utilisateur créé
            $user = $this->userModel->findById($userId);

            // Générer un token de vérification d'email
            $verificationToken = $this->authService->generateEmailVerificationToken(
                $user->getId(),
                $user->getEmail()
            );

            return ServiceResponse::success('Utilisateur créé avec succès', [
                'user' => $user->toArray(),
                'email_verification_token' => $verificationToken
            ]);

        } catch (\Exception $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            return ServiceResponse::error('Erreur interne du serveur');
        }
    }

    /**
     * Authentifier un utilisateur
     */
    public function loginUser(string $email, string $password): array
    {
        try {
            $user = $this->userModel->findByEmail($email);

            if (!$user || !$this->authService->verifyPassword($password, $user->getPasswordHash())) {
                return ServiceResponse::error('Email ou mot de passe incorrect');
            }

            $tokens = $this->generateUserTokens($user);

            return ServiceResponse::success('Connexion réussie', [
                'user' => $user->toArray(),
                ...$tokens
            ]);

        } catch (\Exception $e) {
            error_log("Erreur connexion utilisateur: " . $e->getMessage());
            return ServiceResponse::error('Erreur interne du serveur');
        }
    }

    /**
     * Rafraîchir le token d'accès
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $decoded = $this->authService->verifyRefreshToken($refreshToken);
            if (!$decoded) {
                return ServiceResponse::error('Token de rafraîchissement invalide');
            }

            $user = $this->userModel->findById($decoded->user_id);
            if (!$user) {
                return ServiceResponse::error('Utilisateur non trouvé');
            }

            $accessToken = $this->authService->generateToken(
                $user->getId(),
                $user->getEmail(),
                $user->getUsername()
            );

            return ServiceResponse::success('Token rafraîchi avec succès', [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ]);

        } catch (\Exception $e) {
            error_log("Erreur rafraîchissement token: " . $e->getMessage());
            return ServiceResponse::error('Erreur interne du serveur');
        }
    }

    /**
     * Obtenir le profil de l'utilisateur actuel
     */
    public function getCurrentUser(): array
    {
        try {
            $user = $this->getAuthenticatedUser();
            if (!$user) {
                return ServiceResponse::error('Token manquant ou invalide', [], 401);
            }

            return ServiceResponse::success('Profil récupéré avec succès', [
                'user' => $user->toArray()
            ]);

        } catch (\Exception $e) {
            error_log("Erreur récupération profil: " . $e->getMessage());
            return ServiceResponse::error('Erreur interne du serveur');
        }
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateUserProfile(array $data): array
    {
        try {
            $user = $this->getAuthenticatedUser();
            if (!$user) {
                return ServiceResponse::error('Token manquant ou invalide', [], 401);
            }

            // Validation des données
            $validation = $this->validator->validateUserUpdate($data, $user->getId());
            if (!$validation['valid']) {
                return ServiceResponse::validationError($validation['errors']);
            }

            $updateData = $this->prepareUserDataForUpdate($data);
            $updated = $user->update($updateData);

            if (!$updated) {
                return ServiceResponse::error('Erreur lors de la mise à jour du profil');
            }

            $updatedUser = $this->userModel->findById($user->getId());
            return ServiceResponse::success('Profil mis à jour avec succès', [
                'user' => $updatedUser->toArray()
            ]);

        } catch (\Exception $e) {
            error_log("Erreur mise à jour profil: " . $e->getMessage());
            return ServiceResponse::error('Erreur interne du serveur');
        }
    }

    /**
     * Vérifier l'email d'un utilisateur
     */
    public function verifyEmail(string $token): array
    {
        try {
            $decoded = $this->authService->verifyEmailVerificationToken($token);
            if (!$decoded) {
                return ServiceResponse::error('Token de vérification invalide ou expiré');
            }

            $user = $this->userModel->findById($decoded->user_id);
            if (!$user) {
                return ServiceResponse::error('Utilisateur non trouvé');
            }

            if ($user->isVerified()) {
                return ServiceResponse::success('Email déjà vérifié');
            }

            $updated = $user->update(['is_verified' => 1]);
            if (!$updated) {
                return ServiceResponse::error('Erreur lors de la vérification de l\'email');
            }

            return ServiceResponse::success('Email vérifié avec succès');

        } catch (\Exception $e) {
            error_log("Erreur vérification email: " . $e->getMessage());
            return ServiceResponse::error('Erreur interne du serveur');
        }
    }

    /**
     * Préparer les données utilisateur pour la création
     */
    private function prepareUserDataForCreation(array $data): array
    {
        $userData = $data;
        $userData['password_hash'] = $this->authService->hashPassword($data['password']);
        unset($userData['password']);
        
        return $userData;
    }

    /**
     * Préparer les données utilisateur pour la mise à jour
     */
    private function prepareUserDataForUpdate(array $data): array
    {
        if (isset($data['password'])) {
            $data['password_hash'] = $this->authService->hashPassword($data['password']);
            unset($data['password']);
        }
        
        return $data;
    }

    /**
     * Générer les tokens pour un utilisateur
     */
    private function generateUserTokens(User $user): array
    {
        return [
            'access_token' => $this->authService->generateToken(
                $user->getId(),
                $user->getEmail(),
                $user->getUsername()
            ),
            'refresh_token' => $this->authService->generateRefreshToken($user->getId()),
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ];
    }

    /**
     * Récupérer l'utilisateur authentifié
     */
    private function getAuthenticatedUser(): ?User
    {
        $userId = $this->authService->getUserIdFromToken();
        return $userId ? $this->userModel->findById($userId) : null;
    }
}