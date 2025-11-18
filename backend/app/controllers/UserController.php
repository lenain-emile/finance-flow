<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Services\UserService;
use FinanceFlow\Services\UserRepository;
use FinanceFlow\Services\AuthService;
use FinanceFlow\Middleware\AuthMiddleware;
use FinanceFlow\Core\Response;
use FinanceFlow\DTOs\User\{CreateUserRequest, UpdateUserRequest};

class UserController
{
    private UserService $userService;
    private UserRepository $userRepository;
    private AuthService $authService;
    private AuthMiddleware $authMiddleware;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->userRepository = new UserRepository();
        $this->authService = new AuthService();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * POST /api/users/register
     * Inscription d'un nouvel utilisateur
     */
    public function register(): void
    {
        try {
            // Validation du JSON
            $data = AuthMiddleware::validateJsonInput();
            if ($data === null) {
                return;
            }

            if (!AuthMiddleware::rateLimit(10, 1)) { 
                return;
            }

            // Créer le DTO depuis les données reçues
            $createRequest = CreateUserRequest::fromArray($data);
            $result = $this->userService->createUser($createRequest);
            Response::success($result, 'Utilisateur créé avec succès', 201);
        } catch (\Exception $e) {
            error_log("Erreur registration: " . $e->getMessage() . " - " . $e->getTraceAsString());
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * POST /api/users/login
     * Connexion d'un utilisateur
     */
    public function login(): void
    {
        // Validation du JSON
        $data = AuthMiddleware::validateJsonInput();
        if ($data === null) {
            return;
        }

        // Rate limiting plus strict pour les connexions
        if (!AuthMiddleware::rateLimit(5, 1)) { // 5 tentatives par minute
            return;
        }

        // Validation des champs requis
        if (empty($data['email']) || empty($data['password'])) {
            Response::error('Email et mot de passe requis', 400);
            return;
        }

        try {
            $userResponse = $this->userService->loginUser($data['email'], $data['password']);
            Response::success($userResponse->toArray(), 'Connexion réussie');
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * POST /api/users/refresh-token
     * Rafraîchir le token d'accès
     */
    public function refreshToken(): void
    {
        // Validation du JSON
        $data = AuthMiddleware::validateJsonInput();
        if ($data === null) {
            return;
        }

        if (empty($data['refresh_token'])) {
            Response::error('Token de rafraîchissement requis', 400);
            return;
        }

        try {
            $tokenData = $this->userService->refreshToken($data['refresh_token']);
            Response::success($tokenData, 'Token rafraîchi avec succès');
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * GET /api/users/me
     * Obtenir le profil de l'utilisateur actuel
     */
    public function getProfile(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $userResponse = $this->userService->getCurrentUser($userId);
            Response::success($userResponse->toArray(), 'Profil récupéré avec succès');
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * PUT /api/users/me
     * Mettre à jour le profil de l'utilisateur actuel
     */
    public function updateProfile(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        // Validation du JSON
        $data = AuthMiddleware::validateJsonInput();
        if ($data === null) {
            return;
        }

        try {
            $userId = AuthMiddleware::getCurrentUserId();
            $updateRequest = UpdateUserRequest::fromArray($data);
            $result = $this->userService->updateUserProfile($updateRequest, $userId);

            Response::success($result->toArray(), "Profil mis à jour avec succès");
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * POST /api/users/verify-email
     * Vérifier l'email d'un utilisateur
     */
    public function verifyEmail(): void
    {
        // Validation du JSON
        $data = AuthMiddleware::validateJsonInput();
        if ($data === null) {
            return;
        }

        if (empty($data['token'])) {
            Response::error('Token de vérification requis', 400);
            return;
        }

        try {
            $this->userService->verifyEmail($data['token']);
            Response::success(null, 'Email vérifié avec succès');
        } catch (\Exception $e) {
            $statusCode = $e->getCode() ?: 500;
            Response::error($e->getMessage(), $statusCode);
        }
    }

    /**
     * POST /api/users/logout
     * Déconnexion (côté client principalement)
     */
    public function logout(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        // Note: Avec JWT, la déconnexion est principalement côté client
        // Le client doit supprimer le token de son stockage local
        // Ici on pourrait ajouter le token à une blacklist si nécessaire

        Response::success(null, 'Déconnexion réussie');
    }

    /**
     * GET /api/users/check-username/{username}
     * Vérifier si un nom d'utilisateur est disponible
     */
    public function checkUsernameAvailability(string $username): void
    {
        if (strlen($username) < 3) {
            Response::error('Le nom d\'utilisateur doit contenir au moins 3 caractères', 400);
            return;
        }

        $exists = $this->userRepository->usernameExists($username);

        Response::success([
            'username' => $username,
            'available' => !$exists
        ], $exists ? 'Nom d\'utilisateur non disponible' : 'Nom d\'utilisateur disponible');
    }

    /**
     * GET /api/users/check-email/{email}
     * Vérifier si un email est disponible
     */
    public function checkEmailAvailability(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Format d\'email invalide', 400);
            return;
        }

        $exists = $this->userRepository->emailExists($email);

        Response::success([
            'email' => $email,
            'available' => !$exists
        ], $exists ? 'Email non disponible' : 'Email disponible');
    }

    /**
     * DELETE /api/users/me
     * Supprimer le compte utilisateur (soft delete)
     */
    public function deleteAccount(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        // Validation du JSON pour le mot de passe de confirmation
        $data = AuthMiddleware::validateJsonInput();
        if ($data === null) {
            return;
        }

        if (empty($data['password'])) {
            Response::error('Mot de passe requis pour supprimer le compte', 400);
            return;
        }

        $userId = AuthMiddleware::getCurrentUserId();
        $user = $this->userRepository->findUserObjectById($userId);

        if (!$user) {
            Response::error('Utilisateur non trouvé', 404);
            return;
        }

        // Vérifier le mot de passe
        if (!$this->authService->verifyPassword($data['password'], $user->getPasswordHash())) {
            Response::error('Mot de passe incorrect', 401);
            return;
        }

        // Supprimer le compte (soft delete)
        if ($this->userRepository->delete($userId)) {
            Response::success(null, 'Compte supprimé avec succès');
        } else {
            Response::error('Erreur lors de la suppression du compte', 500);
        }
    }

    /**
     * Méthode pour gérer les routes non trouvées
     */
    public function notFound(): void
    {
        Response::error('Endpoint non trouvé', 404);
    }
}




// entities 
// models 
// interfaces 
// DTO GET / POST / PUT 3 fichiers  