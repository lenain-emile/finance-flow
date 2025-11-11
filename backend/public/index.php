<?php
/**
 * Point d'entrée de l'API Finance Flow
 * 
 * Ce fichier initialise l'application et gère le routage
 */

// Autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger la configuration d'environnement
require_once __DIR__ . '/../config/env.php';

// Démarrer la session
session_start();

// Charger les classes principales
use FinanceFlow\Core\Router;
use FinanceFlow\Core\Response;
use FinanceFlow\Core\Database;
use FinanceFlow\Middleware\AuthMiddleware;

try {
    // Configuration CORS et middlewares
    AuthMiddleware::handleCors();
    
    // Logging des requêtes (en développement)
    if ($_ENV['APP_DEBUG']) {
        AuthMiddleware::logRequest();
    }
    
    // Définir les en-têtes de sécurité
    Response::setSecurityHeaders();
    
    // Créer le routeur
    $router = new Router();
    
    // Routes de base pour tester l'API
    $router->get('/api', function() {
        Response::success([
            'name' => $_ENV['APP_NAME'],
            'version' => '1.0.0',
            'status' => 'running',
            'environment' => $_ENV['APP_ENV'],
            'timestamp' => date('Y-m-d H:i:s')
        ], 'API Finance Flow opérationnelle');
    });
    
    $router->get('/api/health', function() {
        $dbStatus = false;
        $dbMessage = 'Non configuré';
        
        try {
            $db = Database::getInstance();
            $dbStatus = $db->testConnection();
            $dbMessage = $dbStatus ? 'Connexion réussie' : 'Connexion échouée';
        } catch (Exception $e) {
            $dbMessage = 'Erreur: ' . $e->getMessage();
        }
        
        Response::success([
            'api' => 'running',
            'database' => [
                'status' => $dbStatus ? 'connected' : 'disconnected',
                'message' => $dbMessage
            ],
            'environment' => $_ENV['APP_ENV'],
            'php_version' => phpversion(),
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
        ], 'Health check');
    });
    
    // Route pour tester l'authentification
    $router->get('/api/auth/test', function() {
        $authMiddleware = new AuthMiddleware();
        if ($authMiddleware->authenticate()) {
            Response::success([
                'user_id' => AuthMiddleware::getCurrentUserId(),
                'email' => AuthMiddleware::getCurrentUserEmail(),
                'username' => AuthMiddleware::getCurrentUsername(),
                'timestamp' => date('Y-m-d H:i:s')
            ], 'Authentification réussie');
        }
    });
    
    // Charger les routes depuis le fichier de routes
    $routeFile = __DIR__ . '/../routes/api.php';
    if (file_exists($routeFile)) {
        $router->loadRoutes($routeFile);
    }
    
    // Route pour les erreurs 404 de l'API
    $router->get('/api/*', function() {
        Response::notFound('Endpoint API non trouvé');
    });
    
    // Exécuter le routeur
    $router->run();
    
} catch (Exception $e) {
    // Gestion des erreurs globales
    if (Response::expectsJson()) {
        $errorData = null;
        
        // En développement, inclure les détails de l'erreur
        if ($_ENV['APP_DEBUG']) {
            $errorData = [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        Response::error('Une erreur inattendue s\'est produite', $errorData, 500);
    } else {
        http_response_code(500);
        echo "Erreur 500 - Erreur interne du serveur";
    }
    
    // Log l'erreur
    error_log("Finance Flow API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
}