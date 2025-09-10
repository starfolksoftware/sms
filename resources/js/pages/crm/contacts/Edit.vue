<script setup lang="ts">
import ContactForm from './ContactForm.vue';

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
    contact: Contact;
    owners: Array<{ id: number; name: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    sourceOptions: Array<{ value: string; label: string }>;
}

defineProps<Props>();
</script>

<template>
    <ContactForm
        :contact="contact"
        :owners="owners"
        :status-options="statusOptions"
        :source-options="sourceOptions"
    />
</template>