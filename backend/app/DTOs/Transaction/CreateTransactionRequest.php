<?php

namespace FinanceFlow\DTOs\Transaction;

/**
 * DTO pour les requêtes POST de création de transaction
 */
class CreateTransactionRequest
{
    public function __construct(
        public readonly string $title,
        public readonly float $amount,
        public readonly string $date,
        public readonly ?string $description = null,
        public readonly ?string $location = null,
        public readonly ?int $category_id = null,
        public readonly ?int $sub_category_id = null,
        public readonly ?int $account_id = null
    ) {}

    /**
     * Créer depuis un array (validation basique)
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: $data['title'] ?? '',
            amount: (float) ($data['amount'] ?? 0),
            date: $data['date'] ?? date('Y-m-d'),
            description: $data['description'] ?? null,
            location: $data['location'] ?? null,
            category_id: isset($data['category_id']) && !empty($data['category_id']) ? (int) $data['category_id'] : null,
            sub_category_id: isset($data['sub_category_id']) && !empty($data['sub_category_id']) ? (int) $data['sub_category_id'] : null,
            account_id: isset($data['account_id']) && !empty($data['account_id']) ? (int) $data['account_id'] : null
        );
    }

    /**
     * Convertir en array pour traitement
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'amount' => $this->amount,
            'date' => $this->date,
            'description' => $this->description,
            'location' => $this->location,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'account_id' => $this->account_id
        ];
    }

    /**
     * Validation basique des champs requis
     */
    public function isValid(): array
    {
        $errors = [];

        if (empty($this->title)) {
            $errors['title'] = 'Le titre est requis';
        } elseif (strlen($this->title) > 150) {
            $errors['title'] = 'Le titre ne peut pas dépasser 150 caractères';
        }

        if ($this->amount == 0) {
            $errors['amount'] = 'Le montant ne peut pas être zéro';
        }

        if (empty($this->date)) {
            $errors['date'] = 'La date est requise';
        } elseif (!strtotime($this->date)) {
            $errors['date'] = 'Format de date invalide';
        }

        if ($this->description && strlen($this->description) > 1000) {
            $errors['description'] = 'La description ne peut pas dépasser 1000 caractères';
        }

        if ($this->location && strlen($this->location) > 100) {
            $errors['location'] = 'Le lieu ne peut pas dépasser 100 caractères';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}