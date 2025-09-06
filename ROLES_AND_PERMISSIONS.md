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
- Can manage contacts, deals, tasks, view reports and analytics
- Cannot manage users, roles, system settings, or products
- Can only modify resources they created (ownership-based authorization)
- Permissions:
  - `manage_clients`
  - `manage_contacts`, `view_contacts`, `create_contacts`, `edit_contacts`, `delete_contacts`
  - `manage_deals`, `view_deals`, `create_deals`, `edit_deals`, `delete_deals`
  - `manage_tasks`, `view_tasks`, `create_tasks`, `edit_tasks`, `delete_tasks`
  - `view_dashboard`
  - `view_reports`
  - `view_analytics`

### Marketing
- **Campaign management** focused role
- Can create and manage marketing campaigns, view analytics
- Cannot manage contacts, deals, tasks, users, or roles
- Limited access to CRM features
- Permissions:
  - `view_dashboard`
  - `create_campaigns`
  - `manage_campaigns`
  - `view_reports`
  - `view_analytics`

## Available Permissions

### Contact Management
- `manage_contacts` - General contact management permission
- `view_contacts` - View contact records
- `create_contacts` - Create new contact records
- `edit_contacts` - Edit existing contact records
- `delete_contacts` - Delete contact records

### Deal Management
- `manage_deals` - General deal management permission  
- `view_deals` - View deal records
- `create_deals` - Create new deal records
- `edit_deals` - Edit existing deal records
- `delete_deals` - Delete deal records

### Task Management
- `manage_tasks` - General task management permission
- `view_tasks` - View task records
- `create_tasks` - Create new task records
- `edit_tasks` - Edit existing task records
- `delete_tasks` - Delete task records

### Product Management
- `manage_products` - General product management permission
- `view_products` - View product records
- `create_products` - Create new product records
- `edit_products` - Edit existing product records
- `delete_products` - Delete product records

### System & General
- `manage_clients` - Legacy client management permission
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

## Authorization Enforcement

The system implements comprehensive backend authorization enforcement through multiple layers:

### 1. Middleware Protection
Routes are protected using the `CheckPermission` middleware:

```php
// Apply to specific routes
Route::get('/contacts', [ContactController::class, 'index'])
    ->middleware('permission:view_contacts');

// Apply to route groups
Route::resource('products', ProductController::class)
    ->middleware('permission:manage_products');
```

### 2. Policy-Based Authorization
Laravel policies provide fine-grained resource-level authorization:

```php
// Automatically applied in controllers
public function update(Request $request, Contact $contact)
{
    $this->authorize('update', $contact); // Checks ContactPolicy
    // ... update logic
}
```

### 3. Ownership-Based Access Control
Users can only modify resources they created (unless they're admins):

- **Sales users** can view all contacts but only edit/delete their own
- **Admins** can modify any resource regardless of ownership
- **Marketing users** have no access to CRM resources

### 4. Proper HTTP Status Codes
- **403 Forbidden** for unauthorized access to existing resources
- **401 Unauthorized** for unauthenticated requests
- **404 Not Found** for resources that don't exist
- **302 Redirect** for unauthenticated web route access

### 5. Comprehensive Testing
All authorization logic is covered by tests:
- Policy tests verify permission logic
- Controller tests verify route protection
- Integration tests verify end-to-end authorization
- Ownership tests verify resource-level access control

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