<?php

namespace FinanceFlow\DTOs\Account;

/**
 * DTO pour la création d'un compte
 */
class CreateAccountRequest
{
    public string $name;
    public string $type;
    public float $initial_balance;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? '';
        $this->type = $data['type'] ?? 'checking';
        $this->initial_balance = isset($data['initial_balance']) ? (float) $data['initial_balance'] : 0.0;
    }

    /**
     * Valider les données
     */
    public function isValid(): array
    {
        $errors = [];

        // Validation du nom
        if (empty(trim($this->name))) {
            $errors[] = "Le nom du compte est obligatoire";
        }

        if (strlen($this->name) > 100) {
            $errors[] = "Le nom du compte ne peut pas dépasser 100 caractères";
        }

        // Validation du type
        $validTypes = ['checking', 'savings', 'other'];
        if (!in_array($this->type, $validTypes)) {
            $errors[] = "Type de compte invalide. Types autorisés : " . implode(', ', $validTypes);
        }

        // Validation du solde initial
        if ($this->initial_balance < 0) {
            $errors[] = "Le solde initial ne peut pas être négatif";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Convertir en tableau pour la BDD
     */
    public function toArray(): array
    {
        return [
            'name' => trim($this->name),
            'type' => $this->type,
            'initial_balance' => $this->initial_balance
        ];
    }
}
