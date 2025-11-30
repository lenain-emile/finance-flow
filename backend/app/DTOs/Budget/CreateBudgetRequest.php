<?php

namespace FinanceFlow\DTOs\Budget;

/**
 * DTO pour la création d'un budget
 */
class CreateBudgetRequest
{
    public function __construct(
        public readonly float $max_amount,
        public readonly ?int $category_id = null
    ) {}

    /**
     * Créer depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            max_amount: (float) ($data['max_amount'] ?? 0),
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null
        );
    }

    /**
     * Validation du DTO
     */
    public function isValid(): array
    {
        $errors = [];

        if ($this->max_amount <= 0) {
            $errors[] = 'Le montant maximum doit être supérieur à 0';
        }

        if ($this->max_amount > 999999999.99) {
            $errors[] = 'Le montant maximum est trop élevé';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Convertir en tableau
     */
    public function toArray(): array
    {
        return [
            'max_amount' => $this->max_amount,
            'category_id' => $this->category_id
        ];
    }
}
