<?php

namespace Database\Factories;

use App\Models\CaseStudy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CaseStudy>
 */
class CaseStudyFactory extends Factory
{
    protected $model = CaseStudy::class;

    public function definition(): array
    {
        $title = 'Кейс '.Str::random(6);

        return [
            'slug' => Str::slug(Str::random(10)),
            'direction' => 'training',
            'title' => $title,
            'client' => 'Клиент',
            'date_label' => '2026',
            'people' => '20 человек',
            'request' => '<p>Запрос</p>',
            'plan' => ['Шаг'],
            'result' => '<p>Результат</p>',
            'review' => '<p>Отзыв</p>',
        ];
    }
}
