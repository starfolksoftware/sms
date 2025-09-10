<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

interface User {
  id: number
  name: string
}

interface Contact {
  id: number
  name: string
  email: string
}

interface Product {
  id: number
  name: string
}

interface Deal {
  id: number
  title: string
  description: string | null
  contact_id: number
  product_id: number | null
  owner_id: number | null
  amount: string | null
  currency: string
  stage: string
  status: string
  expected_close_date: string | null
  probability: number | null
  notes: string | null
  source: string
  source_meta: any
  contact: Contact
  owner: User | null
  product: Product | null
}

interface Props {
  deal: Deal
  enums: {
    stages: string[]
    sources: string[]
  }
}

const props = defineProps<Props>()

const form = useForm({
  title: props.deal.title,
  description: props.deal.description || '',
  contact_id: props.deal.contact_id,
  product_id: props.deal.product_id,
  owner_id: props.deal.owner_id,
  amount: props.deal.amount || '',
  currency: props.deal.currency,
  stage: props.deal.stage,
  expected_close_date: props.deal.expected_close_date || '',
  probability: props.deal.probability?.toString() || '',
  notes: props.deal.notes || '',
  source: props.deal.source,
  source_meta: props.deal.source_meta || {}
})

// Reactive data for select options
const contacts = ref<Array<{id: number, name: string, email: string}>>([])
const products = ref<Array<{id: number, name: string}>>([])
const users = ref<Array<{id: number, name: string}>>([])

const loadContacts = async () => {
  try {
    const response = await fetch('/contacts')
    const data = await response.json()
    contacts.value = data.contacts?.data || []
  } catch (error) {
    console.error('Failed to load contacts:', error)
  }
}

const loadProducts = async () => {
  try {
    const response = await fetch('/products')
    const data = await response.json()
    products.value = data.products || []
  } catch (error) {
    console.error('Failed to load products:', error)
  }
}

const loadUsers = async () => {
  try {
    const response = await fetch('/admin/users')
    const data = await response.json()
    users.value = data.users || []
  } catch (error) {
    console.error('Failed to load users:', error)
  }
}

onMounted(() => {
  loadContacts()
  loadProducts()
  loadUsers()
})

const submit = () => {
  form.put(route('deals.update', props.deal.id), {
    onSuccess: () => {
      toast.success('Deal updated successfully')
    },
    onError: () => {
      toast.error('Please fix the errors below')
    }
  })
}

const cancel = () => {
  router.visit(route('deals.show', props.deal.id))
}

const canEditStage = props.deal.status === 'open'
</script>

