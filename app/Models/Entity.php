<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class Entity extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes, ValidatesAttributes, AutomaticDateFormat;

    protected $fillable = [
        'name',
        'headers',
        'footers',
    ];

    protected $casts = [
        'headers' => 'array',
        'footers' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(EntityUser::class)
            ->withTimestamps();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'headers' => ['nullable', 'array'],
            'footers' => ['nullable', 'array'],
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('stamps');
        $this->addMediaCollection('logos');
    }
}
