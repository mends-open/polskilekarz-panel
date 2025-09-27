<?php

namespace App\Providers;

use App\Models\ChatwootContext;
use App\Models\CloudflareLink;
use App\Models\Document;
use App\Models\DocumentEntry;
use App\Models\Email;
use App\Models\EmailPatient;
use App\Models\EmaProduct;
use App\Models\EmaSubstance;
use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\Entry;
use App\Models\EntryRelation;
use App\Models\Media;
use App\Models\Patient;
use App\Models\PatientPhone;
use App\Models\Phone;
use App\Models\StripeEvent;
use App\Models\Submission;
use App\Models\User;
use App\Services\Chatwoot\ChatwootClient;
use App\Services\Cloudflare\CloudflareClient;
use App\Services\Cloudflare\LinkShortener;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ChatwootClient::class, function ($app) {
            return new ChatwootClient($app->make(HttpFactory::class));
        });

        $this->app->singleton(CloudflareClient::class, function ($app) {
            return new CloudflareClient($app->make(HttpFactory::class));
        });

        $this->app->singleton(LinkShortener::class, function ($app) {
            return new LinkShortener($app->make(CloudflareClient::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');

        Relation::enforceMorphMap([
            0 => User::class,
            1 => Entity::class,
            2 => EntityUser::class,
            3 => Patient::class,
            4 => Entry::class,
            5 => EntryRelation::class,
            6 => Submission::class,
            7 => Document::class,
            8 => DocumentEntry::class,
            9 => Email::class,
            10 => Phone::class,
            11 => EmailPatient::class,
            12 => PatientPhone::class,
            13 => Media::class,
            14 => EmaSubstance::class,
            15 => EmaProduct::class,
            16 => ChatwootContext::class,
            17 => CloudflareLink::class,
            18 => StripeEvent::class,
        ]);
    }
}
