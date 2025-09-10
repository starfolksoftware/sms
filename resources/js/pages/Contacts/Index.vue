<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { debounce } from '@/lib/utils'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import DataTable from '@/components/ui/data-table/DataTable.vue'
// NOTE: The working columns helper lives in nested 'contacts/contacts/columns.ts' due to legacy structure.
// Using relative path with explicit lowercase segment to prevent TS case-collision.
import { buildContactColumns } from './columns'

interface User {
  id: number
  name: string
}

interface Contact {
  id: number
  name: string | null
  email: string | null
  phone: string | null
  company: string | null
  job_title: string | null
  status: string
  source: string
  owner: User | null
  creator: User
  created_at: string
  deals_count: number
  tasks_count: number
}

interface Link {
  url: string | null
  label: string
  active: boolean
}

interface ContactsResponse {
  data: Contact[]
  links: Link[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface SearchForm {
  search: string
  status: string
  source: string
  owner_id: string
}

interface Filters extends Partial<SearchForm> {
  sort?: string
  direction?: 'asc' | 'desc'
}

const props = withDefaults(defineProps<{
  contacts: ContactsResponse
  filters?: Filters
  users: User[]
  canCreateContacts: boolean
}>(), {
  filters: () => ({}),
})

const searchForm = useForm<SearchForm>({
  search: props.filters?.search || '',
  status: props.filters?.status || 'all',
  source: props.filters?.source || 'all',
  owner_id: props.filters?.owner_id || 'all',
})

// Sorting state kept separate to avoid any potential name collisions with native Array prototype
const sortField = ref<string>((props.filters as any)?.sort_by || 'created_at')
const sortDirection = ref<'asc' | 'desc'>((props.filters as any)?.sort_direction || 'desc')

// Normalize filter values
if (searchForm.status === '') searchForm.status = 'all'
if (searchForm.source === '') searchForm.source = 'all'
if (searchForm.owner_id === '') searchForm.owner_id = 'all'

const statusOptions = [
  { value: 'all', label: 'All Statuses' },
  { value: 'lead', label: 'Lead' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'customer', label: 'Customer' },
  { value: 'archived', label: 'Archived' },
]

const sourceOptions = [
  { value: 'all', label: 'All Sources' },
  { value: 'website_form', label: 'Website Form' },
  { value: 'meta_ads', label: 'Meta Ads' },
  { value: 'x', label: 'X (Twitter)' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'referral', label: 'Referral' },
  { value: 'manual', label: 'Manual' },
  { value: 'other', label: 'Other' },
]

const ownerOptions = computed(() => [
  { value: 'all', label: 'All Owners' },
  { value: 'unassigned', label: 'Unassigned' },
  ...props.users.map(user => ({ value: String(user.id), label: user.name }))
])

const debouncedSearch = debounce(() => {
  performSearch()
}, 300)

function performSearch() {
  const params = {
    search: searchForm.search || undefined,
    status: searchForm.status !== 'all' ? searchForm.status : undefined,
    source: searchForm.source !== 'all' ? searchForm.source : undefined,
    owner_id: searchForm.owner_id !== 'all' ? (searchForm.owner_id === 'unassigned' ? '0' : searchForm.owner_id) : undefined,
    sort_by: sortField.value,
    sort_direction: sortDirection.value,
  }

  router.get('/contacts', params, {
    preserveState: true,
    replace: true,
  })
}

function clearFilters() {
  searchForm.search = ''
  searchForm.status = 'all'
  searchForm.source = 'all'
  searchForm.owner_id = 'all'
  sortField.value = 'created_at'
  sortDirection.value = 'desc'
  performSearch()
}

function handleSort(field: string) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
  performSearch()
}

const columns = buildContactColumns()
</script>

<template>
  <Head title="Contacts" />

  <AppLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
        Contacts
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
        <!-- Search and Filters -->
        <Card>
          <CardHeader>
            <CardTitle>Search & Filters</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="flex flex-wrap gap-4 items-end">
              <div class="flex-1 min-w-64">
                <Label for="search">Search contacts</Label>
                <Input
                  id="search"
                  v-model="searchForm.search"
                  placeholder="Search by name, email, company..."
                  @input="debouncedSearch"
                />
              </div>
              <div class="w-48">
                <Label>Status</Label>
                <Select
                  :model-value="searchForm.status"
                  @update:model-value="(value) => { searchForm.status = String(value ?? 'all'); performSearch(); }"
                >
                  <SelectTrigger>
                    <SelectValue placeholder="All Statuses" />
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
              </div>
              <div class="w-48">
                <Label>Source</Label>
                <Select
                  :model-value="searchForm.source"
                  @update:model-value="(value) => { searchForm.source = String(value ?? 'all'); performSearch(); }"
                >
                  <SelectTrigger>
                    <SelectValue placeholder="All Sources" />
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
              </div>
              <div class="w-48">
                <Label>Owner</Label>
                <Select
                  :model-value="searchForm.owner_id"
                  @update:model-value="(value) => { searchForm.owner_id = String(value ?? 'all'); performSearch(); }"
                >
                  <SelectTrigger>
                    <SelectValue placeholder="All Owners" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="option in ownerOptions"
                      :key="option.value"
                      :value="option.value"
                    >
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="flex">
                <Button variant="outline" @click="clearFilters">Clear</Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Action Buttons -->
        <div class="flex gap-4">
          <Button
            v-if="canCreateContacts"
            @click="router.visit('/contacts/create')"
          >
            Create Contact
          </Button>
        </div>

        <!-- Contacts Table -->
        <Card>
          <CardHeader>
            <CardTitle>
              Contacts ({{ contacts.total }})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <DataTable
              :data="contacts.data"
              :columns="columns"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              empty-text="No contacts found"
              @sort="handleSort"
            />

            <!-- Pagination -->
            <div v-if="contacts.links.length > 3" class="mt-6 flex justify-center">
              <nav class="flex space-x-2">
                <template v-for="(link, index) in contacts.links" :key="index">
                  <Button
                    v-if="link.url"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    @click="router.visit(link.url, { preserveState: true })"
                  >
                    {{ link.label }}
                  </Button>
                  <span v-else class="px-3 py-2 text-sm text-gray-500">
                    {{ link.label }}
                  </span>
                </template>
              </nav>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>