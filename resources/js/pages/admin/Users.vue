<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AdminLayout from '@/layouts/admin/Layout.vue';
import admin from '@/routes/admin';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Switch } from '@/components/ui/switch';
import { index, store, update, destroy, invite, resendInvite as resendInviteRoute } from '@/routes/admin/users';

// Types
type Role = { id: number | string; name: string };
type User = {
    id: number | string;
    name: string;
    email: string;
    status?: string;
    last_login_at?: string | null;
    roles: Role[];
};
type Link = { url: string | null; label: string; active: boolean };
type Paginated<T> = { data: T[]; total: number; links: Link[] };
type SearchForm = {
    search: string;
    role: string;
    status: string;
    sort: string;
    direction: 'asc' | 'desc';
};
type CreateForm = { name: string; email: string; roles: string[]; send_invitation: boolean };
type EditForm = { name: string; email: string; status: string; roles: string[] };
type InviteForm = { name: string; email: string; roles: string[] };

const props = withDefaults(defineProps<{
    users: Paginated<User>;
    roles: Role[];
    filters?: Partial<SearchForm>;
}>(), {
    filters: () => ({}),
});

// State management
const isCreateDialogOpen = ref(false);
const isEditDialogOpen = ref(false);
const isInviteDialogOpen = ref(false);
const editingUser = ref<User | null>(null);
const isDeleteDialogOpen = ref(false);
const userToDelete = ref<User | null>(null);
const isResendDialogOpen = ref(false);
const userToResend = ref<User | null>(null);
// Search and filter state
const searchForm = useForm<SearchForm>({
    search: props.filters?.search || '',
    role: props.filters?.role || 'all',
    status: props.filters?.status || 'all',
    sort: props.filters?.sort || 'name',
    direction: props.filters?.direction || 'asc',
});

// Convert empty string filters from backend to "all" for frontend
if (searchForm.role === '') searchForm.role = 'all';
if (searchForm.status === '') searchForm.status = 'all';

// Create user form
const createForm = useForm<CreateForm>({
    name: '',
    email: '',
    roles: [] as string[],
    send_invitation: true,
});

// Edit user form  
const editForm = useForm<EditForm>({
    name: '',
    email: '',
    status: '',
    roles: [] as string[],
});

// Invite user form
const inviteForm = useForm<InviteForm>({
    name: '',
    email: '',
    roles: [] as string[],
});

