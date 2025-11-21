<?php

namespace FinanceFlow\DTOs\Transaction;

/**
 * DTO pour les requêtes PUT de mise à jour de transaction
 */
class UpdateTransactionRequest
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?float $amount = null,
        public readonly ?string $date = null,
        public readonly ?string $description = null,
        public readonly ?string $location = null,
        public readonly ?int $category_id = null,
        public readonly ?int $sub_category_id = null,
        public readonly ?int $account_id = null
    ) {}

    /**
     * Créer depuis un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: isset($data['title']) ? $data['title'] : null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            date: isset($data['date']) ? $data['date'] : null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            location: array_key_exists('location', $data) ? $data['location'] : null,
            category_id: isset($data['category_id']) && !empty($data['category_id']) ? (int) $data['category_id'] : null,
            sub_category_id: isset($data['sub_category_id']) && !empty($data['sub_category_id']) ? (int) $data['sub_category_id'] : null,
            account_id: isset($data['account_id']) && !empty($data['account_id']) ? (int) $data['account_id'] : null
        );
    }

    /**
     * Convertir en array pour traitement (exclude null values)
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->title !== null) $data['title'] = $this->title;
        if ($this->amount !== null) $data['amount'] = $this->amount;
        if ($this->date !== null) $data['date'] = $this->date;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->location !== null) $data['location'] = $this->location;
        if ($this->category_id !== null) $data['category_id'] = $this->category_id;
        if ($this->sub_category_id !== null) $data['sub_category_id'] = $this->sub_category_id;
        if ($this->account_id !== null) $data['account_id'] = $this->account_id;

        return $data;
    }

    /**
     * Vérifier s'il y a des données à mettre à jour
     */
    public function hasUpdates(): bool
    {
        return !empty($this->toArray());
    }

    /**
     * Validation basique
     */
    public function isValid(): array
    {
        $errors = [];

        // Le titre doit être valide s'il est fourni
        if ($this->title !== null) {
            if (empty($this->title)) {
                $errors['title'] = 'Le titre ne peut pas être vide';
            } elseif (strlen($this->title) > 150) {
                $errors['title'] = 'Le titre ne peut pas dépasser 150 caractères';
            }
        }

        // Le montant doit être positif s'il est fourni
        if ($this->amount !== null && $this->amount <= 0) {
            $errors['amount'] = 'Le montant doit être positif';
        }

        // La date doit être valide si elle est fournie
        if ($this->date !== null && !strtotime($this->date)) {
            $errors['date'] = 'Format de date invalide';
        }

        // Validation des champs optionnels
        if ($this->description !== null && strlen($this->description) > 1000) {
            $errors['description'] = 'La description ne peut pas dépasser 1000 caractères';
        }

        if ($this->location !== null && strlen($this->location) > 100) {
            $errors['location'] = 'Le lieu ne peut pas dépasser 100 caractères';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}