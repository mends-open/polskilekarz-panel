<?php

namespace App\Filament\Widgets\Stripe;

use App\Filament\Widgets\BaseWidget;
use Arr;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Enums\TextSize;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Stripe\Exception\ApiErrorException;


class CustomerInfolist extends BaseWidget
{
    /**
     * @throws ContainerExceptionInterface
     * @throws ApiErrorException
     * @throws NotFoundExceptionInterface
     */
    protected function getStripeCustomer(): array
    {
        if (! session()->has('stripe.customer_id')) {
            return [];
        }
        return stripe()->customers->retrieve(session()->get('stripe.customer_id'))->toArray();
    }

    #[On('stripe.set-context')]
    public function refreshContext(): void
    {
        $this->reset();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ApiErrorException
     * @throws NotFoundExceptionInterface
     */
    public function schema(Schema $schema): Schema
    {
        $contactReady = session()->has('chatwoot.contact_id');
        return $schema
            ->state($this->getStripeCustomer())
            ->components([
                Section::make('customer')
                    ->headerActions([
                        Action::make('fetchFromContact')
                            ->outlined()
                            ->color($contactReady ? 'primary' : 'gray')
                            ->disabled(!$contactReady)
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->inlineLabel()
                            ->fontFamily(FontFamily::Mono),
                        TextEntry::make('name')
                            ->inlineLabel()
                            ->placeholder('No name'),
                        TextEntry::make('created')
                            ->inlineLabel()
                            ->placeholder('No created')
                            ->since(),
                        TextEntry::make('email')
                            ->inlineLabel()
                            ->placeholder('No email'),
                        TextEntry::make('phone')
                            ->inlineLabel()
                            ->placeholder('No phone'),
                        TextEntry::make('currency')
                            ->state(fn ($record) => Str::upper(Arr::get($record, 'currency')))
                            ->inlineLabel()
                            ->badge()
                            ->placeholder('No currency'),
                        ])
            ]);
    }

}
