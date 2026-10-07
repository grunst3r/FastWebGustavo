<?php

namespace App\Services;

/**
 * Validador de datos simple con reglas separadas por "|".
 *
 * Reglas soportadas: required, nullable, email, url, numeric, integer,
 * boolean, min:n, max:n, in:a,b,c, same:campo, confirmed, date.
 *
 * Uso:
 *   $v = validator($datos, ['email' => 'required|email', 'password' => 'required|min:8']);
 *   if ($v->fails()) { print_r($v->errors()); }
 *   $limpio = $v->validated();
 */
class Validator
{
    protected array $errors = [];
    protected bool $failed = false;

    public function __construct(protected array $data, protected array $rules)
    {
        $this->run();
    }

    protected function run(): void
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', (string) $ruleSet);
            $value = $this->data[$field] ?? null;
            $nullable = in_array('nullable', $rules, true) && ($value === null || $value === '');

            foreach ($rules as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                $name = trim((string) $name);

                if ($this->shouldSkip($name, $value, $nullable)) {
                    continue;
                }

                $this->check($name, $field, $value, $param);
            }
        }
    }

    protected function shouldSkip(string $rule, $value, bool $nullable): bool
    {
        if ($rule === 'required') {
            return false;
        }

        if ($nullable) {
            return true;
        }

        return $value === null || $value === '';
    }

    protected function check(string $rule, string $field, $value, $param): bool
    {
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '' || (is_array($value) && count($value) === 0)) {
                    return $this->addError($field, 'El campo es obligatorio.');
                }
                return true;

            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $this->addError($field, 'El correo no es válido.');
                }
                return true;

            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    return $this->addError($field, 'La URL no es válida.');
                }
                return true;

            case 'numeric':
                if (!is_numeric($value)) {
                    return $this->addError($field, 'Debe ser numérico.');
                }
                return true;

            case 'integer':
                if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                    return $this->addError($field, 'Debe ser un número entero.');
                }
                return true;

            case 'boolean':
                if (!in_array($value, [true, false, 0, 1, '0', '1'], true)) {
                    return $this->addError($field, 'Debe ser verdadero o falso.');
                }
                return true;

            case 'min':
                $min = (int) $param;
                $invalid = is_numeric($value)
                    ? ((float) $value < $min)
                    : (mb_strlen((string) $value) < $min);
                if ($invalid) {
                    return $this->addError($field, "Debe tener al menos {$min}.");
                }
                return true;

            case 'max':
                $max = (int) $param;
                $invalid = is_numeric($value)
                    ? ((float) $value > $max)
                    : (mb_strlen((string) $value) > $max);
                if ($invalid) {
                    return $this->addError($field, "Debe tener como máximo {$max}.");
                }
                return true;

            case 'in':
                $allowed = explode(',', (string) $param);
                if (!in_array((string) $value, $allowed, true)) {
                    return $this->addError($field, 'El valor seleccionado no es válido.');
                }
                return true;

            case 'same':
                if (($this->data[$param] ?? null) != $value) {
                    return $this->addError($field, 'Los valores no coinciden.');
                }
                return true;

            case 'confirmed':
                if (($this->data[$field . '_confirmation'] ?? null) != $value) {
                    return $this->addError($field, 'La confirmación no coincide.');
                }
                return true;

            case 'date':
                if (strtotime((string) $value) === false) {
                    return $this->addError($field, 'La fecha no es válida.');
                }
                return true;
        }

        return true;
    }

    protected function addError(string $field, string $message): bool
    {
        $this->errors[$field][] = $message;
        $this->failed = true;

        return false;
    }

    public function fails(): bool
    {
        return $this->failed;
    }

    public function passes(): bool
    {
        return !$this->failed;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    public function flattenErrors(): array
    {
        $flat = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $message) {
                $flat[] = $message;
            }
        }
        return $flat;
    }

    /**
     * Devuelve solo los campos que pasaron la validacion.
     */
    public function validated(): array
    {
        $out = [];
        foreach ($this->rules as $field => $_) {
            if (!isset($this->errors[$field]) && array_key_exists($field, $this->data)) {
                $out[$field] = $this->data[$field];
            }
        }
        return $out;
    }
}
