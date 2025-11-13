<?php

namespace FinanceFlow\Middleware;

use FinanceFlow\Services\AuthService;
use FinanceFlow\Core\Response;

class AuthMiddleware
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Vérifier l'authentification
     */
    public function authenticate(): bool
    {
        $token = $this->authService->extractTokenFromHeader();
        
        if (!$token) {
            Response::json([
                'success' => false,
                'message' => 'Token manquant. Veuillez vous connecter.'
            ], 401);
            return false;
        }

        $decoded = $this->authService->verifyToken($token);
        
        if (!$decoded) {
            Response::json([
                'success' => false,
                'message' => 'Token invalide ou expiré. Veuillez vous reconnecter.'
            ], 401);
            return false;
        }

        // Stocker les informations de l'utilisateur dans la session/globals
        $GLOBALS['current_user_id'] = $decoded->user_id;
        $GLOBALS['current_user_email'] = $decoded->email;
        $GLOBALS['current_user_username'] = $decoded->username;

        return true;
    }

    /**
     * Middleware optionnel - n'interrompt pas le flux si pas de token
     */
    public function optionalAuth(): bool
    {
        $token = $this->authService->extractTokenFromHeader();
        
        if (!$token) {
            return true; // Continue sans authentification
        }

        $decoded = $this->authService->verifyToken($token);
        
        if ($decoded) {
            $GLOBALS['current_user_id'] = $decoded->user_id;
            $GLOBALS['current_user_email'] = $decoded->email;
            $GLOBALS['current_user_username'] = $decoded->username;
        }

        return true;
    }

    /**
     * Vérifier si l'utilisateur est vérifié
     */
    public function requireVerified(): bool
    {
        if (!$this->authenticate()) {
            return false;
        }

        
        
        return true;
    }

    /**
     * Vérifier les permissions admin (pour de futures fonctionnalités)
     */
    public function requireAdmin(): bool
    {
        if (!$this->authenticate()) {
            return false;
        }

        // Ici vous pourriez ajouter une vérification des rôles
        // Pour l'instant, on suppose que tous les utilisateurs authentifiés sont autorisés
        
        return true;
    }

    /**
     * Middleware CORS
     */
    public static function handleCors(): void
    {
        // Gérer les requêtes OPTIONS (preflight)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 86400'); // 24 heures
            http_response_code(204);
            exit;
        }

        // Headers CORS pour les autres requêtes
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    }

    /**
     * Middleware de limitation de taux (rate limiting)
     */
    public static function rateLimit(int $maxRequests = 60, int $windowMinutes = 1): bool
    {
        $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "rate_limit_" . md5($clientIp);
        
        // Ici vous pourriez utiliser Redis ou un système de cache
        // Pour l'instant, on utilise une approche simple avec des fichiers
        
        $cacheDir = sys_get_temp_dir() . '/finance-flow-cache';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        
        $cacheFile = $cacheDir . '/' . $key;
        $currentTime = time();
        $windowStart = $currentTime - ($windowMinutes * 60);
        
        $requests = [];
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data) {
                $requests = array_filter($data, function($timestamp) use ($windowStart) {
                    return $timestamp > $windowStart;
                });
            }
        }
        
        if (count($requests) >= $maxRequests) {
            Response::json([
                'success' => false,
                'message' => 'Trop de requêtes. Veuillez patienter avant de réessayer.',
                'retry_after' => $windowMinutes * 60
            ], 429);
            return false;
        }
        
        $requests[] = $currentTime;
        file_put_contents($cacheFile, json_encode($requests));
        
        return true;
    }

    /**
     * Middleware de validation des données JSON
     */
    public static function validateJsonInput(): ?array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') === false) {
            Response::json([
                'success' => false,
                'message' => 'Content-Type doit être application/json'
            ], 400);
            return null;
        }

        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::json([
                'success' => false,
                'message' => 'JSON invalide: ' . json_last_error_msg()
            ], 400);
            return null;
        }

        return $data;
    }

    /**
     * Middleware de logging des requêtes
     */
    public static function logRequest(): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $GLOBALS['current_user_id'] ?? null
        ];

        // Log dans un fichier (vous pourriez utiliser un logger plus sophistiqué)
        $logDir = dirname(__DIR__, 2) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        
        $logFile = $logDir . '/requests_' . date('Y-m-d') . '.log';
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }

    /**
     * Obtenir l'ID de l'utilisateur actuel
     */
    public static function getCurrentUserId(): ?int
    {
        return $GLOBALS['current_user_id'] ?? null;
    }

    /**
     * Obtenir l'email de l'utilisateur actuel
     */
    public static function getCurrentUserEmail(): ?string
    {
        return $GLOBALS['current_user_email'] ?? null;
    }

    /**
     * Obtenir le username de l'utilisateur actuel
     */
    public static function getCurrentUsername(): ?string
    {
        return $GLOBALS['current_user_username'] ?? null;
    }
}