<x-filament-widgets::widget>
    <div>
        @if ($this->isReady() && isset($this->schema))
            {{ $this->schema }}
        @else
            <x-filament::loading-section />
        @endif
    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
