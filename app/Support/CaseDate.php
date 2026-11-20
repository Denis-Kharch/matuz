<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Дата кейса: разбор текстовой подписи («март 2025», «март–апрель 2025», «14.03.2025», «2024»)
 * в дату для сортировки и обратная подпись «март 2025» из даты.
 */
class CaseDate
{
    private const MONTHS = ['январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь'];

    /** Основы названий месяцев: ловят «март», «марта», «мар.», «сентябрь», «сентября». */
    private const STEMS = ['янв', 'фев', 'мар', 'апр', 'ма[йя]', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

    /** Дата «Y-m-d» для сортировки; null — в подписи нет года. Берётся последний месяц и год (самое свежее). */
    public static function parse(?string $label): ?string
    {
        $label = mb_strtolower(trim((string) $label));
        if (preg_match_all('/\b(\d{1,2})\.(\d{1,2})\.(\d{4})\b/u', $label, $m, PREG_SET_ORDER)) {
            $d = end($m);

            return checkdate((int) $d[2], (int) $d[1], (int) $d[3]) ? sprintf('%04d-%02d-%02d', $d[3], $d[2], $d[1]) : null;
        }
        if (! preg_match_all('/\b(19|20)\d{2}\b/u', $label, $years)) {
            return null;
        }
        $year = (int) end($years[0]);
        $month = 1;
        $pos = -1;
        foreach (self::STEMS as $i => $stem) {
            if (preg_match_all('/(?<![\p{L}])'.$stem.'/u', $label, $hits, PREG_OFFSET_CAPTURE)) {
                $last = end($hits[0])[1];
                if ($last > $pos) {
                    [$pos, $month] = [$last, $i + 1];
                }
            }
        }

        return sprintf('%04d-%02d-01', $year, $month);
    }

    /** Подпись для сайта: «март 2025». */
    public static function label(mixed $date): string
    {
        $d = Carbon::parse($date);

        return self::MONTHS[$d->month - 1].' '.$d->year;
    }
}
