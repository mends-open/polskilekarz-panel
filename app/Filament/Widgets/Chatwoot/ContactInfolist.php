<?php

namespace App\Filament\Widgets\Chatwoot;

use App\Filament\Widgets\BaseSchemaWidget;
use App\Support\Dashboard\DashboardContext;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Livewire\Attributes\On;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ContactInfolist extends BaseSchemaWidget
{
    protected DashboardContext $dashboardContext;

    public function boot(DashboardContext $dashboardContext): void
    {
        $this->dashboardContext = $dashboardContext;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RequestException
     * @throws ContainerExceptionInterface
     * @throws ConnectionException
     */
    protected function getChatwootContact(): array
    {
        $context = $this->dashboardContext->chatwoot();

        if (! $context->canImpersonate()) {
            return [];
        }

        return chatwoot()
            ->platform()
            ->impersonate($context->currentUserId)
            ->contacts()
            ->get($context->accountId, $context->contactId)['payload'];
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function isReady(): bool
    {
        return $this->dashboardContext->isReady();
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
