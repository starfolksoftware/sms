<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import SelectField from '@/components/form/SelectField.vue'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Badge } from '@/components/ui/badge'

interface User {
  id: number
  name: string
}

interface Deal {
  id: number
  title: string
  value: string
  status: string
  expected_close_date: string | null
}

interface Task {
  id: number
  title: string
  status: string
  priority: string
  due_date: string | null
}

interface Contact {
  id: number
  first_name: string | null
  last_name: string | null
  name: string | null
  email: string | null
  phone: string | null
  company: string | null
  job_title: string | null
  status: string
  source: string
  owner: User | null
  creator: User
  notes: string | null
  created_at: string
  updated_at: string
  deals: Deal[]
  tasks: Task[]
}

const props = defineProps<{
  contact: Contact
  canEditContact: boolean
  canDeleteContact: boolean
}>()

// Edit & Delete Dialog State
const isEditDialogOpen = ref(false)
const isDeleteDialogOpen = ref(false)

// Edit form (re-using fields similar to standalone edit page)
const editForm = useForm({
  first_name: props.contact.first_name || '',
  last_name: props.contact.last_name || '',
  email: props.contact.email || '',
  phone: props.contact.phone || '',
  company: props.contact.company || '',
  job_title: props.contact.job_title || '',
  status: props.contact.status,
  source: props.contact.source,
  owner_id: props.contact.owner ? String(props.contact.owner.id) : '',
  notes: props.contact.notes || '',
})

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

function openEditDialog() {
  // Refresh form values from latest contact prop in case of stale data
  editForm.first_name = props.contact.first_name || ''
  editForm.last_name = props.contact.last_name || ''
  editForm.email = props.contact.email || ''
  editForm.phone = props.contact.phone || ''
  editForm.company = props.contact.company || ''
  editForm.job_title = props.contact.job_title || ''
  editForm.status = props.contact.status
  editForm.source = props.contact.source
  editForm.owner_id = props.contact.owner ? String(props.contact.owner.id) : ''
  editForm.notes = props.contact.notes || ''
  isEditDialogOpen.value = true
}

function submitEdit() {
  editForm.put(`/contacts/${props.contact.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isEditDialogOpen.value = false
      // Reload current contact data
      router.reload({ only: ['contact'] })
    }
  })
}

function formatSource(source: string) {
  const sourceLabels = {
    website_form: 'Website Form',
    meta_ads: 'Meta Ads',
    x: 'X (Twitter)',
    instagram: 'Instagram',
    referral: 'Referral',
    manual: 'Manual',
    other: 'Other',
  }
  return sourceLabels[source as keyof typeof sourceLabels] || source
}

function getStatusVariant(status: string) {
  const variants = {
    lead: 'default',
    qualified: 'secondary',
    customer: 'destructive',
    archived: 'outline',
  } as const
  return variants[status as keyof typeof variants] || 'default'
}

function openDeleteDialog() {
  isDeleteDialogOpen.value = true
}

function performDelete() {
  router.delete(`/contacts/${props.contact.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isDeleteDialogOpen.value = false
      router.visit('/contacts')
    },
    onFinish: () => {
      isDeleteDialogOpen.value = false
    }
  })
}
</script>