// Search and filter functionality
function performSearch(): void {
    const formData = { ...searchForm.data() };
    // Convert "all" values back to empty strings for backend
    if (formData.role === 'all') formData.role = '';
    if (formData.status === 'all') formData.status = '';
    
    router.get(index().url, formData, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters(): void {
    searchForm.search = '';
    searchForm.role = 'all';
    searchForm.status = 'all';
    searchForm.sort = 'name';
    searchForm.direction = 'asc';
    performSearch();
}

// Sort functionality
function sortBy(field: string): void {
    if (searchForm.sort === field) {
        searchForm.direction = searchForm.direction === 'asc' ? 'desc' : 'asc';
    } else {
        searchForm.sort = field;
        searchForm.direction = 'asc';
    }
    performSearch();
}

// Create user functionality
function createUser(): void {
    createForm.post(store().url, {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogOpen.value = false;
            createForm.reset();
        }
    });
}

// Edit user functionality
function openEditDialog(user: User): void {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    {
        const s = (user.status ?? '').toLowerCase();
        editForm.status = s === 'active' ? 'active' : (s === 'deactivated' ? 'deactivated' : '');
    }
    editForm.roles = Array.isArray(user.roles) ? user.roles.map((role) => role.name) : [];
    // Initialize edit role state map based on available roles
    Object.keys(editRoleState).forEach((k) => delete editRoleState[k]);
    for (const r of props.roles) {
        editRoleState[r.name] = editForm.roles.includes(r.name);
    }
    isEditDialogOpen.value = true;
}

function updateUser(): void {
    if (!editingUser.value) return;
        editForm.put(update(Number(editingUser.value.id)).url, {
        preserveScroll: true,
        onSuccess: () => {
            isEditDialogOpen.value = false;
            editForm.reset();
            editingUser.value = null;
        }
    });
}

// Invite user functionality
function inviteUser(): void {
    inviteForm.post(invite().url, {
        preserveScroll: true,
        onSuccess: () => {
            isInviteDialogOpen.value = false;
            inviteForm.reset();
        }
    });
}

// Resend invite
function resendInvite(user: User): void {
    router.post(resendInviteRoute(Number(user.id)).url, {}, {
        preserveScroll: true,
    });
}

function confirmResend(user: User): void {
    userToResend.value = user;
    isResendDialogOpen.value = true;
}

function performResend(): void {
    if (!userToResend.value) {
        return;
    }
    resendInvite(userToResend.value);
    isResendDialogOpen.value = false;
    userToResend.value = null;
}

// Delete user
function confirmDelete(user: User): void {
    userToDelete.value = user;
    isDeleteDialogOpen.value = true;
}

function performDelete(): void {
    if (!userToDelete.value) return;
    router.delete(destroy(Number(userToDelete.value.id)).url, {
        preserveScroll: true,
        onFinish: () => {
            isDeleteDialogOpen.value = false;
            userToDelete.value = null;
        },
    });
}

// Utility functions
function getStatusBadgeClasses(status: string): string {
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

function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return 'Never';
    return new Date(dateString).toLocaleDateString();
}

function getStatusLabel(status?: string): string {
    if (!status) return '';
    return status.replace('_', ' ').replace(/\b\w/g, (l: string) => l.toUpperCase());
}

function handleInviteRoleToggle(roleName: string, checked: boolean): void {
    if (checked) {
        if (!inviteForm.roles.includes(roleName)) inviteForm.roles.push(roleName);
    } else {
        const idx = inviteForm.roles.indexOf(roleName);
        if (idx > -1) inviteForm.roles.splice(idx, 1);
    }
}

function handleCreateRoleToggle(roleName: string, checked: boolean): void {
    if (checked) {
        if (!createForm.roles.includes(roleName)) createForm.roles.push(roleName);
    } else {
        const idx = createForm.roles.indexOf(roleName);
        if (idx > -1) createForm.roles.splice(idx, 1);
    }
}

// Edit role state map and sync (like Roles.vue)
const editRoleState = reactive<Record<string, boolean>>({});
watch(editRoleState, () => {
    editForm.roles = Object.keys(editRoleState).filter((k) => editRoleState[k]);
}, { deep: true });

// Computed
const filteredStatusOptions = computed(() => [
    { value: 'all', label: 'All Statuses' },
    { value: 'active', label: 'Active' },
    { value: 'deactivated', label: 'Deactivated' },
    { value: 'pending_invite', label: 'Pending Invite' },
]);

const filteredRoleOptions = computed(() => [
    { value: 'all', label: 'All Roles' },
    ...(Array.isArray(props.roles)
        ? props.roles.map((role) => ({ value: role.name, label: role.name }))
        : [])
]);

// removed set approach; using editRoleState + v-model instead

// Switch expects v-model (modelValue) boolean — map to status string
const editStatusChecked = computed<boolean>({
    get: () => editForm.status === 'active',
    set: (val: boolean) => { editForm.status = val ? 'active' : 'deactivated'; },
});
</script>

<template>
    <Head title="User Management" />
    
    <AppLayout>
        <AdminLayout
            title="User Management"
            description="Manage users, roles, and invitations"
            :sidebar-nav-items="[
                { title: 'Home', href: admin.dashboard().url },
                { title: 'Users', href: admin.users.index().url },
                { title: 'Roles', href: admin.roles.index().url },
                { title: 'Audit Logs', href: admin.auditLogs.index().url },
            ]"
        >
            <!-- Search and Filters -->
            <Card class="mb-6">
                <CardHeader>
                    <CardTitle>Search & Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-4 items-end">
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
                            <Select 
                                :model-value="searchForm.role" 
                                @update:model-value="(value) => { searchForm.role = String(value ?? 'all'); performSearch(); }"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Roles" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in filteredRoleOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="w-48">
                            <Label>Status</Label>
                            <Select 
                                :model-value="searchForm.status" 
                                @update:model-value="(value) => { searchForm.status = String(value ?? 'all'); performSearch(); }"
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="All Statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in filteredStatusOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex">
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
                                            @update:checked="handleInviteRoleToggle(role.name, $event)"
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
                                            @update:checked="handleCreateRoleToggle(role.name, $event)"
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
                                        <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', getStatusBadgeClasses(user.status ?? '')]">
                                            {{ getStatusLabel(user.status) }}
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
                                                @click="confirmResend(user)"
                                            >
                                                Resend
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                @click="confirmDelete(user)"
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
                                >
                                    {{ link.label }}
                                </Button>
                                <span
                                    v-else
                                    class="px-3 py-2 text-sm text-gray-500"
                                >
                                    {{ link.label }}
                                </span>
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
                            <Label>Status</Label>
                            <div class="flex items-center gap-3 pt-2">
                                <Switch v-model="editStatusChecked" />
                                <span class="text-sm text-muted-foreground">{{ editStatusChecked ? 'Active' : 'Deactivated' }}</span>
                            </div>
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
                                        v-model="editRoleState[role.name]"
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

            <!-- Delete Confirmation Dialog -->
            <Dialog v-model:open="isDeleteDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete User</DialogTitle>
                        <DialogDescription>
                            This action cannot be undone. This will permanently delete
                            <strong>{{ userToDelete?.name }}</strong> and remove their data.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button variant="outline" type="button" @click="isDeleteDialogOpen = false">Cancel</Button>
                        <Button variant="destructive" type="button" @click="performDelete">
                            Confirm Delete
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>

            <!-- Resend Invitation Confirmation Dialog -->
            <Dialog v-model:open="isResendDialogOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Resend Invitation</DialogTitle>
                        <DialogDescription>
                            We'll resend the invitation email to
                            <strong>{{ userToResend?.email }}</strong>.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="flex justify-end gap-2 mt-4">
                        <Button variant="outline" type="button" @click="isResendDialogOpen = false">Cancel</Button>
                        <Button type="button" @click="performResend">Confirm Resend</Button>
                    </div>
                </DialogContent>
            </Dialog>
        </AdminLayout>
    </AppLayout>
</template>