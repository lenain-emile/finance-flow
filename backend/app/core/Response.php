<?php
namespace FinanceFlow\Core;

/**
 * Classe utilitaire pour gérer les réponses HTTP
 */
class Response {
    
    /**
     * Envoyer une réponse JSON
     */
    public static function json(array $data, int $statusCode = 200, array $headers = []): void {
        // Définir le code de statut HTTP
        http_response_code($statusCode);
        
        // Définir les en-têtes par défaut
        header('Content-Type: application/json; charset=utf-8');
        
        // Ajouter les en-têtes personnalisés
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        // Encoder et envoyer les données JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Envoyer une réponse de succès standardisée
     */
    public static function success(mixed $data = null, string $message = 'Succès', int $statusCode = 200): void {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        self::json($response, $statusCode);
    }

    /**
     * Envoyer une réponse d'erreur standardisée
     */
    public static function error(string $message = 'Erreur', mixed $errors = null, int $statusCode = 400): void {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        self::json($response, $statusCode);
    }

    /**
     * Envoyer une réponse 404
     */
    public static function notFound(string $message = 'Ressource non trouvée'): void {
        self::error($message, null, 404);
    }

    /**
     * Envoyer une réponse 401 (Non autorisé)
     */
    public static function unauthorized(string $message = 'Accès non autorisé'): void {
        self::error($message, null, 401);
    }

    /**
     * Envoyer une réponse 403 (Interdit)
     */
    public static function forbidden(string $message = 'Accès interdit'): void {
        self::error($message, null, 403);
    }

    /**
     * Envoyer une réponse 422 (Erreur de validation)
     */
    public static function validationError(array $errors, string $message = 'Données invalides'): void {
        self::error($message, $errors, 422);
    }

    /**
     * Envoyer une réponse 500 (Erreur serveur)
     */
    public static function serverError(string $message = 'Erreur interne du serveur'): void {
        self::error($message, null, 500);
    }

    /**
     * Redirection HTTP
     */
    public static function redirect(string $url, int $statusCode = 302): void {
        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Envoyer une réponse HTML simple
     */
    public static function html(string $content, int $statusCode = 200, array $headers = []): void {
        http_response_code($statusCode);
        
        // Définir les en-têtes par défaut
        header('Content-Type: text/html; charset=utf-8');
        
        // Ajouter les en-têtes personnalisés
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        echo $content;
        exit;
    }

    /**
     * Envoyer une réponse de texte brut
     */
    public static function text(string $content, int $statusCode = 200, array $headers = []): void {
        http_response_code($statusCode);
        
        // Définir les en-têtes par défaut
        header('Content-Type: text/plain; charset=utf-8');
        
        // Ajouter les en-têtes personnalisés
        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
        
        echo $content;
        exit;
    }

    /**
     * Vérifier si la requête attend du JSON
     */
    public static function expectsJson(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return str_contains($accept, 'application/json') || 
               str_contains($accept, 'text/json') ||
               isset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    /**
     * Obtenir le code de statut HTTP actuel
     */
    public static function getStatusCode(): int {
        return http_response_code();
    }

    /**
     * Définir des en-têtes de sécurité basiques
     */
    public static function setSecurityHeaders(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }
}