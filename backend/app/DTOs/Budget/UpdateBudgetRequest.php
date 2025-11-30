<?php

namespace FinanceFlow\DTOs\Budget;

/**
 * DTO pour la mise à jour d'un budget
 */
class UpdateBudgetRequest
{
    public function __construct(
        public readonly ?float $max_amount = null,
        public readonly ?int $category_id = null
    ) {}

    /**
     * Créer depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            max_amount: isset($data['max_amount']) ? (float) $data['max_amount'] : null,
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null
        );
    }

    /**
     * Validation du DTO
     */
    public function isValid(): array
    {
        $errors = [];

        if ($this->max_amount !== null && $this->max_amount <= 0) {
            $errors[] = 'Le montant maximum doit être supérieur à 0';
        }

        if ($this->max_amount !== null && $this->max_amount > 999999999.99) {
            $errors[] = 'Le montant maximum est trop élevé';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Vérifier s'il y a des données à mettre à jour
     */
    public function hasUpdates(): bool
    {
        return $this->max_amount !== null || $this->category_id !== null;
    }

    /**
     * Convertir en tableau (seulement les champs non null)
     */
    public function toArray(): array
    {
        $data = [];
        
        if ($this->max_amount !== null) {
            $data['max_amount'] = $this->max_amount;
        }
        
        if ($this->category_id !== null) {
            $data['category_id'] = $this->category_id;
        }
        
        return $data;
    }
}
