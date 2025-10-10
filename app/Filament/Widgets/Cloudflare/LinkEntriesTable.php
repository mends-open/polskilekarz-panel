<?php

namespace App\Filament\Widgets\Cloudflare;

use App\Filament\Widgets\BaseTableWidget;
use App\Filament\Widgets\Cloudflare\Concerns\InteractsWithCloudflareLinks;
use App\Models\CloudflareLink;
use App\Services\Cloudflare\LinkShortener;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use Filament\Actions\BulkActionGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Throwable;

class LinkEntriesTable extends BaseTableWidget
{
    use InteractsWithCloudflareLinks;
    use InteractsWithDashboardContext;

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
            ->heading(__('filament.widgets.cloudflare.link_entries_table.heading'))
            ->records(function (int $page, int $recordsPerPage): LengthAwarePaginator {
                $records = collect($this->linkEntries);

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
            ->emptyStateIcon(Heroicon::OutlinedCursorArrowRays)
            ->emptyStateHeading(__('filament.widgets.cloudflare.link_entries_table.empty_state.heading'))
            ->emptyStateDescription(__('filament.widgets.cloudflare.link_entries_table.empty_state.description'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('slug')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.slug.label'))
                            ->badge()
                            ->color('gray')
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->copyable(),
                        TextColumn::make('short_url')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.short_url.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->copyable()
                            ->limit(50),
                        TextColumn::make('url')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.url.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->limit(50),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('entity_type')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.entity_type.label'))
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
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.entity_identifier.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('gray')
                            ->copyable(),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('request.url')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.request_url.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->limit(50),
                        TextColumn::make('request.method')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.request_method.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color('gray'),
                        TextColumn::make('location')
                            ->state(fn (array $record) => (
                                implode(', ', array_filter([
                                    $record['request']['cf']['city'] ?? null,
                                    $record['request']['cf']['country'] ?? null,
                                    $record['request']['cf']['postalCode'] ?? null,
                                    $record['request']['cf']['region'] ?? null,
                                ]))
                            ))
                            ->placeholder(__('filament.widgets.common.placeholders.blank')),
                        TextColumn::make('request.headers.X-Real-Ip')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.request_ip.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->copyable(),
                    ])->space(1),
                    Stack::make([
                        TextColumn::make('response.status')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.response_status.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->badge()
                            ->color(fn (?int $state) => match ($state) {
                                200, 201, 202, 204 => 'success',
                                301, 302, 307, 308 => 'info',
                                400, 401, 403, 404 => 'warning',
                                500, 502, 503, 504 => 'danger',
                                default => 'secondary',
                            }),
                        TextColumn::make('timestamp')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.timestamp.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->since(),
                        TextColumn::make('timestamp')
                            ->tooltip(__('filament.widgets.cloudflare.link_entries_table.columns.timestamp_exact.label'))
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
    protected function linkEntries(): array
    {
        $links = $this->cloudflareLinks();

        if ($links->isEmpty()) {
            return [];
        }

        $shortener = app(LinkShortener::class);

        return $links
            ->flatMap(function (CloudflareLink $link) use ($shortener) {
                try {
                    $entries = $shortener->entriesFor($link);
                } catch (Throwable $exception) {
                    report($exception);

                    Log::warning('Failed to fetch Cloudflare link entries', [
                        'link_id' => $link->id,
                        'slug' => $link->slug,
                        'exception' => $exception->getMessage(),
                    ]);

                    return collect();
                }

                $metadata = Arr::wrap($link->metadata);
                $entityType = $this->resolveEntityType($metadata);
                $entityIdentifier = $this->resolveEntityIdentifier($metadata, $entityType);

                $shortUrl = $entries['short_url'] ?? null;

                if ($shortUrl === null) {
                    try {
                        $shortUrl = $shortener->buildShortLink($link->slug);
                    } catch (Throwable $exception) {
                        report($exception);

                        Log::warning('Failed to build Cloudflare short link URL', [
                            'link_id' => $link->id,
                            'slug' => $link->slug,
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }

                return collect(Arr::get($entries, 'entries', []))
                    ->filter(fn ($entry) => is_array($entry))
                    ->map(fn (array $entry) => $this->makeRecord($link, $entry, $entityType, $entityIdentifier, $shortUrl));
            })
            ->filter()
            ->sortByDesc(fn (array $record) => (string) ($record['timestamp'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    protected function makeRecord(CloudflareLink $link, array $entry, string $entityType, ?string $entityIdentifier, ?string $shortUrl): array
    {
        $request = $entry['request'] ?? [];
        $response = $entry['response'] ?? [];

        $headers = is_array($request) ? ($request['headers'] ?? []) : [];

        if (is_array($request)) {
            $request['headers'] = $headers;
        }

        $entryKey = (string) ($entry['key'] ?? '');

        return [
            'id' => $entryKey !== ''
                ? $entryKey
                : sprintf('%s-%s', $link->id, $entry['index'] ?? Str::uuid()),
            'slug' => $link->slug,
            'short_url' => $shortUrl,
            'url' => $link->url,
            'entity_type' => $entityType,
            'entity_identifier' => $entityIdentifier,
            'timestamp' => $entry['timestamp'] ?? null,
            'request' => is_array($request) ? $request : [],
            'response' => is_array($response) ? $response : [],
        ];
    }
}
