<?php

namespace App\Filament\Widgets\Cloudflare;

use App\Filament\Widgets\BaseTableWidget;
use App\Models\CloudflareLink;
use App\Services\Cloudflare\LinkShortener;
use App\Support\Dashboard\Concerns\InteractsWithDashboardContext;
use App\Support\Metadata\Metadata;
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

class CloudflareLinksTable extends BaseTableWidget
{
    use InteractsWithDashboardContext;

    protected int|string|array $columnSpan = 'full';

    public $tableRecordsPerPage = 3;

    #[On('reset')]
    public function resetComponent(): void
    {
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
            ->emptyStateIcon(Heroicon::OutlinedLink)
            ->emptyStateHeading(__('filament.widgets.cloudflare.links_table.empty_state.heading'))
            ->emptyStateDescription(__('filament.widgets.cloudflare.links_table.empty_state.description'))
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('slug')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.slug.label'))
                            ->badge()
                            ->color('gray')
                            ->placeholder(__('filament.widgets.common.placeholders.blank')),
                        TextColumn::make('url')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.url.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab()
                            ->limit(50),
                        TextColumn::make('entity_type')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.entity_type.label'))
                            ->badge()
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->formatStateUsing(fn (?string $state) => $state ? __('filament.widgets.cloudflare.links_table.enums.entity_types.' . $state) : null)
                            ->color(fn (?string $state) => match ($state) {
                                'invoice' => 'info',
                                'billing_portal' => 'warning',
                                'customer' => 'success',
                                default => 'gray',
                            }),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('request.url')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.request_url.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->limit(50),
                        TextColumn::make('location')
                            ->state(fn (array $record) => (
                            implode(', ', array_filter([
                                $record['request']['cf']['city'] ?? null,
                                $record['request']['cf']['country'] ?? null,
                                $record['request']['cf']['postalCode'] ?? null,
                                $record['request']['cf']['region'] ?? null
                            ]))
                            ))
                            ->placeholder(__('filament.widgets.common.placeholders.blank')),
                        TextColumn::make('request.headers.X-Real-Ip')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.request_ip.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->copyable(),
                    ])->space(2),
                    Stack::make([
                        TextColumn::make('response.status')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.response_status.label'))
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
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.timestamp.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->since(),
                        TextColumn::make('timestamp')
                            ->tooltip(__('filament.widgets.cloudflare.links_table.columns.timestamp_exact.label'))
                            ->placeholder(__('filament.widgets.common.placeholders.blank'))
                            ->dateTime(),
                    ])->space(2),
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
        $contactId = $this->chatwootContext()->contactId;

        if ($contactId === null) {
            return [];
        }

        $links = CloudflareLink::query()
            ->whereRaw("metadata->>'chatwoot_contact_id' = ?", [(string) $contactId])
            ->latest()
            ->get();

        if ($links->isEmpty()) {
            return [];
        }

        $shortener = app(LinkShortener::class);

        $records = [];

        foreach ($links as $link) {
            try {
                $entries = $shortener->entriesFor($link);
            } catch (Throwable $exception) {
                report($exception);

                Log::warning('Failed to fetch Cloudflare link entries', [
                    'link_id' => $link->id,
                    'slug' => $link->slug,
                    'exception' => $exception->getMessage(),
                ]);

                continue;
            }

            $metadata = $link->metadata ?? [];
            $entityType = $this->resolveEntityType($metadata);

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

            foreach (Arr::get($entries, 'entries', []) as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $records[] = $this->makeRecord($link, $entry, $metadata, $entityType, $shortUrl);
            }
        }

        if ($records === []) {
            return [];
        }

        usort($records, function (array $left, array $right): int {
            $leftTimestamp = $left['timestamp'] ?? '';
            $rightTimestamp = $right['timestamp'] ?? '';

            return strcmp((string) $rightTimestamp, (string) $leftTimestamp);
        });

        return $records;
    }

    /**
     * @param  array<string, mixed>  $entry
     * @param  array<string, string>  $metadata
     * @return array<string, mixed>
     */
    protected function makeRecord(CloudflareLink $link, array $entry, array $metadata, string $entityType, ?string $shortUrl): array
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
            'metadata' => $metadata,
            'metadata_summary' => $this->summariseMetadata($metadata),
            'entity_type' => $entityType,
            'timestamp' => $entry['timestamp'] ?? null,
            'request' => is_array($request) ? $request : [],
            'response' => is_array($response) ? $response : [],
        ];
    }

    /**
     * @param  array<string, string>  $metadata
     */
    protected function summariseMetadata(array $metadata): string
    {
        if ($metadata === []) {
            return '';
        }

        $parts = [];

        foreach ($metadata as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $label = $this->metadataLabel((string) $key);
            $parts[] = sprintf('%s: %s', $label, (string) $value);
        }

        return implode(', ', $parts);
    }

    protected function metadataLabel(string $key): string
    {
        $translationKey = 'filament.widgets.cloudflare.links_table.metadata_keys.' . $key;
        $translation = __($translationKey);

        if ($translation === $translationKey) {
            return Str::title(str_replace('_', ' ', $key));
        }

        return $translation;
    }

    /**
     * @param  array<string, string>  $metadata
     */
    protected function resolveEntityType(array $metadata): string
    {
        if (array_key_exists(Metadata::KEY_STRIPE_INVOICE_ID, $metadata)) {
            return 'invoice';
        }

        if (array_key_exists(Metadata::KEY_STRIPE_BILLING_PORTAL_SESSION, $metadata)) {
            return 'billing_portal';
        }

        return 'link';
    }
}
