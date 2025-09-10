<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Edit, Trash2, Phone, Mail, Building, User, Calendar, RotateCcw, Copy } from 'lucide-vue-next';
import { Head, router, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Contact {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    company: string;
    job_title: string;
    status: string;
    source: string;
    notes: string;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    owner: { id: number; name: string } | null;
    creator: { id: number; name: string } | null;
    deals_count: number;
    tasks_count: number;
}

interface Props {
    contact: Contact;
    can: {
        update: boolean;
        delete: boolean;
        restore: boolean;
    };
}

const props = defineProps<Props>();

const isDeleted = computed(() => !!props.contact.deleted_at);

const deleteContact = () => {
    router.delete(route('crm.contacts.destroy', props.contact.id));
};

const restoreContact = () => {
    router.post(route('crm.contacts.web_restore', props.contact.id));
};

const copyToClipboard = async (text: string, type: string) => {
    try {
        await navigator.clipboard.writeText(text);
        // You could add a toast notification here
        console.log(`${type} copied to clipboard`);
    } catch (err) {
        console.error('Failed to copy:', err);
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const getStatusBadgeClass = (status: string) => {
    const statusClasses = {
        lead: 'bg-blue-100 text-blue-800',
        qualified: 'bg-yellow-100 text-yellow-800',
        customer: 'bg-green-100 text-green-800',
        archived: 'bg-gray-100 text-gray-800',
    };
    return statusClasses[status as keyof typeof statusClasses] || 'bg-gray-100 text-gray-800';
};

const getStatusDisplayName = (status: string) => {
    const statusNames = {
        lead: 'Lead',
        qualified: 'Qualified',
        customer: 'Customer',
        archived: 'Archived',
    };
    return statusNames[status as keyof typeof statusNames] || status;
};

const getSourceDisplayName = (source: string) => {
    const sourceNames = {
        website_form: 'Website Form',
        meta_ads: 'Meta Ads',
        x: 'X (Twitter)',
        instagram: 'Instagram',
        referral: 'Referral',
        manual: 'Manual Entry',
        other: 'Other',
    };
    return sourceNames[source as keyof typeof sourceNames] || source;
};

const breadcrumbs = [
    { title: 'CRM', href: '#' },
    { title: 'Contacts', href: route('crm.contacts.index') },
    { title: props.contact.name, href: route('crm.contacts.show', props.contact.id) }
];
</script>

<template>
    <Head :title="contact.name" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                            {{ contact.name }}
                            <Badge :class="getStatusBadgeClass(contact.status)">
                                {{ getStatusDisplayName(contact.status) }}
                            </Badge>
                            <Badge v-if="isDeleted" variant="destructive">
                                Deleted
                            </Badge>
                        </h1>
                        <div class="flex items-center gap-2 mt-2 text-gray-600">
                            <span v-if="contact.job_title">{{ contact.job_title }}</span>
                            <span v-if="contact.job_title && contact.company">at</span>
                            <span v-if="contact.company">{{ contact.company }}</span>
                        </div>
                        <div v-if="contact.owner" class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                            <User class="w-4 h-4" />
                            Owner: {{ contact.owner.name }}
                        </div>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <Button v-if="isDeleted && can.restore" @click="restoreContact">
                        <RotateCcw class="w-4 h-4 mr-2" />
                        Restore
                    </Button>
                    
                    <template v-if="!isDeleted">
                        <Button v-if="can.update" variant="outline" as-child>
                            <Link :href="route('crm.contacts.edit', contact.id)">
                                <Edit class="w-4 h-4 mr-2" />
                                Edit
                            </Link>
                        </Button>
                        
                        <AlertDialog v-if="can.delete">
                            <AlertDialogTrigger as-child>
                                <Button variant="destructive">
                                    <Trash2 class="w-4 h-4 mr-2" />
                                    Delete
                                </Button>
                            </AlertDialogTrigger>
                            <AlertDialogContent>
                                <AlertDialogHeader>
                                    <AlertDialogTitle>Delete Contact</AlertDialogTitle>
                                    <AlertDialogDescription>
                                        Are you sure you want to delete {{ contact.name }}? 
                                        This action can be undone by restoring the contact later.
                                    </AlertDialogDescription>
                                </AlertDialogHeader>
                                <AlertDialogFooter>
                                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                                    <AlertDialogAction @click="deleteContact">
                                        Delete Contact
                                    </AlertDialogAction>
                                </AlertDialogFooter>
                            </AlertDialogContent>
                        </AlertDialog>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Contact Information</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div v-if="contact.email" class="flex items-center justify-between group">
                                <div class="flex items-center gap-3">
                                    <Mail class="w-5 h-5 text-gray-400" />
                                    <div>
                                        <div class="font-medium">Email</div>
                                        <a 
                                            :href="`mailto:${contact.email}`" 
                                            class="text-blue-600 hover:text-blue-800"
                                        >
                                            {{ contact.email }}
                                        </a>
                                    </div>
                                </div>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="copyToClipboard(contact.email, 'Email')"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <Copy class="w-4 h-4" />
                                </Button>
                            </div>

                            <div v-if="contact.phone" class="flex items-center justify-between group">
                                <div class="flex items-center gap-3">
                                    <Phone class="w-5 h-5 text-gray-400" />
                                    <div>
                                        <div class="font-medium">Phone</div>
                                        <a 
                                            :href="`tel:${contact.phone}`" 
                                            class="text-blue-600 hover:text-blue-800"
                                        >
                                            {{ contact.phone }}
                                        </a>
                                    </div>
                                </div>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    @click="copyToClipboard(contact.phone, 'Phone')"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity"
                                >
                                    <Copy class="w-4 h-4" />
                                </Button>
                            </div>

                            <div v-if="contact.company" class="flex items-center gap-3">
                                <Building class="w-5 h-5 text-gray-400" />
                                <div>
                                    <div class="font-medium">Company</div>
                                    <div class="text-gray-700">{{ contact.company }}</div>
                                </div>
                            </div>

                            <div v-if="!contact.email && !contact.phone && !contact.company" class="text-center py-4 text-gray-500">
                                No contact information available
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Notes -->
                    <Card v-if="contact.notes">
                        <CardHeader>
                            <CardTitle>Notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="whitespace-pre-wrap text-gray-700">
                                {{ contact.notes }}
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Related Items -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Related Items</CardTitle>
                            <CardDescription>
                                Associated deals and tasks
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="text-center p-4 border rounded-lg">
                                    <div class="text-2xl font-bold text-blue-600">
                                        {{ contact.deals_count }}
                                    </div>
                                    <div class="text-sm text-gray-600">Deals</div>
                                    <Button size="sm" variant="outline" class="mt-2" disabled>
                                        View Deals
                                    </Button>
                                </div>
                                
                                <div class="text-center p-4 border rounded-lg">
                                    <div class="text-2xl font-bold text-green-600">
                                        {{ contact.tasks_count }}
                                    </div>
                                    <div class="text-sm text-gray-600">Tasks</div>
                                    <Button size="sm" variant="outline" class="mt-2" disabled>
                                        View Tasks
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Source</div>
                                <div class="mt-1">{{ getSourceDisplayName(contact.source) }}</div>
                            </div>

                            <Separator />

                            <div>
                                <div class="text-sm font-medium text-gray-500">Created</div>
                                <div class="mt-1 flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-gray-400" />
                                    {{ formatDate(contact.created_at) }}
                                </div>
                                <div v-if="contact.creator" class="text-xs text-gray-500 mt-1">
                                    by {{ contact.creator.name }}
                                </div>
                            </div>

                            <div v-if="contact.updated_at !== contact.created_at">
                                <div class="text-sm font-medium text-gray-500">Last Updated</div>
                                <div class="mt-1 flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-gray-400" />
                                    {{ formatDate(contact.updated_at) }}
                                </div>
                            </div>

                            <div v-if="contact.deleted_at">
                                <div class="text-sm font-medium text-red-600">Deleted</div>
                                <div class="mt-1 flex items-center gap-2">
                                    <Calendar class="w-4 h-4 text-red-400" />
                                    {{ formatDate(contact.deleted_at) }}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Quick Actions -->
                    <Card v-if="!isDeleted">
                        <CardHeader>
                            <CardTitle>Quick Actions</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2">
                            <Button variant="outline" size="sm" class="w-full justify-start" disabled>
                                Create Deal
                            </Button>
                            <Button variant="outline" size="sm" class="w-full justify-start" disabled>
                                Add Task
                            </Button>
                            <Button variant="outline" size="sm" class="w-full justify-start" disabled>
                                Send Email
                            </Button>
                            <Button 
                                v-if="contact.phone" 
                                variant="outline" 
                                size="sm" 
                                class="w-full justify-start"
                                as="a"
                                :href="`tel:${contact.phone}`"
                            >
                                <Phone class="w-4 h-4 mr-2" />
                                Call
                            </Button>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>