<template>
  <Head :title="contact.name || 'Contact'" />

  <AppLayout :breadcrumbs="[{ title: 'Contacts', href: '/contacts' }, { title: contact.name || 'Contact', href: `/contacts/${contact.id}` }]">
    <template #header>
  <div class="mt-6 flex items-center justify-between w-full px-6 lg:px-8">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ contact.name || 'Contact' }}
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Detailed contact information and recent activity.</p>
        </div>
        <div class="flex items-center gap-3">
          <Button
            v-if="canEditContact"
            @click="openEditDialog"
          >
            Edit
          </Button>
          <Button
            v-if="canDeleteContact"
            variant="destructive"
            @click="openDeleteDialog"
          >
            Delete
          </Button>
        </div>
      </div>
    </template>

    <div class="py-12 px-6 lg:px-8">
      <div class="space-y-6">
        <!-- Contact Information -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Main Info -->
          <div class="lg:col-span-2">
            <Card>
              <CardHeader>
                <CardTitle>Contact Information</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ contact.name || 'No name' }}
                    </dd>
                  </div>
                  
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ contact.email || '—' }}
                    </dd>
                  </div>
                  
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ contact.phone || '—' }}
                    </dd>
                  </div>
                  
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Company</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ contact.company || '—' }}
                    </dd>
                  </div>
                  
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Job Title</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ contact.job_title || '—' }}
                    </dd>
                  </div>
                  
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</dt>
                    <dd class="mt-1">
                      <Badge :variant="getStatusVariant(contact.status)" class="capitalize">
                        {{ contact.status }}
                      </Badge>
                    </dd>
                  </div>
                  
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Source</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ formatSource(contact.source) }}
                    </dd>
                  </div>
                  
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Owner</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                      {{ contact.owner?.name || 'Unassigned' }}
                    </dd>
                  </div>
                </div>
                
                <div v-if="contact.notes">
                  <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Notes</dt>
                  <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
                    {{ contact.notes }}
                  </dd>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Quick Stats -->
            <Card>
              <CardHeader>
                <CardTitle>Quick Stats</CardTitle>
              </CardHeader>
              <CardContent>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Deals</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                      {{ contact.deals.length }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tasks</dt>
                    <dd class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                      {{ contact.tasks.length }}
                    </dd>
                  </div>
                </dl>
              </CardContent>
            </Card>

            <!-- Recent Activity -->
            <Card>
              <CardHeader>
                <CardTitle>Recent Activity</CardTitle>
              </CardHeader>
              <CardContent>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Created {{ new Date(contact.created_at).toLocaleDateString() }}
                  <br />
                  by {{ contact.creator.name }}
                </p>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Related Deals -->
        <Card v-if="contact.deals.length > 0">
          <CardHeader>
            <CardTitle>Recent Deals</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div
                v-for="deal in contact.deals"
                :key="deal.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div>
                  <h4 class="font-medium">{{ deal.title }}</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Value: ${{ deal.value }} • Status: {{ deal.status }}
                  </p>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  @click="router.visit(`/deals/${deal.id}`)"
                >
                  View
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Related Tasks -->
        <Card v-if="contact.tasks.length > 0">
          <CardHeader>
            <CardTitle>Recent Tasks</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div
                v-for="task in contact.tasks"
                :key="task.id"
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg"
              >
                <div>
                  <h4 class="font-medium">{{ task.title }}</h4>
                  <p class="text-sm text-gray-500 dark:text-gray-400">
                    Status: {{ task.status }} • Priority: {{ task.priority }}
                  </p>
                </div>
                <Button
                  variant="ghost"
                  size="sm"
                  @click="router.visit(`/tasks/${task.id}`)"
                >
                  View
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>

  <!-- Edit Contact Dialog -->
  <Dialog v-model:open="isEditDialogOpen">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>Edit Contact</DialogTitle>
        <DialogDescription>Update the contact details and save your changes.</DialogDescription>
      </DialogHeader>
      <form @submit.prevent="submitEdit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <Label for="first_name">First Name</Label>
            <Input id="first_name" v-model="editForm.first_name" type="text" class="mt-1" />
            <div v-if="editForm.errors.first_name" class="text-sm text-red-600 mt-1">{{ editForm.errors.first_name }}</div>
          </div>
          <div>
            <Label for="last_name">Last Name</Label>
            <Input id="last_name" v-model="editForm.last_name" type="text" class="mt-1" />
            <div v-if="editForm.errors.last_name" class="text-sm text-red-600 mt-1">{{ editForm.errors.last_name }}</div>
          </div>
          <div>
            <Label for="email">Email</Label>
            <Input id="email" v-model="editForm.email" type="email" class="mt-1" />
            <div v-if="editForm.errors.email" class="text-sm text-red-600 mt-1">{{ editForm.errors.email }}</div>
          </div>
          <div>
            <Label for="phone">Phone</Label>
            <Input id="phone" v-model="editForm.phone" type="text" class="mt-1" />
            <div v-if="editForm.errors.phone" class="text-sm text-red-600 mt-1">{{ editForm.errors.phone }}</div>
          </div>
          <div>
            <Label for="company">Company</Label>
            <Input id="company" v-model="editForm.company" type="text" class="mt-1" />
            <div v-if="editForm.errors.company" class="text-sm text-red-600 mt-1">{{ editForm.errors.company }}</div>
          </div>
          <div>
            <Label for="job_title">Job Title</Label>
            <Input id="job_title" v-model="editForm.job_title" type="text" class="mt-1" />
            <div v-if="editForm.errors.job_title" class="text-sm text-red-600 mt-1">{{ editForm.errors.job_title }}</div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <Label>Status</Label>
            <SelectField class="mt-1" :model-value="editForm.status" @update:modelValue="v => editForm.status = v || 'lead'" :options="statusOptions" placeholder="Select status" />
            <div v-if="editForm.errors.status" class="text-sm text-red-600 mt-1">{{ editForm.errors.status }}</div>
          </div>
          <div>
            <Label>Source</Label>
            <SelectField class="mt-1" :model-value="editForm.source" @update:modelValue="v => editForm.source = v || 'manual'" :options="sourceOptions" placeholder="Select source" />
            <div v-if="editForm.errors.source" class="text-sm text-red-600 mt-1">{{ editForm.errors.source }}</div>
          </div>
        </div>
        <div>
          <Label for="notes">Notes</Label>
            <textarea id="notes" v-model="editForm.notes" rows="4" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2" />
          <div v-if="editForm.errors.notes" class="text-sm text-red-600 mt-1">{{ editForm.errors.notes }}</div>
        </div>
        <DialogFooter class="flex justify-end gap-3 pt-4">
          <Button type="button" variant="outline" @click="isEditDialogOpen = false">Cancel</Button>
          <Button type="submit" :disabled="editForm.processing">Save</Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>

  <!-- Delete Confirmation Dialog -->
  <Dialog v-model:open="isDeleteDialogOpen">
    <DialogContent class="max-w-md">
      <DialogHeader>
        <DialogTitle>Delete Contact</DialogTitle>
        <DialogDescription>This action cannot be undone. This will permanently delete the contact.</DialogDescription>
      </DialogHeader>
      <DialogFooter class="flex justify-end gap-3 pt-2">
        <Button variant="outline" type="button" @click="isDeleteDialogOpen = false">Cancel</Button>
        <Button variant="destructive" type="button" :disabled="editForm.processing" @click="performDelete">Delete</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>