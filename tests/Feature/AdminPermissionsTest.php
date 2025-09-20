<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_role_has_all_available_permissions(): void
    {
        // Get all permissions from database
        $allPermissions = Permission::all()->pluck('name')->sort();

        // Get Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        $this->assertNotNull($adminRole, 'Admin role should exist');

        // Get Admin role permissions
        $adminPermissions = $adminRole->permissions->pluck('name')->sort();

        // Assert Admin has all permissions
        $this->assertEquals(
            $allPermissions->values()->toArray(),
            $adminPermissions->values()->toArray(),
            'Admin role should have all available permissions'
        );

        // Verify specific critical permissions
        $criticalPermissions = [
            'manage_clients',
            'manage_deals',
            'manage_tasks',
            'manage_roles',
            'manage_settings',
            'view_dashboard',
            'view_marketing',
            'view_audit_logs',
        ];

        foreach ($criticalPermissions as $permission) {
            $this->assertTrue(
                $adminPermissions->contains($permission),
                "Admin role should have '{$permission}' permission"
            );
        }
    }

    public function test_admin_user_can_access_all_permissions(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Test all permissions
        $permissions = [
            'manage_clients',
            'manage_deals',
            'manage_tasks',
            'manage_roles',
            'manage_settings',
            'manage_stages',
            'view_dashboard',
            'view_clients',
            'view_deals',
            'view_audit_logs',
            'view_marketing',
        ];

        foreach ($permissions as $permission) {
            $this->assertTrue(
                $admin->can($permission),
                "Admin user should have '{$permission}' permission"
            );
        }
    }

    public function test_all_expected_permissions_exist(): void
    {
        $expectedPermissions = [
            'manage_clients',
            'manage_deals',
            'manage_tasks',
            'manage_roles',
            'manage_settings',
            'manage_stages',
            'view_dashboard',
            'view_clients',
            'view_deals',
            'view_audit_logs',
            'view_marketing',
        ];

        foreach ($expectedPermissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            $this->assertNotNull(
                $permission,
                "Permission '{$permissionName}' should exist in database"
            );
        }
    }
}
