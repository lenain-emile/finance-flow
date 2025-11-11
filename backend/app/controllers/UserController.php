<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Services\UserService;
use FinanceFlow\Middleware\AuthMiddleware;
use FinanceFlow\Core\Response;

class UserController
{
    private UserService $userService;
    private AuthMiddleware $authMiddleware;

    public function __construct()
    {
        $this->userService = new UserService();
        $this->authMiddleware = new AuthMiddleware();
    }

    /**
     * POST /api/users/register
     * Inscription d'un nouvel utilisateur
     */
    public function register(): void
    {
        // Validation du JSON
        $data = AuthMiddleware::validateJsonInput();
        if ($data === null) {
            return;
        }

        // Rate limiting
        if (!AuthMiddleware::rateLimit(10, 1)) { // 10 tentatives par minute
            return;
        }

        $result = $this->userService->createUser($data);

        if ($result['success']) {
            Response::success($result['data'], $result['message'], 201);
        } else {
            $statusCode = isset($result['errors']) ? 422 : 400;
            Response::error($result['message'], $statusCode, $result['errors'] ?? null);
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

        $result = $this->userService->loginUser($data['email'], $data['password']);

        if ($result['success']) {
            Response::success($result['data'], $result['message']);
        } else {
            Response::error($result['message'], 401);
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

        $result = $this->userService->refreshToken($data['refresh_token']);

        if ($result['success']) {
            Response::success($result['data'], $result['message']);
        } else {
            Response::error($result['message'], 401);
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

        $result = $this->userService->getCurrentUser();

        if ($result['success']) {
            Response::success($result['data'], $result['message']);
        } else {
            Response::error($result['message'], 404);
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

        $result = $this->userService->updateUserProfile($data);

        if ($result['success']) {
            Response::success($result['data'], $result['message']);
        } else {
            $statusCode = isset($result['errors']) ? 422 : 400;
            Response::error($result['message'], $statusCode, $result['errors'] ?? null);
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

        $result = $this->userService->verifyEmail($data['token']);

        if ($result['success']) {
            Response::success(null, $result['message']);
        } else {
            Response::error($result['message'], 400);
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

        $userModel = new \FinanceFlow\Models\User();
        $exists = $userModel->usernameExists($username);

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

        $userModel = new \FinanceFlow\Models\User();
        $exists = $userModel->emailExists($email);

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
        $userModel = new \FinanceFlow\Models\User();
        $user = $userModel->findById($userId);

        if (!$user) {
            Response::error('Utilisateur non trouvé', 404);
            return;
        }

        // Vérifier le mot de passe
        $authService = new \FinanceFlow\Services\AuthService();
        if (!$authService->verifyPassword($data['password'], $user->getPasswordHash())) {
            Response::error('Mot de passe incorrect', 401);
            return;
        }

        // Supprimer le compte (soft delete) - utiliser update() au lieu de delete()
        if ($user->update(['is_active' => 0])) {
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