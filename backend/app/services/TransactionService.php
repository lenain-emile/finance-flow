<?php

namespace FinanceFlow\Services;

use FinanceFlow\Services\{TransactionRepository, ValidationService};
use FinanceFlow\DTOs\Transaction\{CreateTransactionRequest, UpdateTransactionRequest, TransactionResponse};
use Exception;

/**
 * Service pour la gestion des transactions
 * Suit le même pattern que UserService
 */
class TransactionService
{
    private TransactionRepository $transactionRepository;
    private ValidationService $validator;

    public function __construct()
    {
        $this->transactionRepository = new TransactionRepository();
        $this->validator = new ValidationService();
    }

    /**
     * Créer une nouvelle transaction
     */
    public function createTransaction(CreateTransactionRequest $request, int $userId): array
    {
        try {
            // Le DTO a déjà sa propre validation, mais on peut ajouter des validations business ici
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Préparer les données depuis le DTO
            $transactionData = $this->prepareTransactionDataFromDTO($request, $userId);
            $transactionId = $this->transactionRepository->create($transactionData);

            if (!$transactionId) {
                throw new Exception('Erreur lors de la création de la transaction', 500);
            }

            // Récupérer la transaction créée
            $transaction = $this->transactionRepository->findByIdAndUserId($transactionId, $userId);
            if (!$transaction) {
                throw new Exception('Transaction créée mais impossible de la récupérer', 500);
            }

            return $transaction;

        } catch (Exception $e) {
            error_log("Erreur création transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les transactions d'un utilisateur
     */
    public function getUserTransactions(int $userId, ?int $limit = null, int $offset = 0): array
    {
        try {
            return $this->transactionRepository->getByUserId($userId, $limit, $offset);
        } catch (Exception $e) {
            error_log("Erreur récupération transactions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer une transaction par ID
     */
    public function getTransaction(int $id, int $userId): ?array
    {
        try {
            $transaction = $this->transactionRepository->findByIdAndUserId($id, $userId);
            if (!$transaction) {
                throw new Exception('Transaction non trouvée', 404);
            }
            return $transaction;
        } catch (Exception $e) {
            error_log("Erreur récupération transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mettre à jour une transaction
     */
    public function updateTransaction(int $id, UpdateTransactionRequest $request, int $userId): array
    {
        try {
            // Vérifier que la transaction existe et appartient à l'utilisateur
            $existingTransaction = $this->transactionRepository->findByIdAndUserId($id, $userId);
            if (!$existingTransaction) {
                throw new Exception('Transaction non trouvée', 404);
            }

            // Validation du DTO (mode update)
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // Vérifier qu'il y a des données à mettre à jour
            if (!$request->hasUpdates()) {
                throw new Exception('Aucune donnée à mettre à jour', 400);
            }

            // Convertir le DTO en array pour le repository
            $updateData = $request->toArray();
            $updated = $this->transactionRepository->update($id, $updateData);
            
            if (!$updated) {
                throw new Exception('Erreur lors de la mise à jour de la transaction', 500);
            }

            // Récupérer la transaction mise à jour
            return $this->transactionRepository->findByIdAndUserId($id, $userId);

        } catch (Exception $e) {
            error_log("Erreur mise à jour transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Supprimer une transaction
     */
    public function deleteTransaction(int $id, int $userId): bool
    {
        try {
            // Vérifier que la transaction existe et appartient à l'utilisateur
            $existingTransaction = $this->transactionRepository->findByIdAndUserId($id, $userId);
            if (!$existingTransaction) {
                throw new Exception('Transaction non trouvée', 404);
            }

            return $this->transactionRepository->delete($id);

        } catch (Exception $e) {
            error_log("Erreur suppression transaction: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les transactions par plage de dates
     */
    public function getTransactionsByDateRange(int $userId, string $startDate, string $endDate): array
    {
        try {
            // Validation des dates
            if (!strtotime($startDate) || !strtotime($endDate)) {
                throw new Exception('Format de date invalide', 400);
            }

            if ($startDate > $endDate) {
                throw new Exception('La date de début doit être antérieure à la date de fin', 400);
            }

            return $this->transactionRepository->getByDateRange($userId, $startDate, $endDate);
        } catch (Exception $e) {
            error_log("Erreur récupération transactions par date: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculer le total des transactions
     */
    public function getTotalAmount(int $userId, ?string $startDate = null, ?string $endDate = null): float
    {
        try {
            return $this->transactionRepository->getTotalAmount($userId, $startDate, $endDate);
        } catch (Exception $e) {
            error_log("Erreur calcul total: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les dernières transactions
     */
    public function getRecentTransactions(int $userId, int $limit = 10): array
    {
        try {
            return $this->transactionRepository->getRecentTransactions($userId, $limit);
        } catch (Exception $e) {
            error_log("Erreur récupération transactions récentes: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Rechercher des transactions
     */
    public function searchTransactions(int $userId, string $searchTerm): array
    {
        try {
            if (strlen($searchTerm) < 2) {
                throw new Exception('Le terme de recherche doit contenir au moins 2 caractères', 400);
            }

            return $this->transactionRepository->searchTransactions($userId, $searchTerm);
        } catch (Exception $e) {
            error_log("Erreur recherche transactions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les statistiques des transactions
     */
    public function getTransactionStats(int $userId): array
    {
        try {
            $totalAmount = $this->transactionRepository->getTotalAmount($userId);
            $totalCount = $this->transactionRepository->countByUserId($userId);
            $recentTransactions = $this->transactionRepository->getRecentTransactions($userId, 5);

            // Calculer les totaux par mois (30 derniers jours)
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
            $monthlyAmount = $this->transactionRepository->getTotalAmount($userId, $startDate, $endDate);

            return [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'monthly_amount' => $monthlyAmount,
                'recent_transactions' => $recentTransactions,
                'average_amount' => $totalCount > 0 ? $totalAmount / $totalCount : 0
            ];
        } catch (Exception $e) {
            error_log("Erreur statistiques transactions: " . $e->getMessage());
            throw $e;
        }
    }



    /**
     * Préparer les données de transaction depuis un DTO pour la création
     */
    private function prepareTransactionDataFromDTO(CreateTransactionRequest $request, int $userId): array
    {
        return [
            'title' => trim($request->title),
            'description' => $request->description ? trim($request->description) : null,
            'amount' => $request->amount,
            'date' => $request->date,
            'location' => $request->location ? trim($request->location) : null,
            'category_id' => $request->category_id,
            'sub_category_id' => $request->sub_category_id,
            'user_id' => $userId,
            'account_id' => $request->account_id
        ];
    }


}