<?php
/**
 * Point d'entrée principal de l'API Finance Flow
 * 
 * Ce fichier configure l'environnement, initialise l'autoloader Composer,
 * configure CORS et démarre le routeur pour gérer les requêtes API.
 */

// Headers de sécurité et CORS pour le développement
header('Content-Type: application/json; charset=utf-8');

// Configuration d'erreur pour le développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Chargement de la configuration d'environnement
require_once __DIR__ . '/../config/env.php';

// Inclusion de l'autoloader Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Chargement des classes core
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Response.php';
require_once __DIR__ . '/../app/core/Router.php';

// Chargement des modèles
require_once __DIR__ . '/../app/models/User.php';

// Chargement des services
require_once __DIR__ . '/../app/services/Repository.php';
require_once __DIR__ . '/../app/services/UserRepository.php';
require_once __DIR__ . '/../app/services/ValidationService.php';
require_once __DIR__ . '/../app/services/AuthService.php';
require_once __DIR__ . '/../app/services/UserService.php';

// Chargement des DTOs
require_once __DIR__ . '/../app/DTOs/User/CreateUserRequest.php';
require_once __DIR__ . '/../app/DTOs/User/UpdateUserRequest.php';
require_once __DIR__ . '/../app/DTOs/User/UserResponse.php';

// Chargement des middlewares
require_once __DIR__ . '/../app/Middleware/AuthMiddleware.php';

// Chargement des contrôleurs
require_once __DIR__ . '/../app/controllers/UserController.php';

use FinanceFlow\Core\Router;
use FinanceFlow\Core\Response;

try {
    // Activation de CORS pour le développement avec Vite
    Router::enableCors('http://localhost:5173');
    
    // Initialisation du routeur
    $router = new Router();
    
    // Chargement des routes depuis le fichier de configuration
    $router->loadRoutes(__DIR__ . '/../routes/api.php');
    
    // Démarrage du routeur
    $router->run();
    
} catch (Exception $e) {
    // Gestion des erreurs globales
    error_log("Erreur dans index.php: " . $e->getMessage());
    Response::error('Erreur serveur interne', 500);
} catch (Throwable $e) {
    // Gestion des erreurs fatales
    error_log("Erreur fatale dans index.php: " . $e->getMessage());
    Response::error('Erreur serveur critique', 500);
}