<?php

namespace App\Exceptions;

class ValidationException extends \Exception
{
    /**
     * @var array<string, string|string[]>
     */
    private array $errors;

    /**
     * @param array<string, string|string[]> $errors
     */
    public function __construct(string $message, array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    /**
     * @return array<string, string|string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
