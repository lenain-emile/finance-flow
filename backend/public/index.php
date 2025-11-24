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

// Inclusion de l'autoloader Composer (PSR-4 autoloading gère toutes les classes)
require_once __DIR__ . '/../vendor/autoload.php';

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