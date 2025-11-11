<?php

namespace FinanceFlow\DTOs;

class ServiceResponse
{
    public static function success(string $message, array $data = [], int $code = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code
        ];
    }

    public static function error(string $message, array $errors = [], int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code
        ];
    }

    public static function validationError(array $errors): array
    {
        return self::error('Données invalides', $errors, 422);
    }
}