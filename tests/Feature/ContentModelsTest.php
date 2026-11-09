<?php

use App\Models\FaqItem;
use App\Models\Setting;
use App\Models\Topic;
use App\Models\TopicGroup;
use App\Site\PublishQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('assigns increasing sort and orders by it', function () {
    $a = FaqItem::create(['question' => 'A', 'answer' => 'a']);
    $b = FaqItem::create(['question' => 'B', 'answer' => 'b']);
    expect($b->sort)->toBeGreaterThan($a->sort)->and(FaqItem::ordered()->pluck('question')->all())->toBe(['A', 'B']);
});

it('visible scope hides hidden items', function () {
    FaqItem::create(['question' => 'A', 'answer' => 'a', 'is_visible' => false]);
    expect(FaqItem::visible()->count())->toBe(0);
});

it('requests a publish on save and delete', function () {
    $q = Mockery::spy(PublishQueue::class);
    app()->instance(PublishQueue::class, $q);
    $f = FaqItem::create(['question' => 'A', 'answer' => 'a']);
    $f->delete();
    $q->shouldHaveReceived('request')->twice();
});

it('settings round-trip json values and trigger publish', function () {
    $q = Mockery::spy(PublishQueue::class);
    app()->instance(PublishQueue::class, $q);
    Setting::put('hero', ['title' => 'T']);
    Setting::put('hero', ['title' => 'U']);
    expect(Setting::get('hero'))->toBe(['title' => 'U'])->and(Setting::get('missing', 'd'))->toBe('d');
    $q->shouldHaveReceived('request')->twice();
});

it('topics cascade with group', function () {
    $g = TopicGroup::create(['title' => 'G']);
    $g->topics()->create(['title' => 't', 'text' => 'x']);
    $g->delete();
    expect(Topic::count())->toBe(0);
});
