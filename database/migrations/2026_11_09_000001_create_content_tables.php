<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function listTable(string $name, callable $columns): void
    {
        Schema::create($name, function (Blueprint $t) use ($columns) {
            $t->id();
            $columns($t);
            $t->unsignedInteger('sort')->default(0);
            $t->boolean('is_visible')->default(true);
            $t->timestamps();
        });
    }

    public function up(): void
    {
        $this->listTable('principles', fn ($t) => $t->text('text'));
        $this->listTable('experience_stages', function ($t) {
            $t->string('title');
            $t->string('period')->nullable();
            $t->text('text');
        });
        $this->listTable('formats', function ($t) {
            $t->string('title');
            $t->json('tags');
            $t->text('description');
        });
        $this->listTable('topic_groups', fn ($t) => $t->string('title'));
        $this->listTable('topics', function ($t) {
            $t->foreignId('topic_group_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('text');
        });
        $this->listTable('event_services', function ($t) {
            $t->string('title');
            $t->text('text');
        });
        $this->listTable('case_studies', function ($t) {
            $t->string('slug')->unique();
            $t->enum('direction', ['training', 'events']);
            $t->string('title');
            $t->string('client');
            $t->string('date_label');
            $t->string('people');
            $t->text('request');
            $t->json('plan');
            $t->text('result');
            $t->text('review');
            $t->boolean('demo')->default(false);
        });
        $this->listTable('events', function ($t) {
            $t->string('slug')->unique();
            $t->string('title');
            $t->date('date');
            $t->string('client');
            $t->text('description');
            $t->boolean('demo')->default(false);
        });
        $this->listTable('partners', function ($t) {
            $t->string('name');
            $t->string('short');
            $t->text('description');
            $t->string('url')->nullable();
        });
        $this->listTable('certificates', function ($t) {
            $t->string('title');
            $t->text('description');
            $t->string('year');
        });
        $this->listTable('gallery_items', fn ($t) => $t->string('caption'));
        $this->listTable('faq_items', function ($t) {
            $t->string('question');
            $t->text('answer');
        });

        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value');
            $t->timestamps();
        });
        Schema::create('kp_leads', function (Blueprint $t) {
            $t->id();
            $t->string('company');
            $t->string('city')->nullable();
            $t->string('source');
            $t->timestamp('contacted_at')->nullable();
            $t->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['kp_leads', 'settings', 'faq_items', 'gallery_items', 'certificates', 'partners', 'events', 'case_studies', 'event_services', 'topics', 'topic_groups', 'formats', 'experience_stages', 'principles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
