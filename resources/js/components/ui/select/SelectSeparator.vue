<script setup lang="ts">
import { computed } from 'vue'
import { SelectSeparator, type SelectSeparatorProps, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<SelectSeparatorProps & {
  class?: string
}>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectSeparator
    :class="cn('-mx-1 my-1 h-px bg-muted', props.class)"
    v-bind="forwardedProps"
  />
</template>