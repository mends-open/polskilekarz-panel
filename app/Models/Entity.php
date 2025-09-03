<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'name',
        'headers',
        'footers',
        'stamps',
        'logos',
    ];

    protected $casts = [
        'headers' => 'array',
        'footers' => 'array',
        'stamps' => 'array',
        'logos' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(EntityUser::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'headers' => ['nullable', 'array'],
            'footers' => ['nullable', 'array'],
            'stamps' => ['nullable', 'array'],
            'logos' => ['nullable', 'array'],
        ];
    }
}
