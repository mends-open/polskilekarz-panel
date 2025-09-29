<?php

namespace App\Filament\Widgets\Chatwoot;

use App\Filament\Widgets\BaseWidget;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Widgets\Widget;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContactInfolist extends BaseWidget
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws RequestException
     * @throws ContainerExceptionInterface
     * @throws ConnectionException
     */
    protected function getChatwootContact(): array
    {
        if (! session()->has('chatwoot.current_user_id') || ! session()->has('chatwoot.account_id') || ! session()->has('chatwoot.contact_id') || ! str_contains(session()->get('chatwoot.current_user_id'), '')) {
            return [];
        }
        $account = session()->get('chatwoot.account_id');
        $contact = session()->get('chatwoot.contact_id');
        $user = session()->get('chatwoot.current_user_id');

        return chatwoot()->platform()->impersonate($user)->contacts()->get($account, $contact)['payload'];
    }

    #[On('chatwoot.set-context')]
    public function refreshContext(): void
    {
        $this->reset();
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws RequestException
     */
    public function schema(Schema $schema): Schema
    {
        return $schema
            ->record($this->getChatwootContact())
            ->components([
                Section::make('contact')
                    ->schema([
                        TextEntry::make('id')
                            ->inlineLabel()
                            ->fontFamily(FontFamily::Mono),
                        TextEntry::make('name')
                            ->inlineLabel()
                            ->placeholder('No name'),
                        TextEntry::make('created_at')
                            ->inlineLabel()
                            ->placeholder('No created')
                            ->since(),
                        TextEntry::make('email')
                            ->inlineLabel()
                            ->placeholder('No email'),
                        TextEntry::make('phone_number')
                            ->inlineLabel()
                            ->placeholder('No phone'),
                        TextEntry::make('additional_attributes.country_code')
                            ->inlineLabel()
                            ->badge()
                            ->placeholder('No created'),
                    ])
            ]);
    }
}
