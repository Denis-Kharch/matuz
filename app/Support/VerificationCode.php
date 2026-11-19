<?php

namespace App\Support;

/**
 * Код подтверждения Вебмастера / Search Console из того, что вставили в поле:
 * тег целиком (<meta … content="КОД">), строку DNS-записи («google-site-verification=КОД»,
 * «yandex-verification: КОД») или сам код. Возвращает только код.
 */
class VerificationCode
{
    public static function clean(?string $value): string
    {
        $value = trim((string) $value);
        if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $value, $m)) {
            $value = $m[1];
        }
        // Приставка из DNS-записи или из уже испорченного сохранения («google-site-verification1UcF…»).
        $value = preg_replace('/^\s*(google-site-verification|yandex-verification)\s*[:=]?\s*/i', '', $value);

        return preg_replace('/[^A-Za-z0-9_\-]/', '', $value);
    }
}
