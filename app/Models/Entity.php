<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Entity extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, ValidatesAttributes;

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
        return $this->belongsToMany(User::class, 'entity_user')
            ->wherePivotNull('deleted_at');
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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('stamps');
        $this->addMediaCollection('logos');
    }
}
