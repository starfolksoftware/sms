<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

interface User {
  id: number
  name: string
}

const props = defineProps<{
  users: User[]
}>()

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  company: '',
  job_title: '',
  status: 'lead',
  source: 'manual',
  source_meta: {},
  owner_id: '',
  notes: '',
})

function submit() {
  form.post(route('contacts.store'))
}

const statusOptions = [
  { value: 'lead', label: 'Lead' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'customer', label: 'Customer' },
  { value: 'archived', label: 'Archived' },
]

const sourceOptions = [
  { value: 'website_form', label: 'Website Form' },
  { value: 'meta_ads', label: 'Meta Ads' },
  { value: 'x', label: 'X (Twitter)' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'referral', label: 'Referral' },
  { value: 'manual', label: 'Manual' },
  { value: 'other', label: 'Other' },
]
</script>

<template>
  <Head title="Create Contact" />

  <AppLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Create Contact
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
        <Card>
          <CardHeader>
            <CardTitle>Contact Information</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Basic Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label for="first_name">First Name</Label>
                  <Input
                    id="first_name"
                    v-model="form.first_name"
                    type="text"
                    class="mt-1"
                  />
                  <div v-if="form.errors.first_name" class="text-sm text-red-600 mt-1">
                    {{ form.errors.first_name }}
                  </div>
                </div>

                <div>
                  <Label for="last_name">Last Name</Label>
                  <Input
                    id="last_name"
                    v-model="form.last_name"
                    type="text"
                    class="mt-1"
                  />
                  <div v-if="form.errors.last_name" class="text-sm text-red-600 mt-1">
                    {{ form.errors.last_name }}
                  </div>
                </div>

                <div>
                  <Label for="email">Email</Label>
                  <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1"
                  />
                  <div v-if="form.errors.email" class="text-sm text-red-600 mt-1">
                    {{ form.errors.email }}
                  </div>
                </div>

                <div>
                  <Label for="phone">Phone</Label>
                  <Input
                    id="phone"
                    v-model="form.phone"
                    type="text"
                    class="mt-1"
                  />
                  <div v-if="form.errors.phone" class="text-sm text-red-600 mt-1">
                    {{ form.errors.phone }}
                  </div>
                </div>

                <div>
                  <Label for="company">Company</Label>
                  <Input
                    id="company"
                    v-model="form.company"
                    type="text"
                    class="mt-1"
                  />
                  <div v-if="form.errors.company" class="text-sm text-red-600 mt-1">
                    {{ form.errors.company }}
                  </div>
                </div>

                <div>
                  <Label for="job_title">Job Title</Label>
                  <Input
                    id="job_title"
                    v-model="form.job_title"
                    type="text"
                    class="mt-1"
                  />
                  <div v-if="form.errors.job_title" class="text-sm text-red-600 mt-1">
                    {{ form.errors.job_title }}
                  </div>
                </div>
              </div>

              <!-- Contact Status & Source -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label>Status</Label>
                  <Select
                    :model-value="form.status"
                    @update:model-value="(value) => form.status = String(value ?? 'lead')"
                  >
                    <SelectTrigger class="mt-1">
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
                  <div v-if="form.errors.status" class="text-sm text-red-600 mt-1">
                    {{ form.errors.status }}
                  </div>
                </div>

                <div>
                  <Label>Source</Label>
                  <Select
                    :model-value="form.source"
                    @update:model-value="(value) => form.source = String(value ?? 'manual')"
                  >
                    <SelectTrigger class="mt-1">
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
                  <div v-if="form.errors.source" class="text-sm text-red-600 mt-1">
                    {{ form.errors.source }}
                  </div>
                </div>
              </div>

              <!-- Owner Assignment -->
              <div>
                <Label>Owner</Label>
                <Select
                  :model-value="form.owner_id"
                  @update:model-value="(value) => form.owner_id = String(value ?? '')"
                >
                  <SelectTrigger class="mt-1">
                    <SelectValue placeholder="Select owner (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">Unassigned</SelectItem>
                    <SelectItem
                      v-for="user in users"
                      :key="user.id"
                      :value="String(user.id)"
                    >
                      {{ user.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.owner_id" class="text-sm text-red-600 mt-1">
                  {{ form.errors.owner_id }}
                </div>
              </div>

              <!-- Notes -->
              <div>
                <Label for="notes">Notes</Label>
                <textarea
                  id="notes"
                  v-model="form.notes"
                  rows="4"
                  class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                  placeholder="Additional notes about this contact..."
                />
                <div v-if="form.errors.notes" class="text-sm text-red-600 mt-1">
                  {{ form.errors.notes }}
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 pt-6">
                <Button
                  type="button"
                  variant="outline"
                  @click="$inertia.visit(route('contacts.index'))"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  :disabled="form.processing"
                >
                  Create Contact
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>