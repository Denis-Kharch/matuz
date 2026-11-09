<?php

namespace App\Models;

use App\Models\Concerns\TriggersPublish;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use TriggersPublish;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->find($key);

        return $row ? $row->value : $default;
    }

    public static function put(string $key, mixed $value): void
    {
        static::row($key, $value)->save();
    }

    /** Запись без событий модели: служебные значения (статус публикации) не запускают пересборку. */
    public static function putQuietly(string $key, mixed $value): void
    {
        static::row($key, $value)->saveQuietly();
    }

    private static function row(string $key, mixed $value): static
    {
        $row = static::query()->find($key) ?? new static(['key' => $key]);
        $row->value = $value;

        return $row;
    }
}
