<?php

namespace App\Services;

/**
 * Fachada del Validador.
 *
 * Uso:
 *   $limpio = ValidationService::validate($datos, $reglas); // lanza si falla
 *   $v = ValidationService::make($datos, $reglas);
 */
class ValidationService
{
    public static function make(array $data, array $rules): Validator
    {
        return new Validator($data, $rules);
    }

    public static function validate(array $data, array $rules, bool $throw = true): array
    {
        $validator = new Validator($data, $rules);

        if ($validator->fails() && $throw) {
            throw new \InvalidArgumentException(implode(' ', $validator->flattenErrors()));
        }

        return $validator->validated();
    }
}
