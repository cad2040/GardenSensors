<?php
namespace App\Utils;

class Validator {
    private $errors = [];
    private $data = [];
    private $rules = [];

    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate(): bool {
        $this->errors = [];

        foreach ($this->rules as $field => $rules) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $rules);

            foreach ($rules as $rule) {
                $params = [];
                if (strpos($rule, ':') !== false) {
                    [$rule, $param] = explode(':', $rule, 2);
                    $params = explode(',', $param);
                }

                $method = 'validate' . ucfirst($rule);
                if (method_exists($this, $method)) {
                    if (!$this->$method($field, $value, $params)) {
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    private function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    private function validateRequired(string $field, $value, array $params): bool {
        if ($value === null || $value === '') {
            $this->addError($field, "The {$field} field is required.");
            return false;
        }
        return true;
    }

    private function validateEmail(string $field, $value, array $params): bool {
        if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} must be a valid email address.");
            return false;
        }
        return true;
    }

    private function validateMin(string $field, $value, array $params): bool {
        $min = $params[0] ?? 0;
        if (is_string($value) && strlen($value) < $min) {
            $this->addError($field, "The {$field} must be at least {$min} characters.");
            return false;
        } elseif (is_numeric($value) && $value < $min) {
            $this->addError($field, "The {$field} must be at least {$min}.");
            return false;
        }
        return true;
    }

    private function validateMax(string $field, $value, array $params): bool {
        $max = $params[0] ?? PHP_INT_MAX;
        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, "The {$field} may not be greater than {$max} characters.");
            return false;
        } elseif (is_numeric($value) && $value > $max) {
            $this->addError($field, "The {$field} may not be greater than {$max}.");
            return false;
        }
        return true;
    }

    private function validateNumeric(string $field, $value, array $params): bool {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "The {$field} must be a number.");
            return false;
        }
        return true;
    }

    private function validateAlpha(string $field, $value, array $params): bool {
        if ($value !== null && $value !== '' && !ctype_alpha($value)) {
            $this->addError($field, "The {$field} may only contain letters.");
            return false;
        }
        return true;
    }

    private function validateAlphaNum(string $field, $value, array $params): bool {
        if ($value !== null && $value !== '' && !ctype_alnum($value)) {
            $this->addError($field, "The {$field} may only contain letters and numbers.");
            return false;
        }
        return true;
    }

    private function validateDate(string $field, $value, array $params): bool {
        if ($value !== null && $value !== '') {
            $format = $params[0] ?? 'Y-m-d';
            $date = \DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, "The {$field} is not a valid date.");
                return false;
            }
        }
        return true;
    }

    private function validateIn(string $field, $value, array $params): bool {
        if ($value !== null && $value !== '' && !in_array($value, $params)) {
            $allowed = implode(', ', $params);
            $this->addError($field, "The {$field} must be one of the following: {$allowed}");
            return false;
        }
        return true;
    }

    private function validateRegex(string $field, $value, array $params): bool {
        $pattern = $params[0] ?? '';
        if ($value !== null && $value !== '' && !preg_match($pattern, $value)) {
            $this->addError($field, "The {$field} format is invalid.");
            return false;
        }
        return true;
    }

    private function validateConfirmed(string $field, $value, array $params): bool {
        $confirmation = $this->data[$field . '_confirmation'] ?? null;
        if ($value !== $confirmation) {
            $this->addError($field, "The {$field} confirmation does not match.");
            return false;
        }
        return true;
    }

    private function validateUnique(string $field, $value, array $params): bool {
        if (count($params) < 2) {
            return true;
        }

        [$table, $column] = $params;
        $ignore = $params[2] ?? null;

        $db = Database::getInstance();
        $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = :value";
        $bindings = [':value' => $value];

        if ($ignore !== null) {
            $query .= " AND id != :id";
            $bindings[':id'] = $ignore;
        }

        $result = $db->fetch($query, $bindings);
        if ($result['count'] > 0) {
            $this->addError($field, "The {$field} has already been taken.");
            return false;
        }
        return true;
    }
} 