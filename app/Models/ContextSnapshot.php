<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class ContextSnapshot extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'contextable_id',
        'contextable_type',
        'snapshot',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'snapshot' => 'array',
    ];

    public function contextable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rules(): array
    {
        return [
            'contextable_id' => ['required', 'integer'],
            'contextable_type' => ['required', 'integer'],
            'snapshot' => ['required', 'array'],
        ];
    }
}
