# Role & Permission System

This application uses the [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission/v6/introduction) package to manage user roles and permissions.

## Roles

The system includes three main roles:

### Admin
- **Full access** to all system features
- Can manage users, roles, settings, and all other resources
- Permissions: All permissions available in the system

### Sales
- **Client and task management** focused role
- Can manage clients, tasks, view reports and analytics
- Cannot manage users, roles, or system settings
- Permissions:
  - `manage_clients`
  - `manage_tasks`
  - `view_dashboard`
  - `view_reports`
  - `view_analytics`

### Marketing
- **Campaign management** focused role
- Can create and manage marketing campaigns, view analytics
- Cannot manage clients, tasks, users, or roles
- Permissions:
  - `view_dashboard`
  - `create_campaigns`
  - `manage_campaigns`
  - `view_reports`
  - `view_analytics`

## Available Permissions

- `manage_clients` - Create, edit, delete client records
- `manage_tasks` - Create, edit, delete tasks
- `view_dashboard` - Access to main dashboard
- `manage_users` - Create, edit, delete user accounts
- `manage_roles` - Assign/remove roles from users
- `manage_settings` - Modify system settings
- `view_reports` - Access to reporting features
- `create_campaigns` - Create new marketing campaigns
- `manage_campaigns` - Edit, delete marketing campaigns
- `view_analytics` - Access to analytics and metrics

## Usage Examples

### Assigning Roles to Users

```php
$user = User::find(1);
$user->assignRole('admin');

// Or assign multiple roles
$user->assignRole(['admin', 'sales']);
```

### Checking User Permissions

```php
// Check if user has a specific permission
if ($user->can('manage_clients')) {
    // User can manage clients
}

// Check if user has a specific role
if ($user->hasRole('admin')) {
    // User is an admin
}
```

### Using Middleware

The system includes a custom `CheckPermission` middleware that can be used to protect routes:

```php
Route::get('/admin-panel', AdminController::class)
    ->middleware('permission:manage_users');
```

### Using in Blade/Inertia Templates

```php
@can('manage_clients')
    <a href="{{ route('clients.create') }}">Add Client</a>
@endcan
```

## Seeding

The roles and permissions are automatically seeded when running:

```bash
php artisan migrate:fresh --seed
```

This will create:
- All permissions listed above
- Three roles (admin, sales, marketing) with their respective permissions
- Sample users with roles assigned

## Testing

The system includes comprehensive tests covering:
- Role and permission assignment
- Permission inheritance
- Middleware functionality
- Route protection

Run the tests with:

```bash
php artisan test --filter="RolePermission|CheckPermissionMiddleware|PermissionRoutes"
```