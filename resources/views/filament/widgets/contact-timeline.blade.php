@php
    $timeline = $this->getTimeline();
    $hasRecord = $this->record instanceof \App\Models\Contact;
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Contact Timeline
        </x-slot>
        
        @if($hasRecord)
            <x-slot name="headerActions">
                <x-filament::button
                    wire:click="resetFilters"
                    size="sm"
                    color="gray"
                    outlined
                >
                    Reset Filters
                </x-filament::button>
            </x-slot>
        @endif

        <div class="space-y-6">
            @if($hasRecord)
                {{-- Filters --}}
                <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Event Types</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model.live="filters.types" value="tasks" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Tasks</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model.live="filters.types" value="deals" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Deals</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="checkbox" wire:model.live="filters.types" value="system" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">System Events</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">From Date</label>
                            <input type="date" wire:model.live="filters.date_from" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">To Date</label>
                            <input type="date" wire:model.live="filters.date_to" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600">
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                @if($timeline && $timeline->count() > 0)
                    <div class="space-y-4">
                        @foreach($timeline->items() as $event)
                        <div class="flex items-start space-x-4 p-4 bg-white dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                            {{-- Icon --}}
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-{{ $this->getEventTypeColor($event->type) }}-100 dark:bg-{{ $this->getEventTypeColor($event->type) }}-800">
                                    <x-filament::icon 
                                        :icon="$this->getEventTypeIcon($event->type)"
                                        class="w-5 h-5 text-{{ $this->getEventTypeColor($event->type) }}-600 dark:text-{{ $this->getEventTypeColor($event->type) }}-400"
                                    />
                                </div>
                            </div>

                            {{-- Content --}}
                            <div class="flex-grow min-w-0">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {{ $event->title }}
                                    </h4>
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $this->getEventTypeColor($event->type) }}-100 text-{{ $this->getEventTypeColor($event->type) }}-800 dark:bg-{{ $this->getEventTypeColor($event->type) }}-800 dark:text-{{ $this->getEventTypeColor($event->type) }}-100">
                                            {{ ucfirst($event->type) }}
                                        </span>
                                        <time class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $event->timestamp->diffForHumans() }}
                                        </time>
                                    </div>
                                </div>

                                @if($event->summary)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ $event->summary }}
                                    </p>
                                @endif

                                <div class="mt-2 flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        @if($event->actor)
                                            <div class="flex items-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                                                <x-filament::icon 
                                                    icon="heroicon-m-user"
                                                    class="w-4 h-4"
                                                />
                                                <span>{{ $event->actor['name'] }}</span>
                                            </div>
                                        @endif
                                        
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <x-filament::icon 
                                                icon="heroicon-m-clock"
                                                class="w-4 h-4 inline mr-1"
                                            />
                                            {{ $event->timestamp->format('M j, Y g:i A') }}
                                        </div>
                                    </div>

                                    @if($event->link)
                                        <x-filament::button
                                            :href="$event->link['url']"
                                            size="sm"
                                            color="gray"
                                            outlined
                                            tag="a"
                                        >
                                            {{ $event->link['label'] }}
                                        </x-filament::button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="mt-6">
                    {{ $timeline->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <x-filament::icon 
                        icon="heroicon-o-clock"
                        class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600"
                    />
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        No timeline events found for the selected filters.
                    </p>
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <x-filament::icon 
                    icon="heroicon-o-user"
                    class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-600"
                />
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                    Please select a contact to view timeline.
                </p>
            </div>
        @endif
    </div>
</x-filament::section>
</x-filament-widgets::widget>