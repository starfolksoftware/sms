<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Current Notification Status
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                <div class="flex items-center">
                    <x-heroicon-s-check-circle class="w-5 h-5 text-green-500 mr-2" />
                    <span class="font-medium text-green-800">Email Notifications</span>
                </div>
                <p class="text-sm text-green-600 mt-1">Active and working</p>
            </div>

            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-center">
                    <x-heroicon-s-bell class="w-5 h-5 text-blue-500 mr-2" />
                    <span class="font-medium text-blue-800">In-App Notifications</span>
                </div>
                <p class="text-sm text-blue-600 mt-1">Active and working</p>
            </div>

            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                <div class="flex items-center">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5 text-yellow-500 mr-2" />
                    <span class="font-medium text-yellow-800">Webhooks</span>
                </div>
                <p class="text-sm text-yellow-600 mt-1">Coming soon</p>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            Event Types & Default Recipients
        </x-slot>

        <div class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                    <span class="font-medium">Deal Created</span>
                    <p class="text-sm text-gray-600">Notifies when a new deal is added to the system</p>
                </div>
                <x-filament::badge color="success">Active</x-filament::badge>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                    <span class="font-medium">Deal Stage Changed</span>
                    <p class="text-sm text-gray-600">Notifies when a deal moves between pipeline stages</p>
                </div>
                <x-filament::badge color="success">Active</x-filament::badge>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                    <span class="font-medium">Deal Won</span>
                    <p class="text-sm text-gray-600">Notifies when a deal is successfully closed</p>
                </div>
                <x-filament::badge color="success">Active</x-filament::badge>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                    <span class="font-medium">Deal Lost</span>
                    <p class="text-sm text-gray-600">Notifies when a deal is marked as lost</p>
                </div>
                <x-filament::badge color="success">Active</x-filament::badge>
            </div>

            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                <div>
                    <span class="font-medium">Deal Reassigned</span>
                    <p class="text-sm text-gray-600">Notifies when deal ownership changes</p>
                </div>
                <x-filament::badge color="success">Active</x-filament::badge>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>