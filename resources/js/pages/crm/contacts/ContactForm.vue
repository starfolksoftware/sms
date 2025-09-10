<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertTriangle, Save, X } from 'lucide-vue-next';
import { Head, useForm, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
    owner_id: number;
    notes: string;
}

interface Props {
    contact?: Contact;
    owners: Array<{ id: number; name: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    sourceOptions: Array<{ value: string; label: string }>;
}

const props = defineProps<Props>();

const isEdit = computed(() => !!props.contact);

const form = useForm({
    name: props.contact?.name || '',
    first_name: props.contact?.first_name || '',
    last_name: props.contact?.last_name || '',
    email: props.contact?.email || '',
    phone: props.contact?.phone || '',
    company: props.contact?.company || '',
    job_title: props.contact?.job_title || '',
    status: props.contact?.status || 'lead',
    source: props.contact?.source || 'manual',
    owner_id: props.contact?.owner_id || null,
    notes: props.contact?.notes || '',
});

// Auto-populate name field when first/last name changes
watch([() => form.first_name, () => form.last_name], ([firstName, lastName]) => {
    if (firstName && lastName) {
        form.name = `${firstName} ${lastName}`;
    }
});

// Duplicate warning state
const duplicateWarning = ref('');
const checkingDuplicate = ref(false);

// Check for duplicates when email changes
const checkForDuplicates = async (email: string) => {
    if (!email || checkingDuplicate.value) return;
    
    checkingDuplicate.value = true;
    duplicateWarning.value = '';
    
    try {
        // This would be an API call in a real app
        // For now, we'll handle this in the form submission
    } catch (error) {
        console.error('Error checking duplicates:', error);
    } finally {
        checkingDuplicate.value = false;
    }
};

let emailTimeout: NodeJS.Timeout;
watch(() => form.email, (newEmail) => {
    clearTimeout(emailTimeout);
    emailTimeout = setTimeout(() => {
        if (newEmail) {
            checkForDuplicates(newEmail);
        } else {
            duplicateWarning.value = '';
        }
    }, 500);
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('crm.contacts.update', props.contact!.id), {
            onSuccess: () => {
                // Success handled by redirect
            },
        });
    } else {
        form.post(route('crm.contacts.store'), {
            onSuccess: () => {
                // Success handled by redirect
            },
        });
    }
};

const cancel = () => {
    if (isEdit.value) {
        router.visit(route('crm.contacts.show', props.contact!.id));
    } else {
        router.visit(route('crm.contacts.index'));
    }
};

const breadcrumbs = computed(() => {
    const base = [
        { title: 'CRM', href: '#' },
        { title: 'Contacts', href: route('crm.contacts.index') }
    ];
    
    if (isEdit.value) {
        return [
            ...base,
            { title: props.contact!.name, href: route('crm.contacts.show', props.contact!.id) },
            { title: 'Edit', href: route('crm.contacts.edit', props.contact!.id) }
        ];
    } else {
        return [
            ...base,
            { title: 'Create', href: route('crm.contacts.create') }
        ];
    }
});
</script>

<template>
    <Head :title="isEdit ? `Edit ${contact?.name}` : 'Create Contact'" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="max-w-3xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ isEdit ? `Edit ${contact?.name}` : 'Create Contact' }}
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">
                        {{ isEdit ? 'Update contact information' : 'Add a new contact to your CRM' }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Basic Information -->
                <Card>
                    <CardHeader>
                        <CardTitle>Basic Information</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <!-- Name fields -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="first_name">First Name</Label>
                                <Input
                                    id="first_name"
                                    v-model="form.first_name"
                                    :error="form.errors.first_name"
                                />
                                <div v-if="form.errors.first_name" class="text-sm text-red-600">
                                    {{ form.errors.first_name }}
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="last_name">Last Name</Label>
                                <Input
                                    id="last_name"
                                    v-model="form.last_name"
                                    :error="form.errors.last_name"
                                />
                                <div v-if="form.errors.last_name" class="text-sm text-red-600">
                                    {{ form.errors.last_name }}
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="name">Full Name</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    placeholder="Auto-filled from first/last name"
                                    :error="form.errors.name"
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-600">
                                    {{ form.errors.name }}
                                </div>
                            </div>
                        </div>

                        <!-- Contact details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    :error="form.errors.email"
                                />
                                <div v-if="form.errors.email" class="text-sm text-red-600">
                                    {{ form.errors.email }}
                                </div>
                                <div v-if="checkingDuplicate" class="text-sm text-blue-600">
                                    Checking for duplicates...
                                </div>
                                <Alert v-if="duplicateWarning" class="mt-2">
                                    <AlertTriangle class="h-4 w-4" />
                                    <AlertDescription>
                                        {{ duplicateWarning }}
                                    </AlertDescription>
                                </Alert>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    v-model="form.phone"
                                    :error="form.errors.phone"
                                />
                                <div v-if="form.errors.phone" class="text-sm text-red-600">
                                    {{ form.errors.phone }}
                                </div>
                            </div>
                        </div>

                        <!-- Company details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="company">Company</Label>
                                <Input
                                    id="company"
                                    v-model="form.company"
                                    :error="form.errors.company"
                                />
                                <div v-if="form.errors.company" class="text-sm text-red-600">
                                    {{ form.errors.company }}
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="job_title">Job Title</Label>
                                <Input
                                    id="job_title"
                                    v-model="form.job_title"
                                    :error="form.errors.job_title"
                                />
                                <div v-if="form.errors.job_title" class="text-sm text-red-600">
                                    {{ form.errors.job_title }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Classification -->
                <Card>
                    <CardHeader>
                        <CardTitle>Classification</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="space-y-2">
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in statusOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.status" class="text-sm text-red-600">
                                    {{ form.errors.status }}
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="source">Source</Label>
                                <Select v-model="form.source">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select source" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in sourceOptions"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.source" class="text-sm text-red-600">
                                    {{ form.errors.source }}
                                </div>
                            </div>
                            
                            <div class="space-y-2">
                                <Label for="owner_id">Owner</Label>
                                <Select v-model="form.owner_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select owner" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="owner in owners"
                                            :key="owner.id"
                                            :value="owner.id"
                                        >
                                            {{ owner.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div v-if="form.errors.owner_id" class="text-sm text-red-600">
                                    {{ form.errors.owner_id }}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card>
                    <CardHeader>
                        <CardTitle>Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <Label for="notes">Additional Notes</Label>
                            <Textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                placeholder="Add any additional notes about this contact..."
                            />
                            <div v-if="form.errors.notes" class="text-sm text-red-600">
                                {{ form.errors.notes }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4">
                    <Button type="button" variant="outline" @click="cancel">
                        <X class="w-4 h-4 mr-2" />
                        Cancel
                    </Button>
                    
                    <Button type="submit" :disabled="form.processing">
                        <Save class="w-4 h-4 mr-2" />
                        {{ isEdit ? 'Update Contact' : 'Create Contact' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>