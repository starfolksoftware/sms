<x-filament-panels::page>
    <div class="space-y-6">
        <form wire:submit="save" class="space-y-6">
            <!-- Email Notifications Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <x-heroicon-o-envelope class="w-5 h-5 text-gray-500 mr-2" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Email Notifications</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Choose which events should send you email notifications</p>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input wire:model="data.deal_created_email" type="checkbox" id="deal_created_email" 
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_created_email" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Created
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Get notified by email when new deals are created</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_stage_changed_email" type="checkbox" id="deal_stage_changed_email"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_stage_changed_email" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Stage Changed
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Get notified by email when deals move between stages</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_won_email" type="checkbox" id="deal_won_email"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_won_email" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Won
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Get notified by email when deals are marked as won</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_lost_email" type="checkbox" id="deal_lost_email"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_lost_email" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Lost
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Get notified by email when deals are marked as lost</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_assigned_email" type="checkbox" id="deal_assigned_email"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_assigned_email" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Assignments
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Get notified by email when deals are assigned to you or reassigned</p>
                    </div>
                </div>
            </div>

            <!-- In-App Notifications Section -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <div class="flex items-center mb-4">
                    <x-heroicon-o-bell class="w-5 h-5 text-gray-500 mr-2" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">In-App Notifications</h3>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Choose which events should show in-app notifications</p>
                
                <div class="space-y-4">
                    <div class="flex items-center">
                        <input wire:model="data.deal_created_database" type="checkbox" id="deal_created_database"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_created_database" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Created
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Show in-app notifications when new deals are created</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_stage_changed_database" type="checkbox" id="deal_stage_changed_database"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_stage_changed_database" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Stage Changed
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Show in-app notifications when deals move between stages</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_won_database" type="checkbox" id="deal_won_database"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_won_database" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Won
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Show in-app notifications when deals are marked as won</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_lost_database" type="checkbox" id="deal_lost_database"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_lost_database" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Lost
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Show in-app notifications when deals are marked as lost</p>
                    </div>
                    
                    <div class="flex items-center">
                        <input wire:model="data.deal_assigned_database" type="checkbox" id="deal_assigned_database"
                               class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <label for="deal_assigned_database" class="ml-2 text-sm text-gray-900 dark:text-white">
                            Deal Assignments
                        </label>
                        <p class="ml-2 text-xs text-gray-500">Show in-app notifications when deals are assigned to you or reassigned</p>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end">
                <button type="submit" 
                        class="bg-primary-600 hover:bg-primary-700 text-white font-medium py-2 px-4 rounded-lg shadow transition-colors duration-200">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
