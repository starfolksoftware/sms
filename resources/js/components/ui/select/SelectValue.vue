<script setup lang="ts">
import { computed } from 'vue'
import { SelectValue, type SelectValueProps, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<SelectValueProps & {
  class?: string
}>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectValue
    :class="cn('pointer-events-none', props.class)"
    v-bind="forwardedProps"
  >
    <slot />
  </SelectValue>
</template>