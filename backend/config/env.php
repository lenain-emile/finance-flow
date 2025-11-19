<?php
/**
 * Configuration des variables d'environnement
 */

// Définir les variables d'environnement par défaut
$_ENV = $_ENV ?? [];

// Configuration JWT (ESSENTIELLE pour l'authentification)
$_ENV['JWT_SECRET'] = $_ENV['JWT_SECRET'] ?? 'finance-flow-secret-key-change-this-in-production-2024';

// Configuration de l'application
$_ENV['APP_ENV'] = $_ENV['APP_ENV'] ?? 'development';
$_ENV['APP_DEBUG'] = $_ENV['APP_DEBUG'] ?? 'true';

// Chargement du fichier .env si présent (pour la production)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Ignorer les commentaires
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Supprimer les guillemets si présents
        if (preg_match('/^".*"$/', $value)) {
            $value = substr($value, 1, -1);
        }
        
        $_ENV[$key] = $value;
    }
}
