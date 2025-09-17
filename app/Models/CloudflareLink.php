<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class CloudflareLink extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'slug',
        'url',
    ];

    public function clicks(): HasMany
    {
        return $this->hasMany(CloudflareLinkClick::class);
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string'],
            'url' => ['required', 'url'],
        ];
    }
}
