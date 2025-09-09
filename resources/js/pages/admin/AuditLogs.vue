<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3'
import { ref, h } from 'vue'

import AppLayout from '@/layouts/AppLayout.vue'
import AdminLayout from '@/layouts/admin/Layout.vue'
import HeadingSmall from '@/components/HeadingSmall.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge/index'
import DataTable from '@/components/ui/data-table/DataTable.vue'
import type { ColumnDef } from '@tanstack/vue-table'
import admin from '@/routes/admin'

interface User { id: number; name?: string | null; email?: string | null }
interface Subject { id?: number | null; type?: string | null }
interface Activity {
  id: number
  log_name: string | null
  description: string | null
  created_at: string
  causer?: User | null
  subject?: Subject | null
  properties?: Record<string, unknown>
}

interface Props {
  logs: { data: Activity[]; current_page: number; last_page: number; per_page: number; total: number; links: any[] }
  filters: { log_names: string[]; events: string[] }
  applied?: { log_name?: string | null; event?: string | null; causer_type?: string | null; causer_id?: number | null }
}

const props = defineProps<Props>()

const qLogName = ref(props.applied?.log_name ?? '')
const qEvent = ref(props.applied?.event ?? '')
const qCauserType = ref(props.applied?.causer_type ?? '')
const qCauserId = ref(props.applied?.causer_id ?? '')
const sortField = ref<string>('created_at')
const sortDirection = ref<'asc' | 'desc'>('desc')

function applyFilters() {
  router.get(admin.auditLogs.index.url({ query: {
    log_name: qLogName.value || undefined,
    event: qEvent.value || undefined,
    causer_type: qCauserType.value || undefined,
  causer_id: qCauserId.value || undefined,
  sort: sortField.value,
  direction: sortDirection.value,
  }}), {}, { preserveState: true, preserveScroll: true })
}

function resetFilters() {
  qLogName.value = ''
  qEvent.value = ''
  qCauserType.value = ''
  qCauserId.value = ''
  applyFilters()
}

function onSort(field: string) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
  applyFilters()
}

type ActivityRow = Activity

const columns: ColumnDef<ActivityRow, any>[] = [
  {
    accessorKey: 'created_at',
    header: 'Time',
    meta: { sortable: true, sortField: 'created_at' },
    cell: ({ row }) => new Date(row.original.created_at).toLocaleString(),
  },
  {
    id: 'log_name',
    header: 'Log',
    meta: { sortable: true, sortField: 'log_name' },
    cell: ({ row }) => h(Badge, { variant: 'secondary' }, { default: () => row.original.log_name ?? '' }),
  },
  {
    id: 'event',
    header: 'Event',
    meta: { sortable: true, sortField: 'description' },
    cell: ({ row }) => row.original.description ?? '',
  },
  {
    id: 'causer',
    header: 'Causer',
    cell: ({ row }) => {
      const c = row.original.causer
      if (!c) return h('span', { class: 'text-muted-foreground' }, '—')
      return c.name || c.email || `User #${c.id}`
    },
  },
  {
    id: 'subject',
    header: 'Subject',
    cell: ({ row }) => {
      const s = row.original.subject
      if (!s) return h('span', { class: 'text-muted-foreground' }, '—')
      const type = (s.type ?? '').split('\\').pop()
      return `${type} #${s.id}`
    },
  },
]
</script>

<template>
  <AppLayout>
    <Head title="Audit Logs" />

    <AdminLayout title="Audit Logs" description="Review system activities and events">
      <div class="space-y-6">
        <HeadingSmall title="Audit Logs" description="Search and review audit trail entries" />

        <Card>
          <CardHeader>
            <CardTitle>Filters</CardTitle>
            <CardDescription>Narrow down results by log name, event, or causer</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid gap-4 md:grid-cols-4">
              <div class="space-y-2">
                <Label for="logName">Log Name</Label>
                <Input id="logName" list="log-name-options" v-model="qLogName" placeholder="e.g. default" />
                <datalist id="log-name-options">
                  <option v-for="ln in props.filters.log_names" :key="ln" :value="ln" />
                </datalist>
              </div>
              <div class="space-y-2">
                <Label for="event">Event</Label>
                <Input id="event" list="event-options" v-model="qEvent" placeholder="e.g. created" />
                <datalist id="event-options">
                  <option v-for="ev in props.filters.events" :key="ev" :value="ev" />
                </datalist>
              </div>
              <div class="space-y-2">
                <Label for="causerType">Causer Type</Label>
                <Input id="causerType" v-model="qCauserType" placeholder="App\\Models\\User" />
              </div>
              <div class="space-y-2">
                <Label for="causerId">Causer ID</Label>
                <Input id="causerId" v-model="qCauserId" type="number" min="1" placeholder="1" />
              </div>
            </div>
            <div class="mt-4 flex gap-2">
              <Button @click="applyFilters">Apply</Button>
              <Button variant="outline" @click="resetFilters">Reset</Button>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Results</CardTitle>
            <CardDescription>Showing {{ props.logs.data.length }} of {{ props.logs.total }}</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="overflow-x-auto">
              <DataTable
                :columns="columns"
                :data="props.logs.data as any[]"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="onSort"
                empty-text="No activity found"
              />
            </div>

            <!-- Simple pagination (uses server-provided links if needed later) -->
            <div class="mt-4 text-sm text-muted-foreground">
              Page {{ props.logs.current_page }} of {{ props.logs.last_page }} — {{ props.logs.total }} total
            </div>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  </AppLayout>
</template>
