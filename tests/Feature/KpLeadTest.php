<?php

use App\Filament\Resources\KpLeads\Pages\ListKpLeads;
use App\Filament\Resources\KpLeads\KpLeadResource;
use App\Models\KpLead;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function telegramOn(string $chat = '42'): void
{
    Setting::put('telegram', ['token' => Crypt::encryptString('T'), 'chat_id' => $chat]);
}

it('stores a lead and notifies telegram', function () {
    telegramOn();
    Http::fake();
    $this->postJson('/api/kp-lead', ['company' => 'ООО «Ромашка»', 'city' => 'Казань', 'source' => 'hero'])->assertNoContent();
    expect(KpLead::first()->company)->toBe('ООО «Ромашка»');
    Http::assertSent(fn ($r) => str_contains($r->url(), 'botT/sendMessage') && $r['chat_id'] === '42'
        && str_contains($r['text'], 'Новый запрос КП: ООО «Ромашка», Казань — с блока «hero», '));
});

it('omits empty city in the message', function () {
    telegramOn();
    Http::fake();
    $this->postJson('/api/kp-lead', ['company' => 'Ромашка', 'source' => 'faq'])->assertNoContent();
    Http::assertSent(fn ($r) => str_contains($r['text'], 'Новый запрос КП: Ромашка — с блока «faq»'));
});

it('honeypot silently drops', function () {
    $this->postJson('/api/kp-lead', ['company' => 'X', 'website' => 'spam'])->assertNoContent();
    expect(KpLead::count())->toBe(0);
});

it('validates company', fn () => $this->postJson('/api/kp-lead', ['company' => ''])->assertStatus(422));

it('rate limits per ip', function () {
    foreach (range(1, 5) as $i) {
        $this->postJson('/api/kp-lead', ['company' => "C$i"])->assertNoContent();
    }
    $this->postJson('/api/kp-lead', ['company' => 'C6'])->assertStatus(429);
});

it('telegram failure still stores the lead', function () {
    telegramOn('1');
    Http::fake(fn () => Http::response('', 500));
    $this->postJson('/api/kp-lead', ['company' => 'X'])->assertNoContent();
    expect(KpLead::count())->toBe(1);
});

it('lists leads, toggles contacted mark, shows badge, forbids create/edit', function () {
    $this->actingAs(User::factory()->create());
    $old = KpLead::create(['company' => 'Старая', 'source' => 'hero', 'created_at' => now()->subDay()]);
    $new = KpLead::create(['company' => 'Новая', 'source' => 'faq', 'created_at' => now()]);
    expect(KpLeadResource::getNavigationBadge())->toBe('2');

    Livewire::test(ListKpLeads::class)
        ->assertCanSeeTableRecords([$new, $old], inOrder: true)
        ->callTableAction('contacted', $new)
        ->assertHasNoTableActionErrors();
    expect($new->fresh()->contacted_at)->not->toBeNull()
        ->and(KpLeadResource::getNavigationBadge())->toBe('1');

    Livewire::test(ListKpLeads::class)->callTableAction('uncontacted', $new);
    expect($new->fresh()->contacted_at)->toBeNull();

    Livewire::test(ListKpLeads::class)->callTableAction('delete', $old);
    expect(KpLead::count())->toBe(1)
        ->and(KpLeadResource::canCreate())->toBeFalse()
        ->and(KpLeadResource::canEdit($new))->toBeFalse();
});

it('throttles per real client ip behind a trusted proxy', function () {
    $post = fn (string $client, string $c) => $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->postJson('/api/kp-lead', ['company' => $c], ['X-Forwarded-For' => $client]);
    foreach (range(1, 5) as $i) {
        $post('203.0.113.1', "A$i")->assertNoContent();
        $post('203.0.113.2', "B$i")->assertNoContent();
    }
    $post('203.0.113.1', 'A6')->assertStatus(429);
});

it('ignores spoofed X-Forwarded-For from an untrusted address', function () {
    foreach (range(1, 6) as $i) {
        $r = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->postJson('/api/kp-lead', ['company' => "S$i"], ['X-Forwarded-For' => "203.0.113.$i"]);
        $i <= 5 ? $r->assertNoContent() : $r->assertStatus(429);
    }
});

it('keeps company, city and source on one line and restricts source codes', function () {
    telegramOn();
    Http::fake();
    $this->postJson('/api/kp-lead', ['company' => "Рома\nшка\r\n\tФейк: строка", 'city' => "Ка\nзань", 'source' => "bad\nsource"])->assertNoContent();
    $lead = KpLead::first();
    expect($lead->company)->toBe('Рома шка Фейк: строка')->and($lead->city)->toBe('Ка зань')->and($lead->source)->toBe('unknown');
    Http::assertSent(fn ($r) => ! str_contains($r['text'], "\n") && str_contains($r['text'], 'Рома шка Фейк: строка, Ка зань — с блока «unknown»'));
});
