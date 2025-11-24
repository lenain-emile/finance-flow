<?php

namespace FinanceFlow\Middleware;

use FinanceFlow\Services\AuthService;
use FinanceFlow\Core\Response;

/**
 * Middleware d'authentification pour l'API
 * Gère la validation des tokens JWT et la protection des routes
 */
class AuthMiddleware
{
    private AuthService $authService;
    private static ?int $currentUserId = null;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Authentifier la requête avec JWT
     * @return bool
     */
    public function authenticate(): bool
    {
        $token = $this->authService->extractTokenFromHeader();
        
        if (!$token) {
            Response::error('Token d\'accès requis', 401);
            return false;
        }

        $decoded = $this->authService->verifyToken($token);
        
        if (!$decoded) {
            Response::error('Token invalide ou expiré', 401);
            return false;
        }

        // Stocker l'ID utilisateur pour utilisation ultérieure
        self::$currentUserId = $decoded->user_id;
        
        return true;
    }

    /**
     * Obtenir l'ID de l'utilisateur authentifié
     * @return int|null
     */
    public static function getCurrentUserId(): ?int
    {
        return self::$currentUserId;
    }

    /**
     * Validation du JSON d'entrée avec gestion d'erreurs
     * @return array|null
     */
    public static function validateJsonInput(): ?array
    {
        // Vérifier le Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            Response::error('Content-Type doit être application/json', 400);
            return null;
        }

        // Lire et décoder le JSON
        $rawInput = file_get_contents('php://input');
        
        if (empty($rawInput)) {
            Response::error('Corps de requête vide', 400);
            return null;
        }

        $data = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('JSON invalide: ' . json_last_error_msg(), 400);
            return null;
        }

        return $data;
    }

    /**
     * Rate limiting simple basé sur IP
     * Utilise un fichier temporaire pour éviter les sessions dans une API REST
     * @param int $maxAttempts Nombre maximum de tentatives
     * @param int $windowMinutes Fenêtre de temps en minutes
     * @return bool
     */
    public static function rateLimit(int $maxAttempts = 10, int $windowMinutes = 1): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = md5($ip);
        $rateLimitFile = sys_get_temp_dir() . '/finance_flow_rate_limit_' . $key . '.json';
        
        $now = time();
        $window = $windowMinutes * 60;
        
        // Charger les tentatives existantes
        $attempts = [];
        if (file_exists($rateLimitFile)) {
            $content = file_get_contents($rateLimitFile);
            $attempts = json_decode($content, true) ?: [];
        }
        
        // Nettoyer les tentatives anciennes
        $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        // Vérifier le nombre de tentatives
        if (count($attempts) >= $maxAttempts) {
            $retryAfter = $window - ($now - min($attempts));
            header("Retry-After: {$retryAfter}");
            Response::error(
                "Trop de tentatives. Réessayez dans {$retryAfter} secondes.",
                null,
                429
            );
            return false;
        }
        
        // Ajouter la tentative actuelle et sauvegarder
        $attempts[] = $now;
        file_put_contents($rateLimitFile, json_encode($attempts));
        
        return true;
    }

    /**
     * Vérification CORS simple pour les requêtes preflight
     */
    public static function handleCorsPreFlight(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            header('Access-Control-Allow-Origin: http://localhost:5173');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
            exit();
        }
        return false;
    }

    /**
     * Validation des permissions utilisateur
     * @param array $requiredRoles Rôles requis
     * @return bool
     */
    public function checkPermissions(array $requiredRoles = []): bool
    {
        if (!$this->authenticate()) {
            return false;
        }

        // Si aucun rôle spécifique n'est requis, l'authentification suffit
        if (empty($requiredRoles)) {
            return true;
        }

        // Ici, vous pourriez implémenter la logique des rôles
        // En récupérant les rôles de l'utilisateur depuis la base de données
        
        return true; // Simplifié pour le moment
    }

    /**
     * Middleware pour les routes admin
     * @return bool
     */
    public function requireAdmin(): bool
    {
        return $this->checkPermissions(['admin']);
    }

    /**
     * Validation de l'origine de la requête (protection CSRF basique)
     */
    public static function validateOrigin(): bool
    {
        $allowedOrigins = [
            'http://localhost:5173', // Vite dev server
            'http://localhost:3000', // React dev server alternatif
            'https://votre-domaine.com' // Production
        ];

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (!in_array($origin, $allowedOrigins)) {
            Response::error('Origine non autorisée', 403);
            return false;
        }

        return true;
    }

    /**
     * Nettoyage de sécurité pour les données d'entrée
     * @param array $data
     * @return array
     */
    public static function sanitizeInput(array $data): array
    {
        return array_map(function($value) {
            if (is_string($value)) {
                // Supprimer les espaces en début/fin
                $value = trim($value);
                // Échapper les caractères HTML
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            return $value;
        }, $data);
    }
}