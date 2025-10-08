<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class CloudflareLink extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'slug',
        'url',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            'url' => ['required', 'url'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
