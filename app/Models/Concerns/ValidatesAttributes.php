<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait ValidatesAttributes
{
    protected static function bootValidatesAttributes(): void
    {
        static::saving(function ($model) {
            if (method_exists($model, 'rules')) {
                $validator = Validator::make($model->attributesToArray(), $model->rules());
                if ($validator->fails()) {
                    throw new ValidationException($validator);
                }
            }
        });
    }
}
