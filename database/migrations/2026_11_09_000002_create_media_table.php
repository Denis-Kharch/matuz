<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $t) {
            $t->id();
            // Владелец (модель) либо одиночный блок настроек через slot (hero, hero_mobile, about…).
            $t->nullableMorphs('owner', 'media_owner_index');
            $t->string('slot')->nullable()->index();
            $t->string('collection')->default('default');
            $t->string('original_path');
            $t->json('crop')->nullable();
            $t->unsignedTinyInteger('focal_x')->default(50);
            $t->unsignedTinyInteger('focal_y')->default(50);
            $t->string('alt')->nullable();
            $t->string('caption')->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->string('key')->nullable()->unique();
            $t->json('widths')->nullable();
            $t->json('formats')->nullable();
            $t->unsignedInteger('width')->nullable();
            $t->unsignedInteger('height')->nullable();
            $t->timestamps();

            $t->index(['owner_type', 'owner_id', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
