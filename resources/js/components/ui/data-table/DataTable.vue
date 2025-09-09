<script setup lang="ts">
import { computed } from 'vue'
import type { ColumnDef, ColumnMeta, Table as TanTable } from '@tanstack/vue-table'
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'

type SortDirection = 'asc' | 'desc'

const props = withDefaults(defineProps<{
  // Data & columns
  data: any[]
  columns: ColumnDef<any, any>[]
  // Server-driven sorting state (optional)
  sortField?: string
  sortDirection?: SortDirection
  // Meta-based header click sorting
  sortable?: boolean
  // Row id accessor (optional)
  getRowId?: (row: any) => string | number
  // Empty state
  emptyText?: string
}>(), {
  data: () => [],
  columns: () => [],
  sortable: true,
  emptyText: 'No results',
})

const emit = defineEmits<{
  (e: 'sort', field: string): void
}>()

const table = useVueTable({
  data: props.data,
  columns: props.columns,
  getCoreRowModel: getCoreRowModel(),
  getRowId: (row, index) => String(props.getRowId ? props.getRowId(row) : (row.id ?? index)),
}) as unknown as TanTable<any>

function headerClick(field?: string): void {
  if (!props.sortable || !field) {
    return
  }
  emit('sort', field)
}

const hasRows = computed(() => table.getRowModel().rows.length > 0)
</script>

<template>
  <Table>
    <TableHeader>
      <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
        <TableHead
          v-for="header in headerGroup.headers"
          :key="header.id"
          :class="[
            'select-none',
            (header.column.columnDef.meta as any)?.sortable ? 'cursor-pointer' : ''
          ]"
          @click="headerClick((header.column.columnDef.meta as any)?.sortField)"
        >
          <template v-if="!header.isPlaceholder">
            <span class="inline-flex items-center gap-1">
              <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
              <span v-if="(header.column.columnDef.meta as any)?.sortField === sortField">
                {{ sortDirection === 'asc' ? '↑' : '↓' }}
              </span>
            </span>
          </template>
        </TableHead>
      </TableRow>
    </TableHeader>

    <TableBody>
      <template v-if="hasRows">
        <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
          <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
          </TableCell>
        </TableRow>
      </template>
      <template v-else>
        <TableRow>
          <TableCell :colspan="table.getAllLeafColumns().length" class="h-24 text-center text-muted-foreground">
            {{ emptyText }}
          </TableCell>
        </TableRow>
      </template>
    </TableBody>
  </Table>
</template>
