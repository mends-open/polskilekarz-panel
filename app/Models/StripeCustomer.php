<?php

namespace App\Models;

use App\Models\ChatwootContext;
use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class StripeCustomer extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'stripe_id',
    ];

    public function contexts(): MorphMany
    {
        return $this->morphMany(ChatwootContext::class, 'contextable');
    }

    public function rules(): array
    {
        return [
            'stripe_id' => ['required', 'string'],
        ];
    }
}

