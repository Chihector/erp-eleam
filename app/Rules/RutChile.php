<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class RutChile implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Require a hyphen before the verification digit, optional dots.
        if (!preg_match('/^([0-9]{1,2}\.?[0-9]{3}\.?[0-9]{3}|[0-9]{7,8})-[0-9Kk]{1}$/', $value)) {
            $fail('El RUT debe contener un guión antes del dígito verificador y ser válido. Ejemplo: 12345678-9 o 12.345.678-9');
            return;
        }

        $rut = preg_replace('/[^0-9Kk]/', '', $value);
        if (strlen($rut) < 8) {
            $fail('El RUT ingresado es demasiado corto.');
            return;
        }

        $dv = strtoupper(substr($rut, -1));
        $number = substr($rut, 0, -1);
        
        $i = 2;
        $sum = 0;
        foreach(array_reverse(str_split($number)) as $v) {
            if($i == 8) $i = 2;
            $sum += $v * $i;
            $i++;
        }
        
        $res = 11 - ($sum % 11);
        
        if($res == 11) $expectedDv = '0';
        elseif($res == 10) $expectedDv = 'K';
        else $expectedDv = (string)$res;

        if ($dv !== $expectedDv) {
            $fail('El dígito verificador del RUT ingresado es incorrecto.');
        }
    }
}
