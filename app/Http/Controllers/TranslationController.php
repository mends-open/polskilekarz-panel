<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class TranslationController extends Controller
{
    /**
     * @var array<int, string>
     */
    private array $resources = [
        'document',
        'ema_product',
        'entity',
        'entry',
        'patient',
        'submission',
        'user',
    ];

    public function __invoke(Request $request, ?string $locale = null): JsonResponse
    {
        $locale = $locale
            ?? $request->getPreferredLanguage()
            ?? app()->getLocale();

        $translations = [];

        foreach ($this->resources as $resource) {
            $translations[$resource] = Lang::get($resource, [], $locale);
        }

        return response()->json([
            'locale' => $locale,
            'translations' => $translations,
        ]);
    }
}
