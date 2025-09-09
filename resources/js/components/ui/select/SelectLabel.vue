<script setup lang="ts">
import { computed } from 'vue'
import { SelectLabel, type SelectLabelProps, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<SelectLabelProps & {
  class?: string
}>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <SelectLabel
    :class="cn('px-2 py-1.5 text-sm font-semibold', props.class)"
    v-bind="forwardedProps"
  >
    <slot />
  </SelectLabel>
</template>