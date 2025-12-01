<?php

namespace FinanceFlow\DTOs\Account;

use FinanceFlow\Models\Account;

/**
 * DTO pour la réponse d'un compte
 * Inclut le solde actuel calculé
 */
class AccountResponse
{
    public int $id;
    public string $name;
    public string $type;
    public float $initialBalance;
    public float $currentBalance;
    public int $userId;

    /**
     * Créer depuis un objet Account et son solde actuel
     */
    public static function fromAccount(Account $account, float $currentBalance): self
    {
        $response = new self();
        $response->id = $account->getId();
        $response->name = $account->getName();
        $response->type = $account->getType();
        $response->initialBalance = $account->getInitialBalance();
        $response->currentBalance = $currentBalance;
        $response->userId = $account->getUserId();
        
        return $response;
    }

    /**
     * Créer depuis un tableau (avec current_balance déjà calculé)
     */
    public static function fromArray(array $data): self
    {
        $response = new self();
        $response->id = (int) $data['id'];
        $response->name = $data['name'];
        $response->type = $data['type'];
        $response->initialBalance = (float) $data['initial_balance'];
        $response->currentBalance = (float) ($data['current_balance'] ?? $data['initial_balance']);
        $response->userId = (int) $data['user_id'];
        
        return $response;
    }

    /**
     * Convertir en tableau pour la réponse JSON
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->getTypeLabel(),
            'initial_balance' => $this->initialBalance,
            'current_balance' => $this->currentBalance,
            'movements' => $this->currentBalance - $this->initialBalance,
            'user_id' => $this->userId
        ];
    }

    /**
     * Obtenir le libellé du type de compte
     */
    private function getTypeLabel(): string
    {
        return match($this->type) {
            'checking' => 'Compte courant',
            'savings' => 'Compte épargne',
            'other' => 'Autre',
            default => $this->type
        };
    }
}
