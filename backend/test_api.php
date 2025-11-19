<?php
/**
 * Test simple de l'API Finance Flow
 * Accédez à ce fichier via : http://localhost/finance-flow/backend/test_api.php
 */

// Headers de sécurité et CORS
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: http://localhost:5173");
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Chargement de la configuration
require_once __DIR__ . '/config/env.php';

// Test de la base de données
try {
    require_once __DIR__ . '/app/core/Database.php';
    
    // Utiliser le namespace complet
    $db = \FinanceFlow\Core\Database::getInstance();
    $connection = $db->getConnection();
    
    // Test de connexion
    $stmt = $connection->query('SELECT 1 as test');
    $result = $stmt->fetch();
    
    $response = [
        'success' => true,
        'message' => 'API Finance Flow est opérationnelle',
        'data' => [
            'database_connection' => 'OK',
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'jwt_secret_configured' => !empty($_ENV['JWT_SECRET']),
            'test_query_result' => $result
        ]
    ];
    
    // Test des tables
    try {
        $stmt = $connection->query('DESCRIBE user');
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $response['data']['user_table'] = [
            'exists' => true,
            'columns' => $columns
        ];
    } catch (Exception $e) {
        $response['data']['user_table'] = [
            'exists' => false,
            'error' => $e->getMessage(),
            'solution' => 'Créez la table user dans votre base de données'
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Erreur de connexion à la base de données',
        'error' => $e->getMessage(),
        'solution' => 'Vérifiez les paramètres de connexion dans config/database.php'
    ];
}

// Test des fichiers requis
$requiredFiles = [
    'app/core/Database.php' => 'Core Database',
    'app/core/Response.php' => 'Core Response',
    'app/core/Router.php' => 'Core Router',
    'app/models/User.php' => 'User Model',
    'app/services/AuthService.php' => 'Auth Service',
    'app/controllers/UserController.php' => 'User Controller'
];

$filesStatus = [];
foreach ($requiredFiles as $file => $description) {
    $filesStatus[$description] = file_exists(__DIR__ . '/' . $file) ? 'OK' : 'MISSING';
}

$response['data']['required_files'] = $filesStatus;

// Routes de test disponibles
$response['data']['available_test_routes'] = [
    'GET /test' => 'Cette page',
    'POST /api/users/register' => 'Inscription utilisateur',
    'POST /api/users/login' => 'Connexion utilisateur',
    'GET /api/users/me' => 'Profil utilisateur (nécessite authentification)',
    'GET /api/users/check-email/{email}' => 'Vérifier disponibilité email',
    'GET /api/users/check-username/{username}' => 'Vérifier disponibilité nom d\'utilisateur'
];

// Instructions de test
$response['data']['test_instructions'] = [
    '1. Vérifiez que la base de données est connectée',
    '2. Créez la table user dans votre base de données',
    '3. Testez l\'inscription via le frontend ou avec curl',
    '4. Testez la connexion avec les identifiants créés'
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);