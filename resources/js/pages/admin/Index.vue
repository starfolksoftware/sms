<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import AdminLayout from '@/layouts/admin/Layout.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import admin from '@/routes/admin'
import { Users, Shield, ListChecks } from 'lucide-vue-next'

interface Props {
  stats?: {
    users: number
    roles: number
    permissions: number
  }
}

const props = withDefaults(defineProps<Props>(), {
  stats: () => ({ users: 0, roles: 0, permissions: 0 }),
})

const adminNav: { title: string; href: string }[] = []
</script>

<template>
  <Head title="Admin" />
  <AppLayout>
    <AdminLayout title="Admin" description="Administrative tools and insights" :sidebar-nav-items="adminNav">
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-base">Users</CardTitle>
          </CardHeader>
          <CardContent class="flex items-center justify-between">
            <div class="text-3xl font-semibold">{{ props.stats.users }}</div>
            <Link :href="admin.users.index().url">
              <Button size="sm" variant="secondary">
                <Users class="mr-2 h-4 w-4" /> Manage
              </Button>
            </Link>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-base">Roles</CardTitle>
          </CardHeader>
          <CardContent class="flex items-center justify-between">
            <div class="text-3xl font-semibold">{{ props.stats.roles }}</div>
            <Link :href="admin.roles.index().url">
              <Button size="sm" variant="secondary">
                <Shield class="mr-2 h-4 w-4" /> Manage
              </Button>
            </Link>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="pb-2">
            <CardTitle class="text-base">Permissions</CardTitle>
          </CardHeader>
          <CardContent class="flex items-center justify-between">
            <div class="text-3xl font-semibold">{{ props.stats.permissions }}</div>
            <Link :href="admin.roles.index().url">
              <Button size="sm" variant="secondary">
                <ListChecks class="mr-2 h-4 w-4" /> Review
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  </AppLayout>
  
</template>
