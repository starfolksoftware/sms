<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'

const props = defineProps<{ email: string; token: string }>()

const form = useForm({
  token: props.token,
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post('/invitation/accept', {
    preserveScroll: true,
  })
}
</script>

<template>
  <Head title="Accept Invitation" />
  <AuthLayout title="Accept invitation" :description="`You’re accepting an invitation for ${email}.`">
    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <Label for="password">Password</Label>
        <Input id="password" v-model="form.password" type="password" required />
        <div v-if="form.errors.password" class="text-red-600 text-sm mt-1">{{ form.errors.password }}</div>
      </div>
      <div>
        <Label for="password_confirmation">Confirm Password</Label>
        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" required />
      </div>
      <div class="flex justify-end">
        <Button type="submit" :disabled="form.processing">Set Password & Continue</Button>
      </div>
    </form>
  </AuthLayout>
</template>
