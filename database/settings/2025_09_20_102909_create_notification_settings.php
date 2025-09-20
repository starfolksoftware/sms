<?php

use Spatie\LaravelSettings\Migrations\SettingsBlueprint;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->inGroup('notifications', function (SettingsBlueprint $blueprint): void {
            // Deal Created Settings
            $blueprint->add('dealCreatedEnabled', true);
            $blueprint->add('dealCreatedRoles', ['Sales Manager']);
            $blueprint->add('dealCreatedUsers', []);
            $blueprint->add('dealCreatedEmailEnabled', true);
            $blueprint->add('dealCreatedDatabaseEnabled', true);

            // Deal Stage Changed Settings
            $blueprint->add('dealStageChangedEnabled', true);
            $blueprint->add('dealStageChangedRoles', ['Sales Manager']);
            $blueprint->add('dealStageChangedUsers', []);
            $blueprint->add('dealStageChangedEmailEnabled', true);
            $blueprint->add('dealStageChangedDatabaseEnabled', true);

            // Deal Won Settings
            $blueprint->add('dealWonEnabled', true);
            $blueprint->add('dealWonRoles', ['Sales Manager', 'Admin']);
            $blueprint->add('dealWonUsers', []);
            $blueprint->add('dealWonEmailEnabled', true);
            $blueprint->add('dealWonDatabaseEnabled', true);

            // Deal Lost Settings
            $blueprint->add('dealLostEnabled', true);
            $blueprint->add('dealLostRoles', ['Sales Manager']);
            $blueprint->add('dealLostUsers', []);
            $blueprint->add('dealLostEmailEnabled', true);
            $blueprint->add('dealLostDatabaseEnabled', true);

            // Deal Assigned Settings
            $blueprint->add('dealAssignedEnabled', true);
            $blueprint->add('dealAssignedRoles', []);
            $blueprint->add('dealAssignedUsers', []);
            $blueprint->add('dealAssignedEmailEnabled', true);
            $blueprint->add('dealAssignedDatabaseEnabled', true);
        });
    }
};
