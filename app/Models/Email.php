<?php

namespace App\Models;

use App\Models\Concerns\ValidatesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Email extends Model
{
    use HasFactory, SoftDeletes, ValidatesAttributes;

    protected $fillable = [
        'email',
    ];

    public function patients(): BelongsToMany
    {
        return $this->belongsToMany(Patient::class, 'email_patient')
            ->wherePivotNull('deleted_at');
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