<template>
  <Head :title="`Edit ${deal.title}`" />

  <AppLayout>
    <div class="px-4 sm:px-6 lg:px-8">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-2xl font-semibold text-gray-900">Edit Deal</h1>
          <p class="mt-2 text-sm text-gray-700">
            Update deal information
          </p>
        </div>
      </div>

      <form @submit.prevent="submit" class="mt-8 space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Deal Information</CardTitle>
          </CardHeader>
          <CardContent class="space-y-6">
            <!-- Title -->
            <div>
              <Label for="title">Title *</Label>
              <Input
                id="title"
                v-model="form.title"
                type="text"
                :class="form.errors.title ? 'border-red-300' : ''"
                placeholder="Enter deal title"
                required
              />
              <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">
                {{ form.errors.title }}
              </div>
            </div>

            <!-- Description -->
            <div>
              <Label for="description">Description</Label>
              <Textarea
                id="description"
                v-model="form.description"
                :class="form.errors.description ? 'border-red-300' : ''"
                placeholder="Enter deal description"
                rows="3"
              />
              <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                {{ form.errors.description }}
              </div>
            </div>

            <!-- Contact -->
            <div>
              <Label for="contact_id">Contact *</Label>
              <Select v-model="form.contact_id">
                <SelectTrigger :class="form.errors.contact_id ? 'border-red-300' : ''">
                  <SelectValue placeholder="Select a contact" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="contact in contacts" 
                    :key="contact.id" 
                    :value="contact.id"
                  >
                    {{ contact.name }} ({{ contact.email }})
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.contact_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.contact_id }}
              </div>
            </div>

            <!-- Product (Optional) -->
            <div>
              <Label for="product_id">Product</Label>
              <Select v-model="form.product_id">
                <SelectTrigger>
                  <SelectValue placeholder="Select a product (optional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">No product</SelectItem>
                  <SelectItem 
                    v-for="product in products" 
                    :key="product.id" 
                    :value="product.id"
                  >
                    {{ product.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.product_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.product_id }}
              </div>
            </div>

            <!-- Owner -->
            <div>
              <Label for="owner_id">Owner</Label>
              <Select v-model="form.owner_id">
                <SelectTrigger>
                  <SelectValue placeholder="Assign to user (optional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem :value="null">Unassigned</SelectItem>
                  <SelectItem 
                    v-for="user in users" 
                    :key="user.id" 
                    :value="user.id"
                  >
                    {{ user.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.owner_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.owner_id }}
              </div>
            </div>

            <!-- Amount and Currency -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="amount">Amount</Label>
                <Input
                  id="amount"
                  v-model="form.amount"
                  type="number"
                  step="0.01"
                  min="0"
                  :class="form.errors.amount ? 'border-red-300' : ''"
                  placeholder="0.00"
                />
                <div v-if="form.errors.amount" class="mt-1 text-sm text-red-600">
                  {{ form.errors.amount }}
                </div>
              </div>
              <div>
                <Label for="currency">Currency</Label>
                <Input
                  id="currency"
                  v-model="form.currency"
                  type="text"
                  maxlength="3"
                  :class="form.errors.currency ? 'border-red-300' : ''"
                  placeholder="USD"
                />
                <div v-if="form.errors.currency" class="mt-1 text-sm text-red-600">
                  {{ form.errors.currency }}
                </div>
              </div>
            </div>

            <!-- Stage -->
            <div>
              <Label for="stage">Stage *</Label>
              <Select v-model="form.stage" :disabled="!canEditStage">
                <SelectTrigger :class="form.errors.stage ? 'border-red-300' : ''">
                  <SelectValue placeholder="Select deal stage" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="stage in enums.stages" 
                    :key="stage" 
                    :value="stage"
                  >
                    {{ stage }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.stage" class="mt-1 text-sm text-red-600">
                {{ form.errors.stage }}
              </div>
              <div v-if="!canEditStage" class="mt-1 text-sm text-gray-500">
                Stage cannot be changed for closed deals
              </div>
            </div>

            <!-- Expected Close Date and Probability -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <Label for="expected_close_date">Expected Close Date</Label>
                <Input
                  id="expected_close_date"
                  v-model="form.expected_close_date"
                  type="date"
                  :class="form.errors.expected_close_date ? 'border-red-300' : ''"
                />
                <div v-if="form.errors.expected_close_date" class="mt-1 text-sm text-red-600">
                  {{ form.errors.expected_close_date }}
                </div>
              </div>
              <div>
                <Label for="probability">Probability (%)</Label>
                <Input
                  id="probability"
                  v-model="form.probability"
                  type="number"
                  min="0"
                  max="100"
                  :class="form.errors.probability ? 'border-red-300' : ''"
                  placeholder="0-100"
                />
                <div v-if="form.errors.probability" class="mt-1 text-sm text-red-600">
                  {{ form.errors.probability }}
                </div>
              </div>
            </div>

            <!-- Source -->
            <div>
              <Label for="source">Source *</Label>
              <Select v-model="form.source">
                <SelectTrigger :class="form.errors.source ? 'border-red-300' : ''">
                  <SelectValue placeholder="Select deal source" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem 
                    v-for="source in enums.sources" 
                    :key="source" 
                    :value="source"
                  >
                    {{ source.replace('_', ' ') }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <div v-if="form.errors.source" class="mt-1 text-sm text-red-600">
                {{ form.errors.source }}
              </div>
            </div>

            <!-- Notes -->
            <div>
              <Label for="notes">Notes</Label>
              <Textarea
                id="notes"
                v-model="form.notes"
                :class="form.errors.notes ? 'border-red-300' : ''"
                placeholder="Additional notes about the deal"
                rows="3"
              />
              <div v-if="form.errors.notes" class="mt-1 text-sm text-red-600">
                {{ form.errors.notes }}
              </div>
            </div>
          </CardContent>
        </Card>

        <div class="flex justify-end space-x-3">
          <Button
            type="button"
            variant="outline"
            @click="cancel"
            :disabled="form.processing"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing"
            class="bg-blue-600 hover:bg-blue-700"
          >
            <span v-if="form.processing">Updating...</span>
            <span v-else>Update Deal</span>
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>