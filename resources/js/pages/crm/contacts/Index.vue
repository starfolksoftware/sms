<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Search, Plus, Filter, Users, Phone, Mail, Building } from 'lucide-vue-next';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

interface Contact {
    id: number;
    name: string;
    email: string;
    phone: string;
    company: string;
    job_title: string;
    status: string;
    source: string;
    owner: { id: number; name: string } | null;
    creator: { id: number; name: string } | null;
    created_at: string;
    deals_count: number;
}

interface Props {
    contacts: {
        data: Contact[];
        meta: {
            current_page: number;
            last_page: number;
            per_page: number;
            total: number;
            from: number;
            to: number;
        };
        links: {
            first: string;
            last: string;
            prev: string | null;
            next: string | null;
        };
    };
    filters: {
        search?: string;
        status?: string;
        source?: string;
        owner_id?: number;
        created_from?: string;
        created_to?: string;
    };
    owners: Array<{ id: number; name: string }>;
    statusOptions: Array<{ value: string; label: string; badge_class: string }>;
    sourceOptions: Array<{ value: string; label: string; icon_class: string }>;
}

const props = defineProps<Props>();

// Reactive filters
const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const selectedSource = ref(props.filters.source || '');
const selectedOwner = ref(props.filters.owner_id?.toString() || '');

