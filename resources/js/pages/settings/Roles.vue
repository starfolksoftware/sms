<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { index, store, update, destroy } from '@/routes/roles';
import { type BreadcrumbItem } from '@/types';

interface Permission {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
    created_at: string;
    updated_at: string;
}

interface Props {
    roles: Role[];
    permissions: Permission[];
}

const props = defineProps<Props>();

// Dialog state
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const editingRole = ref<Role | null>(null);

// Create role form
const createForm = useForm({
    name: '',
    permissions: [] as string[],
});

// Edit role form  
const editForm = useForm({
    name: '',
    permissions: [] as string[],
});

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Role Management',
        href: index().url,
    },
];

function openCreateDialog() {
    createForm.reset();
    createForm.permissions = [];
    isCreateDialogOpen.value = true;
}

function openEditDialog(role: Role) {
    editingRole.value = role;
    editForm.name = role.name;
    editForm.permissions = role.permissions.map(p => p.name);
    isEditDialogOpen.value = true;
}

function createRole() {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        }
    });
}

function updateRole() {
    if (!editingRole.value) return;
    
    editForm.put(update(editingRole.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editForm.reset();
            editingRole.value = null;
        }
    });
}

function deleteRole(role: Role) {
    if (role.name === 'admin') {
        alert('Cannot delete the admin role');
        return;
    }
    
    if (confirm(`Are you sure you want to delete the "${role.name}" role? This action cannot be undone.`)) {
        router.delete(destroy(role.id).url, {
            preserveScroll: true
        });
    }
}

function hasPermission(roleName: string, permissionName: string): boolean {
    const role = props.roles.find(r => r.name === roleName);
    return role?.permissions.some(p => p.name === permissionName) ?? false;
}

function toggleCreatePermission(permission: string, checked: boolean) {
    if (checked && !createForm.permissions.includes(permission)) {
        createForm.permissions.push(permission);
    } else if (!checked) {
        createForm.permissions = createForm.permissions.filter(p => p !== permission);
    }
}

function toggleEditPermission(permission: string, checked: boolean) {
    if (checked && !editForm.permissions.includes(permission)) {
        editForm.permissions.push(permission);
    } else if (!checked) {
        editForm.permissions = editForm.permissions.filter(p => p !== permission);
    }
}

