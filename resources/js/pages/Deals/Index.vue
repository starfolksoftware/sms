<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import DataTable from '@/components/ui/data-table/DataTable.vue'

interface User {
  id: number
  name: string
}

interface Contact {
  id: number
  name: string
  email: string
}

interface Product {
  id: number
  name: string
}

interface Deal {
  id: number
  title: string
  amount: string | null
  currency: string
  stage: string
  status: string
  expected_close_date: string | null
  contact: Contact
  owner: User | null
  product: Product | null
  created_at: string
}

interface DealsResponse {
  data: Deal[]
  current_page: number
  last_page: number
  links: any[]
}

interface Props {
  deals: DealsResponse
  filters: {
    q?: string
    status?: string
    stage?: string
    owner_id?: string
    contact_id?: string
    product_id?: string
    created_from?: string
    created_to?: string
    expected_close_from?: string
    expected_close_to?: string
    sort?: string
  }
  enums: {
    stages: string[]
    statuses: string[]
    sources: string[]
  }
}

const props = defineProps<Props>()

const search = ref(props.filters.q || '')
const selectedStatus = ref(props.filters.status || '')
const selectedStage = ref(props.filters.stage || '')

// Note: Create/Edit now use standalone pages; dialogs removed.

const columns = [
  {
    accessorKey: 'title',
    header: 'Title',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      return `<div class="font-medium">${deal.title}</div>`
    }
  },
  {
    accessorKey: 'contact.name',
    header: 'Contact',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      return deal.contact?.name || 'N/A'
    }
  },
  {
    accessorKey: 'amount',
    header: 'Amount',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      return deal.amount ? `${deal.currency} ${Number(deal.amount).toLocaleString()}` : 'N/A'
    }
  },
  {
    accessorKey: 'stage',
    header: 'Stage',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      const stageColors: Record<string, string> = {
        new: 'bg-blue-100 text-blue-800',
        qualified: 'bg-green-100 text-green-800',
        proposal: 'bg-yellow-100 text-yellow-800',
        negotiation: 'bg-orange-100 text-orange-800',
        closed: 'bg-gray-100 text-gray-800'
      }
      const colorClass = stageColors[deal.stage] || 'bg-gray-100 text-gray-800'
      return `<span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${colorClass}">${deal.stage}</span>`
    }
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      const statusColors: Record<string, string> = {
        open: 'bg-blue-100 text-blue-800',
        won: 'bg-green-100 text-green-800',
        lost: 'bg-red-100 text-red-800'
      }
      const colorClass = statusColors[deal.status] || 'bg-gray-100 text-gray-800'
      return `<span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${colorClass}">${deal.status}</span>`
    }
  },
  {
    accessorKey: 'expected_close_date',
    header: 'Expected Close',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      return deal.expected_close_date ? new Date(deal.expected_close_date).toLocaleDateString() : 'N/A'
    }
  },
  {
    accessorKey: 'owner.name',
    header: 'Owner',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      return deal.owner?.name || 'Unassigned'
    }
  },
  {
    id: 'actions',
    header: 'Actions',
    cell: ({ row }: { row: { original: Deal } }) => {
      const deal = row.original
      return `
        <div class="flex space-x-2">
          <a href="/crm/deals/${deal.id}" class="text-blue-600 hover:text-blue-900 text-sm">View</a>
          <a href="/crm/deals/${deal.id}/edit" class="text-green-600 hover:text-green-900 text-sm">Edit</a>
        </div>
      `
    }
  }
]

const applyFilters = () => {
  router.get('/crm/deals', {
    q: search.value,
    status: selectedStatus.value,
    stage: selectedStage.value,
  }, {
    preserveState: true,
    replace: true
  })
}

const clearFilters = () => {
  search.value = ''
  selectedStatus.value = ''
  selectedStage.value = ''
  applyFilters()
}

// No delegated click handlers needed; actions use normal links
</script>

<template>
  <Head title="Deals" />
  
  <AppLayout>
  <template #header>
      <div class="mt-6 flex items-center justify-between w-full px-6 lg:px-8">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Deals</h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">A list of all deals including their title, contact, amount, stage and status.</p>
        </div>
  <Button @click="router.visit('/crm/deals/new')">Add Deal</Button>
      </div>
    </template>

    <div class="py-12 px-0">
      <div class="w-full sm:px-6 lg:px-8 space-y-6">
        <!-- Filters -->
        <Card>
          <CardHeader>
            <CardTitle class="text-lg">Filters</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <Label for="search">Search</Label>
                <Input
                  id="search"
                  v-model="search"
                  placeholder="Search deals..."
                  @keyup.enter="applyFilters"
                />
              </div>
              <div>
                <Label for="status">Status</Label>
                <Select v-model="selectedStatus">
                  <SelectTrigger>
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">All Statuses</SelectItem>
                    <SelectItem
                      v-for="status in enums.statuses"
                      :key="status"
                      :value="status"
                    >
                      {{ status }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label for="stage">Stage</Label>
                <Select v-model="selectedStage">
                  <SelectTrigger>
                    <SelectValue placeholder="Select stage" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="">All Stages</SelectItem>
                    <SelectItem
                      v-for="stage in enums.stages"
                      :key="stage"
                      :value="stage"
                    >
                      {{ stage }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="flex items-end gap-2">
                <Button @click="applyFilters" class="flex-1">Apply Filters</Button>
                <Button @click="clearFilters" variant="outline">Clear</Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Deals Table -->
        <Card>
          <CardContent class="p-0">
            <DataTable
              :columns="columns"
              :data="deals.data"
              :enable-pagination="true"
              :pagination-links="deals.links"
              :current-page="deals.current_page"
              :last-page="deals.last_page"
            />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>

</template>