<?php

namespace FinanceFlow\DTOs\Budget;

/**
 * DTO pour la création d'un budget
 */
class CreateBudgetRequest
{
    public function __construct(
        public readonly float $max_amount,
        public readonly ?int $category_id = null,
        public readonly ?string $start_date = null,
        public readonly ?string $end_date = null
    ) {}

    /**
     * Créer depuis un tableau
     */
    public static function fromArray(array $data): self
    {
        return new self(
            max_amount: (float) ($data['max_amount'] ?? 0),
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            start_date: $data['start_date'] ?? date('Y-m-d'), // Par défaut aujourd'hui
            end_date: $data['end_date'] ?? null
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

        if ($this->category_id === null) {
            $errors[] = 'La catégorie est requise';
        }

        // Validation des dates
        if ($this->start_date !== null && !$this->isValidDate($this->start_date)) {
            $errors[] = 'La date de début est invalide';
        }

        if ($this->end_date !== null && !$this->isValidDate($this->end_date)) {
            $errors[] = 'La date de fin est invalide';
        }

        if ($this->start_date !== null && $this->end_date !== null) {
            if (strtotime($this->end_date) < strtotime($this->start_date)) {
                $errors[] = 'La date de fin doit être après la date de début';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Vérifier si une date est valide
     */
    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Convertir en tableau
     */
    public function toArray(): array
    {
        return [
            'max_amount' => $this->max_amount,
            'category_id' => $this->category_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date
        ];
    }
}
