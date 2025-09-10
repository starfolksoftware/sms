<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
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

function deleteContact() {
  if (confirm('Are you sure you want to delete this contact?')) {
    router.delete(route('contacts.destroy', props.contact.id))
  }
}
</script>

<template>
  <Head :title="contact.name || 'Contact'" />

  <AppLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {{ contact.name || 'Contact' }}
        </h2>
        <div class="flex items-center gap-3">
          <Button
            v-if="canEditContact"
            @click="router.visit(route('contacts.edit', contact.id))"
          >
            Edit
          </Button>
          <Button
            v-if="canDeleteContact"
            variant="destructive"
            @click="deleteContact"
          >
            Delete
          </Button>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
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
                  @click="router.visit(route('deals.show', deal.id))"
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
                  @click="router.visit(route('tasks.show', task.id))"
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
</template>