<?php

use App\Support\CaseDate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Кейсы на сайте — от новых к старым: настоящая дата проведения вместо одной текстовой подписи.
// У существующих кейсов дата заполняется из подписи («март 2025» → 2025-03-01); подпись становится необязательной.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('case_studies', function (Blueprint $t) {
            $t->date('held_at')->nullable()->after('date_label');
            $t->string('date_label')->nullable()->change();
        });

        foreach (DB::table('case_studies')->select('id', 'date_label')->get() as $row) {
            DB::table('case_studies')->where('id', $row->id)->update(['held_at' => CaseDate::parse($row->date_label)]);
        }
    }

    public function down(): void
    {
        Schema::table('case_studies', function (Blueprint $t) {
            $t->dropColumn('held_at');
        });
    }
};
