<?php

namespace FinanceFlow\Controllers;

use FinanceFlow\Core\Response;
use FinanceFlow\Core\Database;
use FinanceFlow\Middleware\AuthMiddleware;
use PDO;

/**
 * Contrôleur pour la gestion des catégories
 */
class CategoryController
{
    private AuthMiddleware $authMiddleware;
    private PDO $pdo;

    public function __construct()
    {
        $this->authMiddleware = new AuthMiddleware();
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Récupérer les catégories de dépenses uniquement (id 1-10)
     * GET /api/categories/expenses
     */
    public function expenses(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $sql = "SELECT id, name FROM category WHERE id <= 10 ORDER BY name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success([
                'categories' => $categories,
                'count' => count($categories)
            ]);

        } catch (\Exception $e) {
            error_log("Erreur récupération catégories dépenses: " . $e->getMessage());
            Response::error('Erreur lors de la récupération des catégories', 500);
        }
    }

    /**
     * Récupérer les catégories de revenus uniquement (id 11-15)
     * GET /api/categories/incomes
     */
    public function incomes(): void
    {
        if (!$this->authMiddleware->authenticate()) {
            return;
        }

        try {
            $sql = "SELECT id, name FROM category WHERE id >= 11 AND id <= 15 ORDER BY name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

            Response::success([
                'categories' => $categories,
                'count' => count($categories)
            ]);

        } catch (\Exception $e) {
            error_log("Erreur récupération catégories revenus: " . $e->getMessage());
            Response::error('Erreur lors de la récupération des catégories', 500);
        }
    }
}
