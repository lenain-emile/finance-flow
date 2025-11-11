<?php
/**
 * Point d'entrée de l'API Finance Flow
 * 
 * Ce fichier initialise l'application et gère le routage
 */

// Gestion des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactiver en production

// Démarrer la session
session_start();

// Autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Charger les classes principales
use FinanceFlow\Core\Router;
use FinanceFlow\Core\Response;
use FinanceFlow\Core\Database;

try {
    // Configuration CORS pour le développement
    Router::enableCors('http://localhost:5173');
    
    // Définir les en-têtes de sécurité
    Response::setSecurityHeaders();
    
    // Créer le routeur
    $router = new Router();
    
    // Routes de base pour tester l'API
    $router->get('/api', function() {
        Response::success([
            'name' => 'Finance Flow API',
            'version' => '1.0.0',
            'status' => 'running'
        ], 'API Finance Flow opérationnelle');
    });
    
    $router->get('/api/health', function() {
        $dbStatus = false;
        try {
            $db = Database::getInstance();
            $dbStatus = $db->testConnection();
        } catch (Exception $e) {
            // La base peut ne pas être configurée
        }
        
        Response::success([
            'api' => 'running',
            'database' => $dbStatus ? 'connected' : 'disconnected',
            'timestamp' => date('Y-m-d H:i:s')
        ], 'Health check');
    });
    
    // Route pour les erreurs 404 de l'API
    $router->get('/api/*', function() {
        Response::notFound('Endpoint API non trouvé');
    });
    
    // Charger les routes depuis le fichier de routes (si il existe)
    $routeFile = __DIR__ . '/../routes/api.php';
    if (file_exists($routeFile)) {
        $router->loadRoutes($routeFile);
    }
    
    // Exécuter le routeur
    $router->run();
    
} catch (Exception $e) {
    // Gestion des erreurs globales
    if (Response::expectsJson()) {
        Response::serverError('Une erreur inattendue s\'est produite');
    } else {
        http_response_code(500);
        echo "Erreur 500 - Erreur interne du serveur";
    }
    
    // Log l'erreur (en production)
    error_log("Finance Flow API Error: " . $e->getMessage());
}