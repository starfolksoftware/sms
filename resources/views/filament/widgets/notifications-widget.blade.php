<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Recent Notifications
            @if($this->getNotifications()->where('read_at', null)->count() > 0)
                <x-filament::badge color="danger" size="sm" class="ml-2">
                    {{ $this->getNotifications()->where('read_at', null)->count() }} unread
                </x-filament::badge>
            @endif
        </x-slot>

        <x-slot name="headerActions">
            @if($this->getNotifications()->where('read_at', null)->count() > 0)
                <x-filament::button
                    wire:click="markAllAsRead"
                    size="sm"
                    color="gray"
                >
                    Mark all as read
                </x-filament::button>
            @endif
        </x-slot>

        <div class="space-y-3">
            @forelse($this->getNotifications() as $notification)
                <div class="flex items-start space-x-3 p-3 rounded-lg border {{ $notification['read_at'] ? 'bg-gray-50 border-gray-200' : 'bg-blue-50 border-blue-200' }}">
                    <div class="flex-shrink-0">
                        @switch($notification['type'])
                            @case('DealWonNotification')
                                <x-heroicon-s-trophy class="w-5 h-5 text-green-500" />
                                @break
                            @case('DealLostNotification')
                                <x-heroicon-s-x-circle class="w-5 h-5 text-red-500" />
                                @break
                            @case('DealCreatedNotification')
                                <x-heroicon-s-plus-circle class="w-5 h-5 text-blue-500" />
                                @break
                            @case('DealStageChangedNotification')
                                <x-heroicon-s-arrow-right class="w-5 h-5 text-yellow-500" />
                                @break
                            @case('DealAssignedNotification')
                                <x-heroicon-s-user-plus class="w-5 h-5 text-purple-500" />
                                @break
                            @default
                                <x-heroicon-s-bell class="w-5 h-5 text-gray-500" />
                        @endswitch
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    @switch($notification['type'])
                                        @case('DealWonNotification')
                                            Deal Won: {{ $notification['data']['deal_title'] }}
                                            @break
                                        @case('DealLostNotification')
                                            Deal Lost: {{ $notification['data']['deal_title'] }}
                                            @break
                                        @case('DealCreatedNotification')
                                            New Deal: {{ $notification['data']['deal_title'] }}
                                            @break
                                        @case('DealStageChangedNotification')
                                            Stage Changed: {{ $notification['data']['deal_title'] }}
                                            @break
                                        @case('DealAssignedNotification')
                                            Deal Reassigned: {{ $notification['data']['deal_title'] }}
                                            @break
                                        @default
                                            Notification
                                    @endswitch
                                </p>
                                <p class="text-sm text-gray-500">
                                    @if(isset($notification['data']['contact_name']))
                                        Contact: {{ $notification['data']['contact_name'] }}
                                    @endif
                                    @if(isset($notification['data']['amount']) && isset($notification['data']['currency']))
                                        • Amount: {{ number_format($notification['data']['amount'], 2) }} {{ $notification['data']['currency'] }}
                                    @endif
                                    @if(isset($notification['data']['won_amount']) && isset($notification['data']['currency']))
                                        • Won: {{ number_format($notification['data']['won_amount'], 2) }} {{ $notification['data']['currency'] }}
                                    @endif
                                </p>
                                @if(isset($notification['data']['from_stage']) && isset($notification['data']['to_stage']))
                                    <p class="text-xs text-gray-400">
                                        {{ $notification['data']['from_stage'] }} → {{ $notification['data']['to_stage'] }}
                                    </p>
                                @endif
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-400">
                                    {{ $notification['created_at']->diffForHumans() }}
                                </span>
                                @if(!$notification['read_at'])
                                    <x-filament::button
                                        wire:click="markAsRead('{{ $notification['id'] }}')"
                                        size="xs"
                                        color="gray"
                                        outlined
                                    >
                                        Mark read
                                    </x-filament::button>
                                @endif
                            </div>
                        </div>

                        @if(isset($notification['data']['deal_id']))
                            <div class="mt-2">
                                <a href="{{ route('filament.admin.resources.deals.view', $notification['data']['deal_id']) }}" 
                                   class="text-sm text-blue-600 hover:text-blue-800">
                                    View Deal →
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-6">
                    <x-heroicon-o-bell-slash class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No notifications</h3>
                    <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>