<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class ChatwootContext extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'contextable_id',
        'contextable_type',
        'chatwoot_account_id',
        'chatwoot_conversation_id',
        'chatwoot_contact_id',
        'chatwoot_user_id',
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
            'chatwoot_account_id' => ['required', 'integer'],
            'chatwoot_conversation_id' => ['required', 'integer'],
            'chatwoot_contact_id' => ['required', 'integer'],
            'chatwoot_user_id' => ['required', 'integer'],
        ];
    }
}
