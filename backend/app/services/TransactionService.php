<?php

namespace FinanceFlow\Services;

use FinanceFlow\Repositories\TransactionRepository;
use FinanceFlow\Models\Transaction;
use FinanceFlow\Services\{ValidationService, BudgetService};
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
    private BudgetService $budgetService;

    public function __construct()
    {
        $this->transactionRepository = new TransactionRepository();
        $this->validator = new ValidationService();
        $this->budgetService = new BudgetService();
    }

    /**
     * Créer une nouvelle transaction
     */
    public function createTransaction(CreateTransactionRequest $request, int $userId): Transaction
    {
        try {
            // Le DTO a déjà sa propre validation, mais on peut ajouter des validations business ici
            $validation = $request->isValid();
            if (!$validation['valid']) {
                throw new Exception('Données invalides: ' . implode(', ', $validation['errors']), 422);
            }

            // VÉRIFICATION DU SOLDE SI DÉPENSE
            if ($request->amount < 0 && $request->account_id !== null) {
                $accountService = new \FinanceFlow\Services\AccountService();
                $balanceCheck = $accountService->hasSufficientBalance(
                    $request->account_id,
                    $userId,
                    $request->amount
                );

                if (!$balanceCheck['sufficient']) {
                    error_log("ALERTE SOLDE: Utilisateur {$userId} - " . $balanceCheck['message']);
                    throw new Exception($balanceCheck['message'], 422);
                }
            }

            // VÉRIFICATION DU BUDGET AVANT CRÉATION
            if ($request->category_id !== null) {
                $budgetImpact = $this->budgetService->checkBudgetImpact(
                    $userId,
                    $request->category_id,
                    $request->amount
                );

                // Loguer l'information pour le suivi
                if ($budgetImpact['will_exceed']) {
                    error_log("ALERTE BUDGET: Utilisateur {$userId} - " . $budgetImpact['message']);
                    // On pourrait aussi envoyer une notification, email, etc.
                }
            }

            // Préparer les données depuis le DTO
            $transactionData = $this->prepareTransactionDataFromDTO($request, $userId);
            $transactionId = $this->transactionRepository->create($transactionData);

            if (!$transactionId) {
                throw new Exception('Erreur lors de la création de la transaction', 500);
            }

            // Récupérer la transaction créée (objet Transaction)
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
     * Récupérer les transactions d'un utilisateur (utiliser getFilteredAndSortedTransactions à la place)
     * @deprecated Utiliser getFilteredAndSortedTransactions() avec filtres vides
     * @return Transaction[]
     */
    public function getUserTransactions(int $userId, ?int $limit = null, int $offset = 0): array
    {
        return $this->getFilteredAndSortedTransactions($userId, [], 'date', 'DESC', $limit, $offset)['transactions'];
    }

    /**
     * Récupérer une transaction par ID
     */
    public function getTransaction(int $id, int $userId): Transaction
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
    public function updateTransaction(int $id, UpdateTransactionRequest $request, int $userId): Transaction
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

            // VÉRIFICATION DU BUDGET si le montant ou la catégorie change
            $categoryId = $request->category_id ?? $existingTransaction->getCategoryId();
            $newAmount = $request->amount ?? $existingTransaction->getAmount();
            $oldAmount = $existingTransaction->getAmount();
            
            // Si le montant ou la catégorie change, vérifier l'impact sur le budget
            if (($request->amount !== null && $request->amount !== $oldAmount) || 
                ($request->category_id !== null && $request->category_id !== $existingTransaction->getCategoryId())) {
                
                // Calculer la différence : si on augmente le montant, vérifier l'impact
                $amountDifference = $newAmount - $oldAmount;
                
                if ($amountDifference > 0 && $categoryId !== null) {
                    $budgetImpact = $this->budgetService->checkBudgetImpact(
                        $userId,
                        $categoryId,
                        $amountDifference
                    );

                    if ($budgetImpact['will_exceed']) {
                        error_log("ALERTE BUDGET: Utilisateur {$userId} - " . $budgetImpact['message']);
                    }
                }
            }

            // Convertir le DTO en array pour le repository
            $updateData = $request->toArray();
            $updated = $this->transactionRepository->update($id, $updateData);
            
            if (!$updated) {
                throw new Exception('Erreur lors de la mise à jour de la transaction', 500);
            }

            // Récupérer la transaction mise à jour (objet Transaction)
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
     * @deprecated Utiliser getFilteredAndSortedTransactions() avec start_date/end_date
     */
    public function getTransactionsByDateRange(int $userId, string $startDate, string $endDate): array
    {
        if (!strtotime($startDate) || !strtotime($endDate)) {
            throw new Exception('Format de date invalide', 400);
        }
        if ($startDate > $endDate) {
            throw new Exception('La date de début doit être antérieure à la date de fin', 400);
        }
        return $this->getFilteredAndSortedTransactions(
            $userId,
            ['start_date' => $startDate, 'end_date' => $endDate]
        )['transactions'];
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
        return $this->getFilteredAndSortedTransactions(
            $userId,
            [],
            'date',
            'DESC',
            $limit,
            0
        )['transactions'];
    }

    /**
     * Rechercher des transactions
     */
    public function searchTransactions(int $userId, string $searchTerm): array
    {
        if (strlen($searchTerm) < 2) {
            throw new Exception('Le terme de recherche doit contenir au moins 2 caractères', 400);
        }
        return $this->getFilteredAndSortedTransactions(
            $userId,
            ['search' => $searchTerm]
        )['transactions'];
    }

    /**
     * Récupérer les statistiques des transactions
     */
    public function getTransactionStats(int $userId): array
    {
        try {
            $totalAmount = $this->transactionRepository->getTotalAmount($userId);
            $allTransactions = $this->getFilteredAndSortedTransactions($userId)['transactions'];
            $totalCount = count($allTransactions);
            $recentTransactions = array_slice($allTransactions, 0, 5);

            // Calculer les totaux par mois (30 derniers jours)
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate = date('Y-m-d');
            $monthlyAmount = $this->transactionRepository->getTotalAmount($userId, $startDate, $endDate);

            // getFilteredAndSortedTransactions() retourne déjà des arrays, pas besoin de convertir
            $recentTransactionsArray = $recentTransactions;

            return [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'monthly_amount' => $monthlyAmount,
                'recent_transactions' => $recentTransactionsArray,
                'average_amount' => $totalCount > 0 ? $totalAmount / $totalCount : 0
            ];
        } catch (Exception $e) {
            error_log("Erreur statistiques transactions: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Récupérer les transactions avec filtres et tri avancés
     * 
     * @param int $userId ID de l'utilisateur
     * @param array $filters Filtres à appliquer
     * @param string $sortBy Colonne de tri
     * @param string $sortOrder Ordre de tri (ASC/DESC)
     * @param int|null $limit Limite de résultats
     * @param int $offset Offset pour pagination
     * @return array
     */
    public function getFilteredAndSortedTransactions(
        int $userId,
        array $filters = [],
        string $sortBy = 'date',
        string $sortOrder = 'DESC',
        ?int $limit = null,
        int $offset = 0
    ): array {
        try {
            // Valider les paramètres de tri
            $allowedSortColumns = ['date', 'amount', 'title', 'category_id', 'sub_category_id'];
            if (!in_array($sortBy, $allowedSortColumns)) {
                throw new Exception("Colonne de tri invalide: {$sortBy}", 400);
            }
            
            $sortOrder = strtoupper($sortOrder);
            if (!in_array($sortOrder, ['ASC', 'DESC'])) {
                throw new Exception("Ordre de tri invalide: {$sortOrder}", 400);
            }
            
            // Valider les filtres de montant
            if (isset($filters['min_amount']) && isset($filters['max_amount'])) {
                if ((float)$filters['min_amount'] > (float)$filters['max_amount']) {
                    throw new Exception('Le montant minimum ne peut pas être supérieur au montant maximum', 400);
                }
            }
            
            // Valider les filtres de date
            if (isset($filters['start_date']) && isset($filters['end_date'])) {
                if ($filters['start_date'] > $filters['end_date']) {
                    throw new Exception('La date de début doit être antérieure à la date de fin', 400);
                }
            }
            
            $transactions = $this->transactionRepository->getFilteredAndSorted(
                $userId,
                $filters,
                $sortBy,
                $sortOrder,
                $limit,
                $offset
            );
            
            $totalCount = $this->transactionRepository->countFiltered($userId, $filters);
            
            // Convertir les objets Transaction en tableaux
            $transactionsArray = array_map(fn(Transaction $t) => $t->toArray(), $transactions);
            
            return [
                'transactions' => $transactionsArray,
                'total' => $totalCount,
                'filters_applied' => $filters,
                'sort' => [
                    'by' => $sortBy,
                    'order' => $sortOrder
                ],
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => $limit ? ($offset + $limit < $totalCount) : false
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Erreur getFilteredAndSortedTransactions: " . $e->getMessage());
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