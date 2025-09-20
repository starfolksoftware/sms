<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-6">
            <!-- Email Notifications Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center mb-4">
                        <x-heroicon-o-envelope class="h-5 w-5 text-gray-400 mr-2" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Email Notifications</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Choose which events should send you email notifications</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Created</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Get notified by email when new deals are created</p>
                            </div>
                            <input type="checkbox" wire:model="deal_created_email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Stage Changed</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Get notified by email when deals move between stages</p>
                            </div>
                            <input type="checkbox" wire:model="deal_stage_changed_email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Won</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Get notified by email when deals are marked as won</p>
                            </div>
                            <input type="checkbox" wire:model="deal_won_email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Lost</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Get notified by email when deals are marked as lost</p>
                            </div>
                            <input type="checkbox" wire:model="deal_lost_email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Assignments</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Get notified by email when deals are assigned to you or reassigned</p>
                            </div>
                            <input type="checkbox" wire:model="deal_assigned_email" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- In-App Notifications Section -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center mb-4">
                        <x-heroicon-o-bell class="h-5 w-5 text-gray-400 mr-2" />
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">In-App Notifications</h3>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Choose which events should show in-app notifications</p>
                    
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Created</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Show in-app notifications when new deals are created</p>
                            </div>
                            <input type="checkbox" wire:model="deal_created_database" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Stage Changed</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Show in-app notifications when deals move between stages</p>
                            </div>
                            <input type="checkbox" wire:model="deal_stage_changed_database" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Won</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Show in-app notifications when deals are marked as won</p>
                            </div>
                            <input type="checkbox" wire:model="deal_won_database" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Lost</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Show in-app notifications when deals are marked as lost</p>
                            </div>
                            <input type="checkbox" wire:model="deal_lost_database" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Deal Assignments</label>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Show in-app notifications when deals are assigned to you or reassigned</p>
                            </div>
                            <input type="checkbox" wire:model="deal_assigned_database" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Preferences
            </button>
        </div>
    </form>
</x-filament-panels::page>
