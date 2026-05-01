<?php

declare(strict_types=1);

namespace App\Utils;

/**
 * Validator — Centralized input validation.
 *
 * Usage:
 *   $result = Validator::make($_POST, [
 *       'amount'   => 'required|numeric|min:0.01|max:1000000|decimal:2',
 *       'currency' => 'required|in:NGN,USD,GHS,KES,ZAR,GBP,EUR',
 *       'email'    => 'required|email|max:254',
 *   ]);
 *
 *   if ($result->fails()) {
 *       Response::error('Validation failed', 422, $result->errors());
 *   }
 *
 * IMPORTANT: Validator validates — it does NOT sanitize or modify values.
 * Sanitize (trim, strip) before calling make(). After validation passes,
 * raw values still go through prepared statements — never raw SQL.
 */
final class Validator
{
    private array $errors = [];

    private function __construct(
        private readonly array $data,
        private readonly array $rules
    ) {
        $this->run();
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    // ─── Rule runner ──────────────────────────────────────────────────────────

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                [$ruleName, $param] = $this->parseRule($rule);

                if ($ruleName !== 'required' && ($value === null || $value === '')) {
                    break; // Skip other rules if field is absent and not required
                }

                $error = $this->applyRule($field, $value, $ruleName, $param);
                if ($error !== null) {
                    $this->errors[$field][] = $error;
                    break; // One error per field per validation pass
                }
            }
        }
    }

    private function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
            return [$name, $param];
        }
        return [$rule, null];
    }

    private function applyRule(string $field, mixed $value, string $rule, ?string $param): ?string
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required' => ($value === null || $value === '')
                ? "{$label} is required."
                : null,

            'numeric' => !is_numeric($value)
                ? "{$label} must be a number."
                : null,

            'integer' => filter_var($value, FILTER_VALIDATE_INT) === false
                ? "{$label} must be an integer."
                : null,

            'email' => !filter_var($value, FILTER_VALIDATE_EMAIL)
                ? "{$label} must be a valid email address."
                : null,

            'min' => is_numeric($value) && (float) $value < (float) $param
                ? "{$label} must be at least {$param}."
                : null,

            'max' => is_numeric($value) && (float) $value > (float) $param
                ? "{$label} must not exceed {$param}."
                : null,

            'min_length' => strlen((string) $value) < (int) $param
                ? "{$label} must be at least {$param} characters."
                : null,

            'max_length', 'max' => strlen((string) $value) > (int) $param
                ? "{$label} must not exceed {$param} characters."
                : null,

            'decimal' => !preg_match('/^\d+(\.\d{1,' . (int)$param . '})?$/', (string) $value)
                ? "{$label} must have at most {$param} decimal places."
                : null,

            'in' => !in_array($value, explode(',', $param ?? ''), true)
                ? "{$label} must be one of: {$param}."
                : null,

            'regex' => !preg_match('/' . $param . '/u', (string) $value)
                ? "{$label} contains invalid characters."
                : null,

            'positive' => !is_numeric($value) || (float) $value <= 0
                ? "{$label} must be a positive number."
                : null,

            'has_uppercase' => !preg_match('/[A-Z]/', (string) $value)
                ? "{$label} must contain at least one uppercase letter."
                : null,

            'has_number' => !preg_match('/[0-9]/', (string) $value)
                ? "{$label} must contain at least one number."
                : null,

            'has_special' => !preg_match('/[^a-zA-Z0-9]/', (string) $value)
                ? "{$label} must contain at least one special character."
                : null,

            'confirmed' => $value !== ($this->data[$field . '_confirmation'] ?? null)
                ? "{$label} confirmation does not match."
                : null,

            default => null,
        };
    }
}
