<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntityUser extends Pivot
{
    use SoftDeletes, ValidatesAttributes;

    protected $table = 'entity_user';

    protected $fillable = [
        'entity_id',
        'user_id',
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rules(): array
    {
        return [
            'entity_id' => ['required', 'exists:entities,id'],
            'user_id' => ['required', 'exists:users,id'],
        ];
    }
}
