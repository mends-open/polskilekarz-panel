<?php

namespace App\Filament\Widgets\Chatwoot;

use App\Filament\Widgets\BaseSchemaWidget;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Attributes\Session;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContactInfolist extends BaseSchemaWidget
{

    /**
     * @throws NotFoundExceptionInterface
     * @throws RequestException
     * @throws ContainerExceptionInterface
     * @throws ConnectionException
     */
    protected function getChatwootContact(): array
    {
        $account = session()->get('chatwoot.account_id');
        $contact = session()->get('chatwoot.contact_id');
        $user = session()->get('chatwoot.current_user_id');

        return chatwoot()->platform()->impersonate($user)->contacts()->get($account, $contact)['payload'];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function isReady(): bool
    {
        return session()->get('ready');
    }

    #[On('reset')]
    public function resetComponent(): void
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
            ->state($this->getChatwootContact())
            ->components([
                Section::make('contact')
                    ->headerActions([
                        Action::make('reset')
                            ->action(fn () => $this->reset())
                            ->hiddenLabel()
                            ->icon(Heroicon::OutlinedArrowPath)
                            ->link()
                    ])
                    ->schema([
                        TextEntry::make('id')
                            ->badge()
                            ->color('gray')
                            ->inlineLabel(),
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
