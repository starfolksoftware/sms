import type { ColumnDef } from '@tanstack/vue-table'
import { h } from 'vue'
import { router } from '@inertiajs/vue3'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'

interface Contact {
  id: number
  name: string | null
  email: string | null
  phone: string | null
  company: string | null
  job_title: string | null
  status: string
  source: string
  owner: { id: number; name: string } | null
  creator: { id: number; name: string }
  created_at: string
  deals_count: number
  tasks_count: number
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

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString()
}

export function buildContactColumns(): ColumnDef<Contact>[] {
  return [
    {
      id: 'name',
      header: 'Name',
      cell: ({ row }) => {
        const contact = row.original
        return h('div', { class: 'space-y-1' }, [
          h('div', { class: 'font-medium' }, contact.name || 'No name'),
          contact.job_title && h('div', { class: 'text-sm text-muted-foreground' }, contact.job_title)
        ])
      },
      meta: { sortable: true, sortField: 'name' },
    },
    {
      id: 'email',
      header: 'Email',
      accessorKey: 'email',
      cell: ({ row }) => row.original.email || '—',
      meta: { sortable: true, sortField: 'email' },
    },
    {
      id: 'phone',
      header: 'Phone',
      accessorKey: 'phone',
      cell: ({ row }) => row.original.phone || '—',
    },
    {
      id: 'company',
      header: 'Company',
      accessorKey: 'company',
      cell: ({ row }) => row.original.company || '—',
      meta: { sortable: true, sortField: 'company' },
    },
    {
      id: 'status',
      header: 'Status',
      cell: ({ row }) => {
        const status = row.original.status
        return h(Badge, { 
          variant: getStatusVariant(status),
          class: 'capitalize'
        }, () => status)
      },
      meta: { sortable: true, sortField: 'status' },
    },
    {
      id: 'source',
      header: 'Source',
      cell: ({ row }) => formatSource(row.original.source),
      meta: { sortable: true, sortField: 'source' },
    },
    {
      id: 'owner',
      header: 'Owner',
      cell: ({ row }) => row.original.owner?.name || 'Unassigned',
    },
    {
      id: 'created_at',
      header: 'Created',
      cell: ({ row }) => formatDate(row.original.created_at),
      meta: { sortable: true, sortField: 'created_at' },
    },
    {
      id: 'actions',
      header: 'Actions',
      cell: ({ row }) => {
        const contact = row.original
        return h('div', { class: 'flex items-center gap-2' }, [
          h(Button, {
            variant: 'ghost',
            size: 'sm',
            onClick: () => router.visit(route('contacts.show', contact.id))
          }, () => 'View'),
          h(Button, {
            variant: 'ghost', 
            size: 'sm',
            onClick: () => router.visit(route('contacts.edit', contact.id))
          }, () => 'Edit'),
        ])
      },
    },
  ]
}