<script setup lang="ts">
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { toUrl, urlIsActive } from '@/lib/utils'
import { type NavItem } from '@/types'
import { Link, usePage } from '@inertiajs/vue3'

interface Props {
  showSidebar?: boolean
  title?: string
  description?: string
}

const props = withDefaults(defineProps<Props>(), {
  showSidebar: true,
  title: 'Admin',
  description: 'Manage your platform'
})

const page = usePage()

// Admin navigation items - only show if user has appropriate permissions
const sidebarNavItems = computed(() => {
  const items: NavItem[] = []
  const permissions: string[] = (page.props.auth as any)?.permissions ?? []
  
  // Users management - requires manage_users permission
  if (permissions.includes('manage_users')) {
    items.push({
      title: 'Users',
      href: { url: '/admin/users', method: 'get' }
    })
  }
  
  return items
})

const currentPath = typeof window !== 'undefined' ? window.location.pathname : ''
</script>

<template>
  <div class="px-4 py-6">
    <Heading :title="props.title" :description="props.description" />

    <div v-if="props.showSidebar && sidebarNavItems.length > 0" class="flex flex-col lg:flex-row lg:space-x-12">
      <aside class="w-full max-w-xl lg:w-48">
        <nav class="flex flex-col space-y-1 space-x-0">
          <Button
            v-for="item in sidebarNavItems"
            :key="toUrl(item.href)"
            variant="ghost"
            :class="['w-full justify-start', { 'bg-muted': urlIsActive(item.href, currentPath) }]"
            as-child
          >
            <Link :href="item.href">
              {{ item.title }}
            </Link>
          </Button>
        </nav>
      </aside>

      <Separator class="my-6 lg:hidden" />

      <div class="flex-1 lg:max-w-6xl">
        <section class="space-y-12">
          <slot />
        </section>
      </div>
    </div>
    
    <div v-else class="mt-6">
      <slot />
    </div>
  </div>
</template>