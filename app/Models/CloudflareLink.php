<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use App\Models\Entity;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class CloudflareLink extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'key',
        'value',
    ];

    public function clicks(): HasMany
    {
        return $this->hasMany(CloudflareLinkClick::class);
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string'],
            'value' => ['required', 'url'],
        ];
    }
}
