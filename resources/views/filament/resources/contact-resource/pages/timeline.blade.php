<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Timeline Filters --}}
        <x-filament::section>
            <x-slot name="heading">
                Timeline Filters
            </x-slot>
            
            <x-slot name="description">
                Filter timeline events by type and date range
            </x-slot>

            <div class="space-y-6">
                {{ $this->filterForm }}
            </div>
        </x-filament::section>

        {{-- Timeline Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Contact Timeline
            </x-slot>
            
            <x-slot name="description">
                Chronological view of all contact interactions
            </x-slot>

            <div class="space-y-6">
                {{ $this->table }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>