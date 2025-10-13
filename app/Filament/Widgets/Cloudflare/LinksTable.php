<?php

namespace App\Filament\Widgets\Cloudflare;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Cloudflare\Concerns\InteractsWithCloudflareLinks;
use App\Models\CloudflareLink;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Services\Cloudflare\LinkShortener;
use App\Support\Dashboard\Concerns\RefreshesDashboardContextOnBoot;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class LinksTable extends BaseTableWidget
{
    use InteractsWithCloudflareLinks;
    use InteractsWithDashboardContext;
    use RefreshesDashboardContextOnBoot;
    protected int|string|array $columnSpan = 'full';

    public $tableRecordsPerPage = 5;

    #[On('reset')]
    public function resetComponent(): void
    {
        $this->resetCloudflareLinksCache();
        $this->resetTable();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function isReady(): bool
    {
        return $this->dashboardContextIsReady(
            fn (): bool => $this->chatwootContext()->hasContact(),
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('filament.widgets.cloudflare.links_table.heading'))
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $records = collect($this->links());

                $items = $records
                    ->forPage($page, $recordsPerPage)
                    ->values()
                    ->all();

                return new LengthAwarePaginator(
                    items: $items,
                    total: $records->count(),
                    perPage: $recordsPerPage,
                    currentPage: $page,
                );
            })
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25, 50])
            ->emptyStateIcon(Heroicon::OutlinedLink)
            ->emptyStateHeading(__('filament.widgets.cloudflare.links_table.empty_state.heading'))
            ->emptyStateDescription(__('filament.widgets.cloudflare.links_table.empty_state.description'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('slug')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.slug.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('gray')
                            ->copyable(),
                        TextColumn::make('short_url')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.short_url.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->copyable()
                            ->limit(50),
                        TextColumn::make('url')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.url.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->limit(50),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('entity_type')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.entity_type.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => $state ? __('filament.widgets.cloudflare.enums.entity_types.' . $state) : null)
                            ->color(fn (?string $state) => match ($state) {
                                'invoice' => 'info',
                                'billing_portal' => 'warning',
                                'customer' => 'success',
                                default => 'gray',
                            }),
                        TextColumn::make('entity_identifier')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.entity_identifier.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('gray')
                            ->copyable(),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('created_at')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.created_at.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->since(),
                        TextColumn::make('created_at')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.created_at_exact.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->dateTime(),
                    ])->space(1),
                ])->from('lg'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    #[Computed(persist: true)]
    protected function links(): array
    {
        $links = $this->cloudflareLinks();

        if ($links->isEmpty()) {
            return [];
        }

        $shortener = app(LinkShortener::class);

        return $links
            ->map(fn (CloudflareLink $link) => $this->makeRecord($link, $shortener))
            ->all();
    }

    protected function makeRecord(CloudflareLink $link, LinkShortener $shortener): array
    {
        $metadata = Arr::wrap($link->metadata);
        $entityType = $this->resolveEntityType($metadata);

        return [
            'id' => $link->getKey(),
            'slug' => $link->slug,
            'short_url' => $shortener->buildShortLink($link->slug),
            'url' => $link->url,
            'entity_type' => $entityType,
            'entity_identifier' => $this->resolveEntityIdentifier($metadata, $entityType),
            'created_at' => $link->created_at?->toImmutable(),
        ];
    }
}
