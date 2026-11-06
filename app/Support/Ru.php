<?php

namespace App\Support;

class Ru
{
    /**
     * Русская форма множественного числа: plural(5, ['тема', 'темы', 'тем']) → 'тем'.
     *
     * @param  array{0:string, 1:string, 2:string}  $forms
     */
    public static function plural(int $n, array $forms): string
    {
        $n = abs($n) % 100;
        $last = $n % 10;

        return match (true) {
            $n >= 11 && $n <= 14 => $forms[2],
            $last === 1 => $forms[0],
            $last >= 2 && $last <= 4 => $forms[1],
            default => $forms[2],
        };
    }
}
