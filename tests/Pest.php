<?php

use Database\Factories\MediaFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Хелперы для тестов фото: кладут оригиналы на диск `local` (в тестах — Storage::fake).
 */
trait StoresMediaFiles
{
    /** «3000x2000.jpg» → сгенерированный GD jpg такого размера в media-originals/. */
    public function store(string $name): string
    {
        [$w, $h] = array_map('intval', explode('x', pathinfo($name, PATHINFO_FILENAME)));

        return MediaFactory::fakeJpeg($w, $h, $name);
    }

    /** Копирует tests/fixtures/{name} в media-originals/. */
    public function storeFixture(string $name): string
    {
        $path = 'media-originals/'.$name;
        Storage::disk('local')->put($path, file_get_contents(__DIR__.'/fixtures/'.$name));

        return $path;
    }
}
