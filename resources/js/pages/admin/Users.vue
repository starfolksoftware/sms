<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { index, store, update, destroy, invite, resendInvite as resendInviteRoute } from '@/routes/admin/users';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

// State management
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isInviteDialogOpen = ref(false);
const editingUser = ref(null);

// Search and filter state
const searchForm = useForm({
    search: props.filters.search || '',
    role: props.filters.role || '',
    status: props.filters.status || '',
    sort: props.filters.sort || 'name',
    direction: props.filters.direction || 'asc',
});

// Create user form
const createForm = useForm({
    name: '',
    email: '',
    roles: [],
    send_invitation: true,
});

// Edit user form  
const editForm = useForm({
    name: '',
    email: '',
    status: '',
    roles: [],
});

// Invite user form
const inviteForm = useForm({
    name: '',
    email: '',
    roles: [],
});

// Search and filter functionality
function performSearch() {
    router.get(index().url, searchForm.data(), {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    searchForm.reset();
    performSearch();
}

// Sort functionality
function sortBy(field) {
    if (searchForm.sort === field) {
        searchForm.direction = searchForm.direction === 'asc' ? 'desc' : 'asc';
    } else {
        searchForm.sort = field;
        searchForm.direction = 'asc';
    }
    performSearch();
}

// Create user functionality
function createUser() {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        }
    });
}

// Edit user functionality
function openEditDialog(user) {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.status = user.status;
    editForm.roles = user.roles.map(role => role.name);
    isEditDialogOpen.value = true;
}

function updateUser() {
    if (!editingUser.value) return;
    
    editForm.put(update(editingUser.value.id).url, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editForm.reset();
            editingUser.value = null;
        }
    });
}

// Invite user functionality
function inviteUser() {
    inviteForm.post(invite().url, {
        preserveScroll: true,
        onSuccess: () => {
            isInviteDialogOpen.value = false;
            inviteForm.reset();
        }
    });
}

// Resend invite
function resendInvite(user) {
    router.post(resendInviteRoute(user.id).url, {}, {
        preserveScroll: true,
    });
}

// Delete user
function deleteUser(user) {
    if (confirm(`Are you sure you want to delete "${user.name}"? This action cannot be undone.`)) {
        router.delete(destroy(user.id).url, {
            preserveScroll: true,
        });
    }
}

