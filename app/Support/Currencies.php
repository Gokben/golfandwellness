<?php

namespace App\Support;

class Currencies
{
    public const CODES = ['USD', 'GBP', 'TL', 'EUR'];
    public static function normalize(array $records): array
    {
        foreach ($records as $key => &$value) {
            if (is_array($value)) $value = self::normalize($value);
            elseif ($key === 'currency') $value = match ($value) { 'EU' => 'EUR', 'TRY' => 'TL', default => $value };
        }
        return $records;
    }
}
