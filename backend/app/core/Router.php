<?php
namespace FinanceFlow\Core;

use FinanceFlow\Core\Response;

/**
 * Routeur simple pour l'API REST
 */
class Router {
    private array $routes = [];
    private string $basePath = '';

    /**
     * Constructeur
     */
    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Ajouter une route GET
     */
    public function get(string $path, callable|string $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Ajouter une route POST
     */
    public function post(string $path, callable|string $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Ajouter une route PUT
     */
    public function put(string $path, callable|string $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Ajouter une route DELETE
     */
    public function delete(string $path, callable|string $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Ajouter une route PATCH
     */
    public function patch(string $path, callable|string $handler): void {
        $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Ajouter une route pour tous les verbes HTTP
     */
    public function any(string $path, callable|string $handler): void {
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        foreach ($methods as $method) {
            $this->addRoute($method, $path, $handler);
        }
    }

    /**
     * Ajouter une route
     */
    private function addRoute(string $method, string $path, callable|string $handler): void {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->basePath . $path,
            'handler' => $handler
        ];
    }

    /**
     * Exécuter le routeur
     */
    public function run(): void {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Gérer les requêtes OPTIONS (CORS preflight)
        if ($requestMethod === 'OPTIONS') {
            Response::json(['message' => 'OK'], 200);
            return;
        }

        // Nettoyer l'URI (enlever les paramètres GET)
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        
        // Debug: afficher les informations pour diagnostiquer
        error_log("Original REQUEST_URI: " . $requestUri);
        error_log("Parsed path: " . $requestPath);
        error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NULL'));
        
        // Enlever le préfixe du chemin si on accède via un sous-dossier
        // Méthode 1: Utiliser PATH_INFO si disponible
        if (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            $requestPath = $_SERVER['PATH_INFO'];
            error_log("Using PATH_INFO: " . $requestPath);
        } else {
            // Méthode 2: Calculer manuellement
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            $scriptDir = dirname($scriptName);
            
            // Si le chemin commence par le répertoire du script, l'enlever
            if ($scriptDir !== '/' && strpos($requestPath, $scriptDir) === 0) {
                $requestPath = substr($requestPath, strlen($scriptDir));
            }
            
            // Enlever index.php du chemin si présent
            if (strpos($requestPath, '/index.php') === 0) {
                $requestPath = substr($requestPath, strlen('/index.php'));
            }
            
            error_log("Calculated path: " . $requestPath);
        }
        
        // S'assurer que le chemin commence par /
        if (empty($requestPath) || strpos($requestPath, '/') !== 0) {
            $requestPath = '/' . ltrim($requestPath, '/');
        }
        
        error_log("Final processed path: " . $requestPath);

        // Chercher une route correspondante
        foreach ($this->routes as $route) {
            error_log("Checking route: " . $route['path'] . " against: " . $requestPath);
            if ($route['method'] === $requestMethod && $this->matchPath($route['path'], $requestPath)) {
                $params = $this->extractParams($route['path'], $requestPath);
                $this->callHandler($route['handler'], $params);
                return;
            }
        }

        // Aucune route trouvée
        $this->notFound();
    }

    /**
     * Vérifier si le chemin correspond (supporte les paramètres)
     */
    private function matchPath(string $routePath, string $requestPath): bool {
        // Convertir la route en regex pour supporter les paramètres {id}, {slug}, etc.
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        return (bool) preg_match($pattern, $requestPath);
    }

    /**
     * Extraire les paramètres de l'URL
     */
    private function extractParams(string $routePath, string $requestPath): array {
        $params = [];
        
        // Extraire les noms des paramètres
        preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
        
        // Convertir la route en regex et capturer les valeurs
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $requestPath, $matches)) {
            array_shift($matches); // Enlever le match complet
            
            foreach ($paramNames[1] as $index => $name) {
                $params[$name] = $matches[$index] ?? null;
            }
        }
        
        return $params;
    }

    /**
     * Appeler le gestionnaire de route
     */
    private function callHandler(callable|string $handler, array $params = []): void {
        try {
            if (is_callable($handler)) {
                // Fonction anonyme ou callable
                $handler($params);
            } elseif (is_string($handler) && str_contains($handler, '@')) {
                // Format "Controller@method"
                [$controllerName, $methodName] = explode('@', $handler);
                $controllerClass = "FinanceFlow\\Controllers\\{$controllerName}";
                
                if (class_exists($controllerClass)) {
                    $controller = new $controllerClass();
                    if (method_exists($controller, $methodName)) {
                        $controller->$methodName($params);
                    } else {
                        Response::json(['error' => 'Méthode non trouvée'], 500);
                    }
                } else {
                    Response::json(['error' => 'Contrôleur non trouvé'], 500);
                }
            } else {
                Response::json(['error' => 'Handler invalide'], 500);
            }
        } catch (\Exception $e) {
            Response::json(['error' => 'Erreur serveur', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Réponse 404
     */
    private function notFound(): void {
        Response::json([
            'error' => 'Route non trouvée',
            'method' => $_SERVER['REQUEST_METHOD'],
            'path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        ], 404);
    }

    /**
     * Définir les en-têtes CORS pour le développement
     */
    public static function enableCors(string $origin = 'http://localhost:5173'): void {
        header("Access-Control-Allow-Origin: {$origin}");
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24h
    }

    /**
     * Charger les routes depuis un fichier
     */
    public function loadRoutes(string $routeFile): void {
        if (file_exists($routeFile)) {
            $router = $this;
            require $routeFile;
        }
    }
}