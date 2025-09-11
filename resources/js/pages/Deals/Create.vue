<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

interface Props {
  prefill?: { contact_id?: number }
  enums: {
    stages: string[]
    statuses: string[]
    sources: string[]
    currencyDefault: string
  }
}

const props = defineProps<Props>()

const form = useForm({
  title: '',
  description: '',
  contact_id: props.prefill?.contact_id || null,
  product_id: null,
  owner_id: null,
  amount: '',
  currency: props.enums.currencyDefault,
  stage: 'new',
  status: 'open',
  expected_close_date: '',
  probability: '',
  notes: '',
  source: 'manual',
  source_meta: {}
})

const contacts = ref<Array<{ id: number; name: string; email: string }>>([])
const products = ref<Array<{ id: number; name: string }>>([])
const users = ref<Array<{ id: number; name: string }>>([])

const loadContacts = async () => {
  try {
    const res = await fetch('/contacts')
    const data = await res.json()
    contacts.value = data.contacts?.data || []
  } catch {}
}

const loadProducts = async () => {
  try {
    const res = await fetch('/products')
    const data = await res.json()
    products.value = data.products || []
  } catch {}
}

const loadUsers = async () => {
  try {
    const res = await fetch('/admin/users')
    const data = await res.json()
    users.value = data.users || []
  } catch {}
}

onMounted(() => {
  loadContacts()
  loadProducts()
  loadUsers()
})

const submit = () => {
  form.post('/crm/deals', {
    onSuccess: () => toast.success('Deal created successfully'),
    onError: () => toast.error('Please fix the errors below'),
  })
}
</script>

<template>
  <Head title="Create Deal" />

  <AppLayout :breadcrumbs="[{ title: 'Deals', href: '/crm/deals' }, { title: 'New', href: '/crm/deals/new' }]">
    <template #header>
      <div class="mt-6 w-full px-6 lg:px-8">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Create Deal</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Add a new deal to your pipeline.</p>
      </div>
    </template>

    <div class="py-12 px-0">
      <div class="w-full sm:px-6 lg:px-8 max-w-none">
        <form @submit.prevent="submit" class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Deal Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-6">
              <div>
                <Label for="title">Title *</Label>
                <Input id="title" v-model="form.title" type="text" class="mt-1" :class="form.errors.title ? 'border-red-300' : ''" placeholder="Enter deal title" required />
                <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</div>
              </div>

              <div>
                <Label for="description">Description</Label>
                <Textarea id="description" v-model="form.description" class="mt-1" :class="form.errors.description ? 'border-red-300' : ''" placeholder="Enter deal description" :rows="3" />
                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
              </div>

              <div>
                <Label for="contact_id">Contact *</Label>
                <Select v-model="form.contact_id">
                  <SelectTrigger class="mt-1" :class="form.errors.contact_id ? 'border-red-300' : ''">
                    <SelectValue placeholder="Select a contact" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="contact in contacts" :key="contact.id" :value="contact.id">
                      {{ contact.name }} ({{ contact.email }})
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.contact_id" class="mt-1 text-sm text-red-600">{{ form.errors.contact_id }}</div>
              </div>

              <div>
                <Label for="product_id">Product</Label>
                <Select v-model="form.product_id">
                  <SelectTrigger class="mt-1">
                    <SelectValue placeholder="Select a product (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem :value="null">No product</SelectItem>
                    <SelectItem v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.product_id" class="mt-1 text-sm text-red-600">{{ form.errors.product_id }}</div>
              </div>

              <div>
                <Label for="owner_id">Owner</Label>
                <Select v-model="form.owner_id">
                  <SelectTrigger class="mt-1">
                    <SelectValue placeholder="Assign to user (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem :value="null">Unassigned</SelectItem>
                    <SelectItem v-for="user in users" :key="user.id" :value="user.id">
                      {{ user.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.owner_id" class="mt-1 text-sm text-red-600">{{ form.errors.owner_id }}</div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label for="amount">Amount</Label>
                  <Input id="amount" v-model="form.amount" type="number" step="0.01" min="0" class="mt-1" :class="form.errors.amount ? 'border-red-300' : ''" placeholder="0.00" />
                  <div v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</div>
                </div>
                <div>
                  <Label for="currency">Currency</Label>
                  <Input id="currency" v-model="form.currency" type="text" maxlength="3" class="mt-1" :class="form.errors.currency ? 'border-red-300' : ''" placeholder="USD" />
                  <div v-if="form.errors.currency" class="mt-1 text-sm text-red-600">{{ form.errors.currency }}</div>
                </div>
              </div>

              <div>
                <Label for="stage">Stage *</Label>
                <Select v-model="form.stage">
                  <SelectTrigger class="mt-1" :class="form.errors.stage ? 'border-red-300' : ''">
                    <SelectValue placeholder="Select deal stage" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="stage in enums.stages" :key="stage" :value="stage">
                      {{ stage }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.stage" class="mt-1 text-sm text-red-600">{{ form.errors.stage }}</div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <Label for="expected_close_date">Expected Close Date</Label>
                  <Input id="expected_close_date" v-model="form.expected_close_date" type="date" class="mt-1" :class="form.errors.expected_close_date ? 'border-red-300' : ''" />
                  <div v-if="form.errors.expected_close_date" class="mt-1 text-sm text-red-600">{{ form.errors.expected_close_date }}</div>
                </div>
                <div>
                  <Label for="probability">Probability (%)</Label>
                  <Input id="probability" v-model="form.probability" type="number" min="0" max="100" class="mt-1" :class="form.errors.probability ? 'border-red-300' : ''" placeholder="0-100" />
                  <div v-if="form.errors.probability" class="mt-1 text-sm text-red-600">{{ form.errors.probability }}</div>
                </div>
              </div>

              <div>
                <Label for="source">Source *</Label>
                <Select v-model="form.source">
                  <SelectTrigger class="mt-1" :class="form.errors.source ? 'border-red-300' : ''">
                    <SelectValue placeholder="Select deal source" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="source in enums.sources" :key="source" :value="source">
                      {{ source.replace('_', ' ') }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <div v-if="form.errors.source" class="mt-1 text-sm text-red-600">{{ form.errors.source }}</div>
              </div>

              <div>
                <Label for="notes">Notes</Label>
                <Textarea id="notes" v-model="form.notes" class="mt-1" :class="form.errors.notes ? 'border-red-300' : ''" placeholder="Additional notes about the deal" :rows="3" />
                <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">{{ form.errors.notes }}</div>
              </div>
            </CardContent>
          </Card>

          <div class="flex items-center justify-end gap-3 pt-6">
            <Button type="button" variant="outline" @click="$inertia.visit('/crm/deals')" :disabled="form.processing">Cancel</Button>
            <Button type="submit" :disabled="form.processing">
              <span v-if="form.processing">Creating...</span>
              <span v-else>Create Deal</span>
            </Button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>