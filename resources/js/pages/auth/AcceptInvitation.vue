<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
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
  <AppLayout>
    <div class="max-w-md mx-auto py-12">
      <h1 class="text-2xl font-semibold mb-6">Accept Invitation</h1>
      <p class="mb-4 text-sm text-muted-foreground">You're accepting an invitation for <strong>{{ email }}</strong>.</p>

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
    </div>
  </AppLayout>
</template>
