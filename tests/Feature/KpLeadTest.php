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
