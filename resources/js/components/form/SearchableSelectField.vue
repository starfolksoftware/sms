<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from '@/components/ui/select'
import { Input } from '@/components/ui/input'

interface Option { value: string; label: string }

const props = withDefaults(defineProps<{
  modelValue: string | null | undefined
  options: Option[]
  placeholder?: string
  disabled?: boolean
  class?: string
  filterPlaceholder?: string
}>(), {
  placeholder: 'Select an option',
  filterPlaceholder: 'Search...'
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

const search = ref('')
const internalValue = computed<string | undefined>({
  get: () => (props.modelValue == null || props.modelValue === '' ? undefined : String(props.modelValue)),
  set: (val) => emit('update:modelValue', (val == null ? null : String(val)))
})

const filtered = computed(() => {
  if (!search.value) { return props.options }
  const q = search.value.toLowerCase()
  return props.options.filter(o => o.label.toLowerCase().includes(q) || o.value.toLowerCase().includes(q))
})

watch(() => props.options, () => { search.value = '' })
</script>

<template>
  <Select v-model="internalValue" :disabled="disabled">
    <SelectTrigger :class="props.class">
      <SelectValue :placeholder="placeholder">
        <span v-if="internalValue">
          {{ props.options.find(o => o.value === internalValue)?.label || placeholder }}
        </span>
      </SelectValue>
    </SelectTrigger>
    <SelectContent>
      <div class="p-2">
        <Input :placeholder="filterPlaceholder" v-model="search" />
      </div>
      <div class="max-h-64 overflow-y-auto">
        <SelectItem v-for="o in filtered" :key="o.value" :value="o.value">{{ o.label }}</SelectItem>
        <div v-if="filtered.length === 0" class="px-3 py-2 text-sm text-muted-foreground">No results</div>
      </div>
    </SelectContent>
  </Select>
</template>