// Debounced search
let searchTimeout: NodeJS.Timeout;
watch(search, (newValue) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

// Apply filters when dropdowns change
watch([selectedStatus, selectedSource, selectedOwner], () => {
    applyFilters();
});

const applyFilters = () => {
    const filters: Record<string, any> = {};
    
    if (search.value) filters.search = search.value;
    if (selectedStatus.value) filters.status = selectedStatus.value;
    if (selectedSource.value) filters.source = selectedSource.value;
    if (selectedOwner.value) filters.owner_id = selectedOwner.value;
    
    router.get(route('crm.contacts.index'), filters, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    selectedSource.value = '';
    selectedOwner.value = '';
    
    router.get(route('crm.contacts.index'), {}, {
        preserveState: true,
        replace: true,
    });
};

const getStatusBadgeClass = (status: string) => {
    const option = props.statusOptions.find(opt => opt.value === status);
    return option?.badge_class || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString();
};

const hasActiveFilters = computed(() => {
    return search.value || selectedStatus.value || selectedSource.value || selectedOwner.value;
});

// Pagination
const goToPage = (page: number) => {
    if (page < 1 || page > props.contacts.meta.last_page) return;
    
    const currentFilters = {
        ...(props.filters.search && { search: props.filters.search }),
        ...(props.filters.status && { status: props.filters.status }),
        ...(props.filters.source && { source: props.filters.source }),
        ...(props.filters.owner_id && { owner_id: props.filters.owner_id }),
        page,
    };
    
    router.get(route('crm.contacts.index'), currentFilters);
};
</script>

<template>
    <Head title="Contacts" />
    
    <AppLayout :breadcrumbs="[{ title: 'CRM', href: '#' }, { title: 'Contacts', href: route('crm.contacts.index') }]">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Contacts</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        Manage your contacts and leads
                    </p>
                </div>
                
                <Button as-child v-if="$page.props.auth.permissions.includes('create_contacts')">
                    <Link :href="route('crm.contacts.create')">
                        <Plus class="w-4 h-4 mr-2" />
                        Add Contact
                    </Link>
                </Button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Users class="h-8 w-8 text-blue-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Contacts</p>
                                <p class="text-2xl font-bold text-gray-900">{{ contacts.meta.total }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Mail class="h-8 w-8 text-green-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">With Email</p>
                                <p class="text-2xl font-bold text-gray-900">{{ contacts.data.filter(c => c.email).length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Phone class="h-8 w-8 text-purple-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">With Phone</p>
                                <p class="text-2xl font-bold text-gray-900">{{ contacts.data.filter(c => c.phone).length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
                
                <Card>
                    <CardContent class="p-6">
                        <div class="flex items-center">
                            <Building class="h-8 w-8 text-orange-600" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">With Company</p>
                                <p class="text-2xl font-bold text-gray-900">{{ contacts.data.filter(c => c.company).length }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Filter class="w-5 h-5" />
                        Filters
                    </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <!-- Search -->
                        <div class="relative">
                            <Search class="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                            <Input
                                v-model="search"
                                placeholder="Search contacts..."
                                class="pl-10"
                            />
                        </div>

                        <!-- Status Filter -->
                        <Select v-model="selectedStatus">
                            <SelectTrigger>
                                <SelectValue placeholder="All Status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Status</SelectItem>
                                <SelectItem
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Source Filter -->
                        <Select v-model="selectedSource">
                            <SelectTrigger>
                                <SelectValue placeholder="All Sources" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Sources</SelectItem>
                                <SelectItem
                                    v-for="option in sourceOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Owner Filter -->
                        <Select v-model="selectedOwner">
                            <SelectTrigger>
                                <SelectValue placeholder="All Owners" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">All Owners</SelectItem>
                                <SelectItem
                                    v-for="owner in owners"
                                    :key="owner.id"
                                    :value="owner.id.toString()"
                                >
                                    {{ owner.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <!-- Clear Filters -->
                        <Button 
                            variant="outline" 
                            @click="clearFilters"
                            :disabled="!hasActiveFilters"
                        >
                            Clear Filters
                        </Button>
                    </div>
                </CardContent>
            </Card>

            <!-- Contacts Table -->
            <Card>
                <CardHeader>
                    <CardTitle>
                        Contacts ({{ contacts.meta.total }})
                    </CardTitle>
                    <CardDescription>
                        Showing {{ contacts.meta.from || 0 }} to {{ contacts.meta.to || 0 }} of {{ contacts.meta.total }} contacts
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="contacts.data.length > 0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Email</TableHead>
                                    <TableHead>Phone</TableHead>
                                    <TableHead>Company</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Source</TableHead>
                                    <TableHead>Owner</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead>Deals</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow 
                                    v-for="contact in contacts.data" 
                                    :key="contact.id"
                                    class="hover:bg-gray-50 cursor-pointer"
                                    @click="router.visit(route('crm.contacts.show', contact.id))"
                                >
                                    <TableCell class="font-medium">
                                        {{ contact.name }}
                                        <div v-if="contact.job_title" class="text-sm text-gray-500">
                                            {{ contact.job_title }}
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="contact.email" class="text-blue-600">
                                            {{ contact.email }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="contact.phone" class="text-gray-900">
                                            {{ contact.phone }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="contact.company" class="text-gray-900">
                                            {{ contact.company }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :class="getStatusBadgeClass(contact.status)">
                                            {{ statusOptions.find(s => s.value === contact.status)?.label || contact.status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span class="text-gray-700">
                                            {{ sourceOptions.find(s => s.value === contact.source)?.label || contact.source }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="contact.owner" class="text-gray-900">
                                            {{ contact.owner.name }}
                                        </span>
                                        <span v-else class="text-gray-400">Unassigned</span>
                                    </TableCell>
                                    <TableCell class="text-gray-500">
                                        {{ formatDate(contact.created_at) }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {{ contact.deals_count }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2" @click.stop>
                                            <Button 
                                                size="sm" 
                                                variant="outline" 
                                                as-child
                                            >
                                                <Link :href="route('crm.contacts.show', contact.id)">
                                                    View
                                                </Link>
                                            </Button>
                                            <Button 
                                                v-if="$page.props.auth.permissions.includes('edit_contacts')"
                                                size="sm" 
                                                variant="outline" 
                                                as-child
                                            >
                                                <Link :href="route('crm.contacts.edit', contact.id)">
                                                    Edit
                                                </Link>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Pagination -->
                        <div class="flex items-center justify-between mt-4">
                            <div class="text-sm text-gray-700">
                                Showing {{ contacts.meta.from || 0 }} to {{ contacts.meta.to || 0 }} 
                                of {{ contacts.meta.total }} results
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="goToPage(contacts.meta.current_page - 1)"
                                    :disabled="contacts.meta.current_page <= 1"
                                >
                                    Previous
                                </Button>
                                
                                <div class="flex items-center gap-1">
                                    <template v-for="page in Math.min(5, contacts.meta.last_page)" :key="page">
                                        <Button
                                            v-if="page <= contacts.meta.last_page"
                                            size="sm"
                                            :variant="page === contacts.meta.current_page ? 'default' : 'outline'"
                                            @click="goToPage(page)"
                                        >
                                            {{ page }}
                                        </Button>
                                    </template>
                                </div>
                                
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="goToPage(contacts.meta.current_page + 1)"
                                    :disabled="contacts.meta.current_page >= contacts.meta.last_page"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <Users class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">No contacts</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Get started by creating a new contact.
                        </p>
                        <div class="mt-6">
                            <Button as-child v-if="$page.props.auth.permissions.includes('create_contacts')">
                                <Link :href="route('crm.contacts.create')">
                                    <Plus class="w-4 h-4 mr-2" />
                                    Add Contact
                                </Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>