<x-filament-widgets::widget wire:poll>
    <div>
        @if ($this->isReady() && isset($this->schema))
            {{ $this->schema }}
        @else
            <x-filament::loading-section />
        @endif
    </div>
    <x-filament-actions::modals />
</x-filament-widgets::widget>
