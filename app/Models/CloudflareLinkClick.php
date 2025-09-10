<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Tpetry\PostgresqlEnhanced\Eloquent\Concerns\AutomaticDateFormat;

class CloudflareLinkClick extends Model
{
    use AutomaticDateFormat, HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'cloudflare_link_id',
        'ray_id',
        'cloudflare',
        'headers',
    ];

    protected $casts = [
        'cloudflare' => 'array',
        'headers' => 'array',
    ];

    public function cloudflareLink(): BelongsTo
    {
        return $this->belongsTo(CloudflareLink::class);
    }

    public function rules(): array
    {
        return [
            'cloudflare_link_id' => ['required', 'exists:cloudflare_links,id'],
            'ray_id' => ['required', 'string'],
            'cloudflare' => ['required', 'array'],
            'headers' => ['required', 'array'],
        ];
    }
}

