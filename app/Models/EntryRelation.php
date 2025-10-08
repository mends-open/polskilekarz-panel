<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\MorphPivot;

class EntryRelation extends MorphPivot
{
    protected $fillable = [
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
