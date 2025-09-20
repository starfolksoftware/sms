@php
    $timeline = $this->getTimeline();
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Contact Timeline
        </x-slot>
        
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

        <div class="space-y-6">
            {{-- Filters --}}
            <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                {{ $this->form }}
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
        </div>
    </x-filament::section>
</x-filament-widgets::widget>