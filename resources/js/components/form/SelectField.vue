<script setup lang="ts">
import { computed } from 'vue'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'

interface Option { value: string; label: string }
const props = withDefaults(defineProps<{
  modelValue: string | null | undefined
  options: Option[]
  placeholder?: string
  disabled?: boolean
  class?: string
}>(), {
  placeholder: 'Select an option'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const internalValue = computed<string | undefined>({
  get: () => (props.modelValue == null || props.modelValue === '' ? undefined : String(props.modelValue)),
  set: (val) => emit('update:modelValue', (val == null ? null : String(val)))
})

function resolveLabel(): string | undefined {
  if (internalValue.value == null || internalValue.value === '') return undefined
  return props.options.find(o => o.value === internalValue.value)?.label
}
</script>

<template>
  <Select :model-value="internalValue" @update:model-value="val => internalValue = val as any">
    <SelectTrigger :class="props.class">
      <SelectValue :placeholder="placeholder">
        <span v-if="resolveLabel()">{{ resolveLabel() }}</span>
      </SelectValue>
    </SelectTrigger>
    <SelectContent>
      <SelectItem v-for="o in options" :key="o.value" :value="o.value">{{ o.label }}</SelectItem>
    </SelectContent>
  </Select>
</template>