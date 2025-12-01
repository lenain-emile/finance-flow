<?php

namespace FinanceFlow\DTOs\Account;

/**
 * DTO pour la mise à jour d'un compte
 * Note: initial_balance n'est PAS modifiable pour des raisons de sécurité
 */
class UpdateAccountRequest
{
    public ?string $name = null;
    public ?string $type = null;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? null;
        $this->type = $data['type'] ?? null;
    }

    /**
     * Valider les données
     */
    public function isValid(): array
    {
        $errors = [];

        // Validation du nom si fourni
        if ($this->name !== null) {
            if (empty(trim($this->name))) {
                $errors[] = "Le nom du compte ne peut pas être vide";
            }

            if (strlen($this->name) > 100) {
                $errors[] = "Le nom du compte ne peut pas dépasser 100 caractères";
            }
        }

        // Validation du type si fourni
        if ($this->type !== null) {
            $validTypes = ['checking', 'savings', 'other'];
            if (!in_array($this->type, $validTypes)) {
                $errors[] = "Type de compte invalide. Types autorisés : " . implode(', ', $validTypes);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Convertir en tableau pour la BDD (seulement les champs non-null)
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = trim($this->name);
        }

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        return $data;
    }

    /**
     * Vérifier s'il y a des données à mettre à jour
     */
    public function hasChanges(): bool
    {
        return $this->name !== null || $this->type !== null;
    }
}