// Utility functions
function getStatusBadgeClasses(status) {
    switch (status) {
        case 'active': 
            return 'bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-300';
        case 'deactivated': 
            return 'bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-300';
        case 'pending_invite': 
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-300';
        default: 
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}

function formatDate(dateString) {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString();
}

// Computed
const filteredStatusOptions = computed(() => [
    { value: '', label: 'All Statuses' },
    { value: 'active', label: 'Active' },
    { value: 'deactivated', label: 'Deactivated' },
    { value: 'pending_invite', label: 'Pending Invite' },
]);

const filteredRoleOptions = computed(() => [
    { value: '', label: 'All Roles' },
    ...props.roles.map(role => ({ value: role.name, label: role.name }))
]);
</script>

<template>
    <Head title="User Management" />
    
    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">User Management</h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">Manage users, roles, and invitations</p>
            </div>

            <!-- Search and Filters -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Search & Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4">
                        <div class="flex-1 min-w-64">
                            <Label for="search">Search by name or email</Label>
                            <Input
                                id="search"
                                v-model="searchForm.search"
                                placeholder="Search users..."
                                @input="performSearch"
                            />
                        </div>
                        <div class="w-48">
                            <Label>Role</Label>
                            <select 
                                v-model="searchForm.role" 
                                @change="performSearch"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                            >
                                <option
                                    v-for="option in filteredRoleOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="w-48">
                            <Label>Status</Label>
                            <select 
                                v-model="searchForm.status" 
                                @change="performSearch"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                            >
                                <option
                                    v-for="option in filteredStatusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <Button variant="outline" @click="clearFilters">Clear</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Action Buttons -->
            <div class="flex gap-4 mb-6">
                <Dialog v-model:open="isInviteDialogOpen">
                    <DialogTrigger as-child>
                        <Button>Invite User</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Invite New User</DialogTitle>
                            <DialogDescription>
                                Send an invitation email to a new user
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="inviteUser" class="space-y-4">
                            <div>
                                <Label for="invite-name">Name</Label>
                                <Input
                                    id="invite-name"
                                    v-model="inviteForm.name"
                                    required
                                />
                                <div v-if="inviteForm.errors.name" class="text-red-600 text-sm mt-1">
                                    {{ inviteForm.errors.name }}
                                </div>
                            </div>
                            <div>
                                <Label for="invite-email">Email</Label>
                                <Input
                                    id="invite-email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    required
                                />
                                <div v-if="inviteForm.errors.email" class="text-red-600 text-sm mt-1">
                                    {{ inviteForm.errors.email }}
                                </div>
                            </div>
                            <div>
                                <Label>Roles</Label>
                                <div class="space-y-2 mt-2">
                                    <div
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="flex items-center space-x-2"
                                    >
                                        <Checkbox
                                            :id="`invite-role-${role.id}`"
                                            :checked="inviteForm.roles.includes(role.name)"
                                            @update:checked="(checked) => {
                                                if (checked) {
                                                    inviteForm.roles.push(role.name);
                                                } else {
                                                    const index = inviteForm.roles.indexOf(role.name);
                                                    if (index > -1) inviteForm.roles.splice(index, 1);
                                                }
                                            }"
                                        />
                                        <Label :for="`invite-role-${role.id}`">{{ role.name }}</Label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="isInviteDialogOpen = false">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="inviteForm.processing">
                                    Send Invitation
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog v-model:open="isCreateDialogOpen">
                    <DialogTrigger as-child>
                        <Button variant="outline">Create User</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New User</DialogTitle>
                            <DialogDescription>
                                Create a user directly or send an invitation
                            </DialogDescription>
                        </DialogHeader>
                        <form @submit.prevent="createUser" class="space-y-4">
                            <div>
                                <Label for="create-name">Name</Label>
                                <Input
                                    id="create-name"
                                    v-model="createForm.name"
                                    required
                                />
                                <div v-if="createForm.errors.name" class="text-red-600 text-sm mt-1">
                                    {{ createForm.errors.name }}
                                </div>
                            </div>
                            <div>
                                <Label for="create-email">Email</Label>
                                <Input
                                    id="create-email"
                                    v-model="createForm.email"
                                    type="email"
                                    required
                                />
                                <div v-if="createForm.errors.email" class="text-red-600 text-sm mt-1">
                                    {{ createForm.errors.email }}
                                </div>
                            </div>
                            <div>
                                <Label>Roles</Label>
                                <div class="space-y-2 mt-2">
                                    <div
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="flex items-center space-x-2"
                                    >
                                        <Checkbox
                                            :id="`create-role-${role.id}`"
                                            :checked="createForm.roles.includes(role.name)"
                                            @update:checked="(checked) => {
                                                if (checked) {
                                                    createForm.roles.push(role.name);
                                                } else {
                                                    const index = createForm.roles.indexOf(role.name);
                                                    if (index > -1) createForm.roles.splice(index, 1);
                                                }
                                            }"
                                        />
                                        <Label :for="`create-role-${role.id}`">{{ role.name }}</Label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <Checkbox
                                    id="send-invitation"
                                    v-model:checked="createForm.send_invitation"
                                />
                                <Label for="send-invitation">Send invitation email</Label>
                            </div>
                            <div class="flex justify-end gap-2">
                                <Button type="button" variant="outline" @click="isCreateDialogOpen = false">
                                    Cancel
                                </Button>
                                <Button type="submit" :disabled="createForm.processing">
                                    Create User
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            <!-- Users Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Users ({{ users.total }})</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 px-4 cursor-pointer" @click="sortBy('name')">
                                        Name
                                        <span v-if="searchForm.sort === 'name'">
                                            {{ searchForm.direction === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </th>
                                    <th class="text-left py-3 px-4 cursor-pointer" @click="sortBy('email')">
                                        Email
                                        <span v-if="searchForm.sort === 'email'">
                                            {{ searchForm.direction === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </th>
                                    <th class="text-left py-3 px-4">Roles</th>
                                    <th class="text-left py-3 px-4">Status</th>
                                    <th class="text-left py-3 px-4 cursor-pointer" @click="sortBy('last_login_at')">
                                        Last Login
                                        <span v-if="searchForm.sort === 'last_login_at'">
                                            {{ searchForm.direction === 'asc' ? '↑' : '↓' }}
                                        </span>
                                    </th>
                                    <th class="text-left py-3 px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="border-b hover:bg-gray-50 dark:hover:bg-gray-800"
                                >
                                    <td class="py-3 px-4 font-medium">{{ user.name }}</td>
                                    <td class="py-3 px-4">{{ user.email }}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="role in user.roles"
                                                :key="role.id"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                            >
                                                {{ role.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusBadgeClasses(user.status)]">
                                            {{ user.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">{{ formatDate(user.last_login_at) }}</td>
                                    <td class="py-3 px-4">
                                        <div class="flex gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                @click="openEditDialog(user)"
                                            >
                                                Edit
                                            </Button>
                                            <Button
                                                v-if="user.status === 'pending_invite'"
                                                size="sm"
                                                variant="outline"
                                                @click="resendInvite(user)"
                                            >
                                                Resend
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                @click="deleteUser(user)"
                                            >
                                                Delete
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="users.data.length === 0">
                                    <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                        No users found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="users.links.length > 3" class="mt-6 flex justify-center">
                        <nav class="flex space-x-2">
                            <template v-for="(link, index) in users.links" :key="index">
                                <Button
                                    v-if="link.url"
                                    :variant="link.active ? 'default' : 'outline'"
                                    size="sm"
                                    @click="router.visit(link.url, { preserveState: true })"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-500"
                                    v-html="link.label"
                                />
                            </template>
                        </nav>
                    </div>
                </CardContent>
            </Card>

            <!-- Edit User Dialog -->
            <Dialog v-model:open="isEditDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                        <DialogDescription>
                            Update user information and roles
                        </DialogDescription>
                    </DialogHeader>
                    <form v-if="editingUser" @submit.prevent="updateUser" class="space-y-4">
                        <div>
                            <Label for="edit-name">Name</Label>
                            <Input
                                id="edit-name"
                                v-model="editForm.name"
                                required
                            />
                            <div v-if="editForm.errors.name" class="text-red-600 text-sm mt-1">
                                {{ editForm.errors.name }}
                            </div>
                        </div>
                        <div>
                            <Label for="edit-email">Email</Label>
                            <Input
                                id="edit-email"
                                v-model="editForm.email"
                                type="email"
                                required
                            />
                            <div v-if="editForm.errors.email" class="text-red-600 text-sm mt-1">
                                {{ editForm.errors.email }}
                            </div>
                        </div>
                        <div v-if="editingUser.status !== 'pending_invite'">
                            <Label for="edit-status">Status</Label>
                            <select 
                                v-model="editForm.status"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-800 dark:text-white"
                            >
                                <option value="active">Active</option>
                                <option value="deactivated">Deactivated</option>
                            </select>
                            <div v-if="editForm.errors.status" class="text-red-600 text-sm mt-1">
                                {{ editForm.errors.status }}
                            </div>
                        </div>
                        <div>
                            <Label>Roles</Label>
                            <div class="space-y-2 mt-2">
                                <div
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="flex items-center space-x-2"
                                >
                                    <Checkbox
                                        :id="`edit-role-${role.id}`"
                                        :checked="editForm.roles.includes(role.name)"
                                        @update:checked="(checked) => {
                                            if (checked) {
                                                editForm.roles.push(role.name);
                                            } else {
                                                const index = editForm.roles.indexOf(role.name);
                                                if (index > -1) editForm.roles.splice(index, 1);
                                            }
                                        }"
                                    />
                                    <Label :for="`edit-role-${role.id}`">{{ role.name }}</Label>
                                </div>
                            </div>
                            <div v-if="editForm.errors.roles" class="text-red-600 text-sm mt-1">
                                {{ editForm.errors.roles }}
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="isEditDialogOpen = false">
                                Cancel
                            </Button>
                            <Button type="submit" :disabled="editForm.processing">
                                Update User
                            </Button>
                        </div>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </AppLayout>
</template>