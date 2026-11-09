<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KpLead extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['contacted_at' => 'datetime'];
    }
}
