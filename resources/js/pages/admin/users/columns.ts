import type { ColumnDef } from '@tanstack/vue-table'
import { h } from 'vue'

type Role = { id: number | string; name: string }
export type UserRow = {
  id: number | string
  name: string
  email: string
  status?: string
  last_login_at?: string | null
  roles: Role[]
}

type BuildColumnsOptions = {
  // Sorting helpers (server-driven)
  sortField: string
  sortDirection: 'asc' | 'desc'
  // Action handlers
  onEdit: (user: UserRow) => void
  onResend: (user: UserRow) => void
  onDelete: (user: UserRow) => void
  // Formatters
  getStatusBadgeClasses: (status: string) => string
  getStatusLabel: (status?: string) => string
  formatDate: (dateString: string | null | undefined) => string
}

export function buildUserColumns(opts: BuildColumnsOptions): ColumnDef<UserRow, any>[] {
  return [
    {
      accessorKey: 'name',
      header: 'Name',
      meta: { sortable: true, sortField: 'name' },
      cell: ({ row }) => row.original.name,
    },
    {
      accessorKey: 'email',
      header: 'Email',
      meta: { sortable: true, sortField: 'email' },
      cell: ({ row }) => row.original.email,
    },
    {
      id: 'roles',
      header: 'Roles',
      cell: ({ row }) => {
        const roles = Array.isArray(row.original.roles) ? row.original.roles : []
        return h(
          'div',
          { class: 'flex flex-wrap gap-1' },
          roles.map((r) => h(
            'span',
            { class: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' },
            r.name,
          )),
        )
      },
    },
    {
      id: 'status',
      header: 'Status',
      cell: ({ row }) => {
        const s = row.original.status ?? ''
        const label = opts.getStatusLabel(s)
        const klass = opts.getStatusBadgeClasses(s)
        return h('span', { class: `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${klass}` }, label)
      },
    },
    {
      accessorKey: 'last_login_at',
      header: 'Last Login',
      meta: { sortable: true, sortField: 'last_login_at' },
      cell: ({ row }) => opts.formatDate(row.original.last_login_at ?? null),
    },
    {
      id: 'actions',
      header: 'Actions',
      cell: ({ row }) => {
        const u = row.original
        const canResend = u.status === 'pending_invite'
        return h(
          'div',
          { class: 'flex gap-2' },
          [
            h('button', { class: 'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3', onClick: () => opts.onEdit(u) }, 'Edit'),
            canResend ? h('button', { class: 'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3', onClick: () => opts.onResend(u) }, 'Resend') : null,
            h('button', { class: 'inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-destructive text-destructive-foreground hover:bg-destructive/90 h-9 px-3', onClick: () => opts.onDelete(u) }, 'Delete'),
          ],
        )
      },
    },
  ]
}
