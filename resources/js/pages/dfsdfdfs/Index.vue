<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, router, useForm, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { debounce } from '@/lib/utils'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
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
  notes?: string | null
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

  isLoading.value = true
  router.get('/contacts', params, {
    preserveState: () => false,
    replace: true,
    onSuccess: () => {
      const page: any = usePage()
      if (page.props?.contacts?.data) {
        localContacts.value = [...page.props.contacts.data]
      }
    },
    onFinish: () => { isLoading.value = false }
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

// Editing state handler passed into columns for inline edit dialog
const editingContact = ref<Contact | null>(null)
function openEdit(contact: Contact) {
  editingContact.value = contact
  // Pre-fill form with split name best-effort
  const nameParts = (contact.name || '').split(' ')
  createForm.first_name = nameParts.shift() || ''
  createForm.last_name = nameParts.join(' ') || ''
  createForm.email = contact.email || ''
  createForm.phone = contact.phone || ''
  createForm.company = contact.company || ''
  createForm.job_title = contact.job_title || ''
  createForm.status = contact.status
  createForm.source = contact.source
  createForm.owner_id = contact.owner ? String(contact.owner.id) : 'unassigned'
  // Prefill notes if present on contact (falls back to empty string)
  createForm.notes = (contact as any).notes || ''
  isCreateDialogOpen.value = true
}

const columns = buildContactColumns(openEdit)

// Local reactive contacts list for optimistic updates
const localContacts = ref<Contact[]>([...props.contacts.data])
const isLoading = ref(false)
// Sync when prop reference changes post-response
watch(() => props.contacts, (val) => { if (!isLoading.value && val?.data) { localContacts.value = [...val.data] } })

// ---------------------------------------------------------------------------
// Create Contact Dialog State & Form
// ---------------------------------------------------------------------------
const isCreateDialogOpen = ref(false)

const createForm = useForm({
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

const createStatusOptions = [
  { value: 'lead', label: 'Lead' },
  { value: 'qualified', label: 'Qualified' },
  { value: 'customer', label: 'Customer' },
  { value: 'archived', label: 'Archived' },
]

const createSourceOptions = [
  { value: 'website_form', label: 'Website Form' },
  { value: 'meta_ads', label: 'Meta Ads' },
  { value: 'x', label: 'X (Twitter)' },
  { value: 'instagram', label: 'Instagram' },
  { value: 'referral', label: 'Referral' },
  { value: 'manual', label: 'Manual' },
  { value: 'other', label: 'Other' },
]

function submitCreate() {
  // Normalize owner_id: treat sentinel 'unassigned' or empty string as null (no owner)
  const previousTransform = (createForm as any)._transform || ((data: any) => data)
  createForm.transform((data) => ({
    ...data,
    owner_id: data.owner_id === '' || data.owner_id === 'unassigned' ? null : data.owner_id,
  }))

  createForm.post('/contacts', {
    preserveScroll: true,
    onSuccess: () => {
      isCreateDialogOpen.value = false
      // Server will redirect back with updated list when reloaded; we optimistically prepend first.
      if ((createForm as any).recentlySuccessful !== false) {
        const optimisticId = Date.now()
        try {
          const optimistic: Contact = {
            id: optimisticId,
            name: [createForm.first_name, createForm.last_name].filter(Boolean).join(' ') || null,
            email: createForm.email || null,
            phone: createForm.phone || null,
            company: createForm.company || null,
            job_title: createForm.job_title || null,
            status: createForm.status,
            source: createForm.source,
            owner: createForm.owner_id && createForm.owner_id !== 'unassigned'
              ? { id: Number(createForm.owner_id), name: props.users.find(u => u.id === Number(createForm.owner_id))?.name || 'Owner' }
              : null,
            creator: { id: 0, name: 'You' },
            created_at: new Date().toISOString(),
            deals_count: 0,
            tasks_count: 0,
          }
          localContacts.value = [optimistic, ...localContacts.value]
        } catch { /* ignore */ }
      }
      toast.success('Contact created')
      createForm.reset()
      router.reload({ only: ['contacts'] })
    },
    onFinish: () => {
      // Restore original transform (identity) to avoid affecting other submissions
      createForm.transform(previousTransform)
    }
  })
}

function submitEdit() {
  if (!editingContact.value) { return }
  const id = editingContact.value.id
  const previousTransform = (createForm as any)._transform || ((data: any) => data)
  createForm.transform((data) => ({
    ...data,
    owner_id: data.owner_id === '' || data.owner_id === 'unassigned' ? null : data.owner_id,
    name: [data.first_name, data.last_name].filter(Boolean).join(' '),
  }))
  createForm.put(`/contacts/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      // Optimistically update local array (replace by id)
      const idx = localContacts.value.findIndex(c => c.id === id)
      if (idx !== -1) {
        localContacts.value[idx] = {
          ...localContacts.value[idx],
          name: [createForm.first_name, createForm.last_name].filter(Boolean).join(' ') || null,
          email: createForm.email || null,
          phone: createForm.phone || null,
          company: createForm.company || null,
          job_title: createForm.job_title || null,
          status: createForm.status,
          source: createForm.source,
          owner: createForm.owner_id && createForm.owner_id !== 'unassigned'
            ? { id: Number(createForm.owner_id), name: props.users.find(u => u.id === Number(createForm.owner_id))?.name || 'Owner' }
            : null,
          notes: createForm.notes || null,
        }
      }
      toast.success('Contact updated')
      isCreateDialogOpen.value = false
      editingContact.value = null
      router.reload({ only: ['contacts'] })
      createForm.reset()
    },
    onFinish: () => {
      createForm.transform(previousTransform)
    }
  })
}
</script>

<template>
  <Head title="Contacts" />

  <AppLayout :breadcrumbs="[{ title: 'Contacts', href: '/contacts' }]">
    <template #header>
  <div class="mt-6 flex items-center justify-between w-full px-6 lg:px-8">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Contacts</h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage and track your contacts.</p>
        </div>
        <Button
          v-if="canCreateContacts"
          @click="isCreateDialogOpen = true"
        >
          Create Contact
        </Button>
      </div>
    </template>

    <div class="py-12 px-0">
      <div class="w-full sm:px-6 lg:px-8 space-y-6">
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
                  @update:model-value="(value: any) => { searchForm.status = String((value ?? 'all')); performSearch(); }"
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
                  @update:model-value="(value: any) => { searchForm.source = String((value ?? 'all')); performSearch(); }"
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
                  @update:model-value="(value: any) => { searchForm.owner_id = String((value ?? 'all')); performSearch(); }"
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

  <!-- Action Buttons moved to header -->

        <!-- Contacts Table -->
        <Card>
          <CardHeader>
            <CardTitle>
              Contacts ({{ contacts.total }})
            </CardTitle>
          </CardHeader>
          <CardContent>
            <DataTable
              :data="localContacts"
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

        <!-- Create / Edit Contact Dialog -->
        <Dialog v-model:open="isCreateDialogOpen">
          <DialogContent class="max-w-3xl">
            <DialogHeader>
              <DialogTitle>{{ editingContact ? 'Edit Contact' : 'Create Contact' }}</DialogTitle>
              <DialogDescription>
                {{ editingContact ? 'Update the contact details and save your changes.' : 'Fill in the details below to add a new contact.' }}
              </DialogDescription>
            </DialogHeader>
            <form @submit.prevent="editingContact ? submitEdit() : submitCreate()" class="space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label for="first_name">First Name</Label>
                  <Input id="first_name" v-model="createForm.first_name" type="text" class="mt-1" />
                  <div v-if="createForm.errors.first_name" class="text-sm text-red-600 mt-1">{{ createForm.errors.first_name }}</div>
                </div>
                <div>
                  <Label for="last_name">Last Name</Label>
                  <Input id="last_name" v-model="createForm.last_name" type="text" class="mt-1" />
                  <div v-if="createForm.errors.last_name" class="text-sm text-red-600 mt-1">{{ createForm.errors.last_name }}</div>
                </div>
                <div>
                  <Label for="email">Email</Label>
                  <Input id="email" v-model="createForm.email" type="email" class="mt-1" />
                  <div v-if="createForm.errors.email" class="text-sm text-red-600 mt-1">{{ createForm.errors.email }}</div>
                </div>
                <div>
                  <Label for="phone">Phone</Label>
                  <Input id="phone" v-model="createForm.phone" type="text" class="mt-1" />
                  <div v-if="createForm.errors.phone" class="text-sm text-red-600 mt-1">{{ createForm.errors.phone }}</div>
                </div>
                <div>
                  <Label for="company">Company</Label>
                  <Input id="company" v-model="createForm.company" type="text" class="mt-1" />
                  <div v-if="createForm.errors.company" class="text-sm text-red-600 mt-1">{{ createForm.errors.company }}</div>
                </div>
                <div>
                  <Label for="job_title">Job Title</Label>
                  <Input id="job_title" v-model="createForm.job_title" type="text" class="mt-1" />
                  <div v-if="createForm.errors.job_title" class="text-sm text-red-600 mt-1">{{ createForm.errors.job_title }}</div>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <Label>Status</Label>
                  <Select :model-value="createForm.status" @update:model-value="(val: any) => createForm.status = String((val ?? 'lead'))">
                    <SelectTrigger class="mt-1">
                      <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in createStatusOptions" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                    </SelectContent>
                  </Select>
                  <div v-if="createForm.errors.status" class="text-sm text-red-600 mt-1">{{ createForm.errors.status }}</div>
                </div>
                <div>
                  <Label>Source</Label>
                  <Select :model-value="createForm.source" @update:model-value="(val: any) => createForm.source = String((val ?? 'manual'))">
                    <SelectTrigger class="mt-1">
                      <SelectValue placeholder="Select source" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in createSourceOptions" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                    </SelectContent>
                  </Select>
                  <div v-if="createForm.errors.source" class="text-sm text-red-600 mt-1">{{ createForm.errors.source }}</div>
                </div>
              </div>

              <div>
                <Label>Owner</Label>
                <Select :model-value="createForm.owner_id" @update:model-value="(val: any) => createForm.owner_id = String((val ?? ''))">
                  <SelectTrigger class="mt-1">
                    <SelectValue placeholder="Select owner (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="unassigned">Unassigned</SelectItem>
                    <SelectItem v-for="user in users" :key="user.id" :value="String(user.id)">{{ user.name }}</SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="createForm.errors.owner_id" class="text-sm text-red-600 mt-1">{{ createForm.errors.owner_id }}</div>
              </div>

              <div>
                <Label for="notes">Notes</Label>
                <textarea id="notes" v-model="createForm.notes" rows="4" class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" />
                <div v-if="createForm.errors.notes" class="text-sm text-red-600 mt-1">{{ createForm.errors.notes }}</div>
              </div>

              <div class="flex justify-end gap-3 pt-4">
                <Button type="button" variant="outline" @click="isCreateDialogOpen = false">Cancel</Button>
                <Button type="submit" :disabled="createForm.processing">{{ editingContact ? 'Save' : 'Create' }}</Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>
    </div>
  </AppLayout>
</template>