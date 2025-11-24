<?php

namespace FinanceFlow\Services;

use FinanceFlow\Models\User;
use FinanceFlow\Repositories\UserRepository;
use FinanceFlow\Services\{AuthService, ValidationService};
use FinanceFlow\DTOs\User\{CreateUserRequest, UpdateUserRequest, UserResponse};

class UserService
{
    private AuthService $authService;
    private UserRepository $userRepository;
    private ValidationService $validator;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->userRepository = new UserRepository();
        $this->validator = new ValidationService();
    }

    /**
     * Créer un nouvel utilisateur
     */
    public function createUser(CreateUserRequest $request): array
    {
        try {
            // Validation du DTO
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new \Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Validation métier approfondie
            $businessValidation = $this->validator->validateUserRegistration($request->toArray());
            if (!$businessValidation['valid']) {
                throw new \Exception('Erreurs de validation: ' . implode(', ', $businessValidation['errors']), 422);
            }

            // Créer l'utilisateur
            $userData = $this->prepareUserDataForCreation($request->toArray());
            $userId = $this->userRepository->create($userData);

            if (!$userId) {
                throw new \Exception('Erreur lors de la création de l\'utilisateur', 500);
            }

            // Récupérer l'utilisateur créé et créer la réponse
            $user = $this->userRepository->findUserObjectById($userId);
            if (!$user) {
                throw new \Exception('Utilisateur créé mais impossible de le récupérer', 500);
            }
            
            $userResponse = UserResponse::fromUser($user);

            // Temporairement désactiver le token de vérification
            // $verificationToken = $this->authService->generateEmailVerificationToken(
            //     $user->getId(),
            //     $user->getEmail()
            // );

            return [
                'user' => $userResponse->toArray(),
                // 'email_verification_token' => $verificationToken
            ];

        } catch (\Exception $e) {
            error_log("Erreur création utilisateur: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Authentifier un utilisateur
     */
    public function loginUser(string $email, string $password): UserResponse
    {
        try {
            $user = $this->userRepository->findUserObjectByEmail($email);

            if (!$user || !$this->authService->verifyPassword($password, $user->getPasswordHash())) {
                throw new \Exception('Email ou mot de passe incorrect', 401);
            }

            $userResponse = UserResponse::fromUser($user);
            $tokens = $this->generateUserTokens($user);
            $userResponse->setTokens($tokens);

            return $userResponse;

        } catch (\Exception $e) {
            error_log("Erreur connexion utilisateur: " . $e->getMessage());
            throw $e;
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
                throw new \Exception('Token de rafraîchissement invalide', 401);
            }

            $user = $this->userRepository->findUserObjectById($decoded->user_id);
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé', 404);
            }

            $accessToken = $this->authService->generateToken(
                $user->getId(),
                $user->getEmail(),
                $user->getUsername()
            );

            return [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ];

        } catch (\Exception $e) {
            error_log("Erreur rafraîchissement token: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtenir le profil de l'utilisateur actuel
     */
    public function getCurrentUser(int $userId): UserResponse
    {
        try {
            $user = $this->userRepository->findUserObjectById($userId);
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé', 404);
            }

            return UserResponse::fromUser($user);

        } catch (\Exception $e) {
            error_log("Erreur récupération profil: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour le profil utilisateur
     */
    public function updateUserProfile(UpdateUserRequest $request, int $userId): UserResponse
    {
        try {
            // Validation du DTO
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new \Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Vérifier que l'utilisateur a des données à mettre à jour
            if (!$request->hasUpdates()) {
                throw new \Exception('Aucune donnée à mettre à jour', 400);
            }

            // Validation métier approfondie avec ValidationService
            $businessValidation = $this->validator->validateUserUpdate($request->toArray(), $userId);
            if (!$businessValidation['valid']) {
                throw new \Exception('Erreurs de validation: ' . implode(', ', $businessValidation['errors']), 422);
            }

            $updateData = $this->prepareUserDataForUpdate($request->toArray());
            $updated = $this->userRepository->update($userId, $updateData);

            if (!$updated) {
                throw new \Exception('Erreur lors de la mise à jour du profil', 500);
            }

            $updatedUser = $this->userRepository->findUserObjectById($userId);
            return UserResponse::fromUser($updatedUser);

        } catch (\Exception $e) {
            error_log("Erreur mise à jour profil: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Vérifier l'email d'un utilisateur
     */
    public function verifyEmail(string $token): bool
    {
        try {
            $decoded = $this->authService->verifyEmailVerificationToken($token);
            if (!$decoded) {
                throw new \Exception('Token de vérification invalide ou expiré', 400);
            }

            $user = $this->userRepository->findUserObjectById($decoded->user_id);
            if (!$user) {
                throw new \Exception('Utilisateur non trouvé', 404);
            }

            if ($user->isVerified()) {
                return true; // Déjà vérifié, pas d'erreur
            }

            $updated = $this->userRepository->update($user->getId(), ['is_verified' => 1]);
            if (!$updated) {
                throw new \Exception('Erreur lors de la vérification de l\'email', 500);
            }

            return true;

        } catch (\Exception $e) {
            error_log("Erreur vérification email: " . $e->getMessage());
            throw $e;
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
        return $userId ? $this->userRepository->findUserObjectById($userId) : null;
    }
}