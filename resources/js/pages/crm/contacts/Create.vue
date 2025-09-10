<script setup lang="ts">
import ContactForm from './ContactForm.vue';

interface Props {
    owners: Array<{ id: number; name: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    sourceOptions: Array<{ value: string; label: string }>;
}

defineProps<Props>();
</script>

<template>
    <ContactForm
        :owners="owners"
        :status-options="statusOptions"
        :source-options="sourceOptions"
    />
</template>