// Group permissions by category for better organization
const permissionCategories = {
    'Client Management': props.permissions.filter(p => p.name.includes('client')),
    'Contact Management': props.permissions.filter(p => p.name.includes('contact')),
    'Deal Management': props.permissions.filter(p => p.name.includes('deal')),
    'Task Management': props.permissions.filter(p => p.name.includes('task')),
    'Product Management': props.permissions.filter(p => p.name.includes('product')),
    'Campaign Management': props.permissions.filter(p => p.name.includes('campaign')),
    'System & Administration': props.permissions.filter(p => 
        p.name.includes('user') || 
        p.name.includes('role') || 
        p.name.includes('setting') ||
        p.name.includes('dashboard') ||
        p.name.includes('report') ||
        p.name.includes('analytics')
    ),
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Role Management" />

        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <HeadingSmall 
                        title="Role Management" 
                        description="Manage roles and their permissions for your organization" 
                    />
                    <Button @click="openCreateDialog">
                        Create Role
                    </Button>
                </div>

                <!-- Roles and Permissions Matrix -->
                <Card>
                    <CardHeader>
                        <CardTitle>Roles & Permissions Matrix</CardTitle>
                        <CardDescription>
                            Overview of all roles and their assigned permissions
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left py-3 px-4 font-medium">Permission</th>
                                        <th 
                                            v-for="role in roles" 
                                            :key="role.id"
                                            class="text-center py-3 px-4 font-medium min-w-24"
                                        >
                                            {{ role.name }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="(categoryPermissions, category) in permissionCategories" :key="category">
                                        <tr v-if="categoryPermissions.length > 0" class="border-b bg-muted/50">
                                            <td :colspan="roles.length + 1" class="py-2 px-4 font-medium text-sm text-muted-foreground">
                                                {{ category }}
                                            </td>
                                        </tr>
                                        <tr 
                                            v-for="permission in categoryPermissions" 
                                            :key="permission.id"
                                            class="border-b hover:bg-muted/50"
                                        >
                                            <td class="py-3 px-4 text-sm">
                                                {{ permission.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                            </td>
                                            <td 
                                                v-for="role in roles" 
                                                :key="role.id"
                                                class="text-center py-3 px-4"
                                            >
                                                <div class="flex justify-center">
                                                    <Checkbox 
                                                        :checked="hasPermission(role.name, permission.name)"
                                                        disabled
                                                        class="pointer-events-none"
                                                    />
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <!-- Individual Role Cards -->
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="role in roles" :key="role.id">
                        <CardHeader>
                            <CardTitle class="flex items-center justify-between">
                                {{ role.name }}
                                <div class="flex gap-2">
                                    <Button 
                                        size="sm" 
                                        variant="outline"
                                        @click="openEditDialog(role)"
                                    >
                                        Edit
                                    </Button>
                                    <Button 
                                        v-if="role.name !== 'admin'"
                                        size="sm" 
                                        variant="destructive"
                                        @click="deleteRole(role)"
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </CardTitle>
                            <CardDescription>
                                {{ role.permissions.length }} permissions assigned
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <div 
                                    v-for="permission in role.permissions" 
                                    :key="permission.id"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ permission.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                </div>
                                <div v-if="role.permissions.length === 0" class="text-sm text-muted-foreground">
                                    No permissions assigned
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>

            <!-- Create Role Dialog -->
            <Dialog v-model:open="isCreateDialogOpen">
                <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Create New Role</DialogTitle>
                    </DialogHeader>
                    
                    <form @submit.prevent="createRole" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="create-name">Role Name</Label>
                            <Input 
                                id="create-name"
                                v-model="createForm.name"
                                placeholder="Enter role name"
                                required
                            />
                            <div v-if="createForm.errors.name" class="text-sm text-red-600">
                                {{ createForm.errors.name }}
                            </div>
                        </div>

                        <div class="space-y-4">
                            <Label>Permissions</Label>
                            <div class="space-y-4">
                                <template v-for="(categoryPermissions, category) in permissionCategories" :key="category">
                                    <div v-if="categoryPermissions.length > 0" class="space-y-2">
                                        <h4 class="font-medium text-sm">{{ category }}</h4>
                                        <div class="grid gap-3 md:grid-cols-2 pl-4">
                                            <div 
                                                v-for="permission in categoryPermissions" 
                                                :key="permission.id"
                                                class="flex items-center space-x-2"
                                            >
                                                <Checkbox 
                                                    :id="`create-${permission.name}`"
                                                    :checked="createForm.permissions.includes(permission.name)"
                                                    @update:checked="(checked) => toggleCreatePermission(permission.name, checked)"
                                                />
                                                <Label 
                                                    :for="`create-${permission.name}`"
                                                    class="text-sm font-normal cursor-pointer"
                                                >
                                                    {{ permission.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                                </Label>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="isCreateDialogOpen = false">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="createForm.processing">
                                Create Role
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>

            <!-- Edit Role Dialog -->
            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent class="max-w-2xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>Edit Role</DialogTitle>
                    </DialogHeader>
                    
                    <form @submit.prevent="updateRole" class="space-y-6">
                        <div class="space-y-2">
                            <Label for="edit-name">Role Name</Label>
                            <Input 
                                id="edit-name"
                                v-model="editForm.name"
                                placeholder="Enter role name"
                                required
                            />
                            <div v-if="editForm.errors.name" class="text-sm text-red-600">
                                {{ editForm.errors.name }}
                            </div>
                        </div>

                        <div class="space-y-4">
                            <Label>Permissions</Label>
                            <div class="space-y-4">
                                <template v-for="(categoryPermissions, category) in permissionCategories" :key="category">
                                    <div v-if="categoryPermissions.length > 0" class="space-y-2">
                                        <h4 class="font-medium text-sm">{{ category }}</h4>
                                        <div class="grid gap-3 md:grid-cols-2 pl-4">
                                            <div 
                                                v-for="permission in categoryPermissions" 
                                                :key="permission.id"
                                                class="flex items-center space-x-2"
                                            >
                                                <Checkbox 
                                                    :id="`edit-${permission.name}`"
                                                    :checked="editForm.permissions.includes(permission.name)"
                                                    @update:checked="(checked) => toggleEditPermission(permission.name, checked)"
                                                />
                                                <Label 
                                                    :for="`edit-${permission.name}`"
                                                    class="text-sm font-normal cursor-pointer"
                                                >
                                                    {{ permission.name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                                </Label>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="isEditDialogOpen = false">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="editForm.processing">
                                Update Role
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </SettingsLayout>
    </AppLayout>
</template>