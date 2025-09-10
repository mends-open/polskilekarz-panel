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
        'user_id',
        'entity_id',
        'patient_id',
    ];

    public function clicks(): HasMany
    {
        return $this->hasMany(CloudflareLinkClick::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function rules(): array
    {
        return [
            'key' => ['required', 'string'],
            'value' => ['required', 'url'],
            'user_id' => ['required', 'exists:users,id'],
            'entity_id' => ['required', 'exists:entities,id'],
            'patient_id' => ['required', 'exists:patients,id'],
        ];
    }
}
