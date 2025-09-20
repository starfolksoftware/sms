<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('notifications', function (SettingsBlueprint $blueprint): void {
            // Deal Created Settings
            $blueprint->add('deal_created_enabled', true);
            $blueprint->add('deal_created_roles', ['Sales Manager']);
            $blueprint->add('deal_created_users', []);
            $blueprint->add('deal_created_email_enabled', true);
            $blueprint->add('deal_created_database_enabled', true);

            // Deal Stage Changed Settings
            $blueprint->add('deal_stage_changed_enabled', true);
            $blueprint->add('deal_stage_changed_roles', ['Sales Manager']);
            $blueprint->add('deal_stage_changed_users', []);
            $blueprint->add('deal_stage_changed_email_enabled', true);
            $blueprint->add('deal_stage_changed_database_enabled', true);

            // Deal Won Settings
            $blueprint->add('deal_won_enabled', true);
            $blueprint->add('deal_won_roles', ['Sales Manager', 'Admin']);
            $blueprint->add('deal_won_users', []);
            $blueprint->add('deal_won_email_enabled', true);
            $blueprint->add('deal_won_database_enabled', true);

            // Deal Lost Settings
            $blueprint->add('deal_lost_enabled', true);
            $blueprint->add('deal_lost_roles', ['Sales Manager']);
            $blueprint->add('deal_lost_users', []);
            $blueprint->add('deal_lost_email_enabled', true);
            $blueprint->add('deal_lost_database_enabled', true);

            // Deal Assigned Settings
            $blueprint->add('deal_assigned_enabled', true);
            $blueprint->add('deal_assigned_roles', []);
            $blueprint->add('deal_assigned_users', []);
            $blueprint->add('deal_assigned_email_enabled', true);
            $blueprint->add('deal_assigned_database_enabled', true);
        });
    }
};
