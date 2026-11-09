<?php

namespace App\Http\Controllers;

use App\Models\KpLead;
use App\Notifications\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Приём заявок на КП с публичного сайта (статика отправляет их сюда по kp-endpoint). */
class KpLeadController extends Controller
{
    public function store(Request $request): Response
    {
        // Ловушка для ботов: поле «website» человек не видит. Отвечаем как при успехе, но ничего не сохраняем.
        if (filled($request->input('website'))) {
            return response()->noContent();
        }

        $data = $request->validate([
            'company' => ['required', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $lead = KpLead::create([
            'company' => self::oneLine($data['company']),
            'city' => self::oneLine($data['city'] ?? '') ?: null,
            'source' => preg_match('/^[a-z0-9_-]{1,50}$/', $data['source'] ?? '') ? $data['source'] : 'unknown',
        ]);

        // После ответа и без сериализации замыкания (см. SitePublisher::request).
        app()->terminating(fn () => TelegramNotifier::send(self::message($lead)));

        return response()->noContent();
    }

    /** Управляющие символы и переводы строк — в один пробел (чтобы нельзя было подделать строки сообщения). */
    private static function oneLine(string $value): string
    {
        return trim(preg_replace('/[\p{C}\s]+/u', ' ', $value) ?? '');
    }

    /** Источник — код блока как есть (hero, faq, …). Пустой город опускается. */
    private static function message(KpLead $lead): string
    {
        $who = $lead->city ? "{$lead->company}, {$lead->city}" : $lead->company;

        return "Новый запрос КП: {$who} — с блока «{$lead->source}», ".$lead->created_at->format('d.m H:i');
    }
}
