<script setup lang="ts">
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import AppLayout from '@/layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'

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
  amount: string | null
  currency: string
  stage: string
  status: string
  expected_close_date: string | null
  probability: number | null
  lost_reason: string | null
  won_amount: string | null
  closed_at: string | null
  source: string
  source_meta: any
  notes: string | null
  contact: Contact
  owner: User | null
  product: Product | null
  creator: User
  created_at: string
  updated_at: string
}

interface Props {
  deal: Deal
}

const props = defineProps<Props>()

const showWinDialog = ref(false)
const showLoseDialog = ref(false)

const winForm = useForm({
  won_amount: props.deal.amount || ''
})

const loseForm = useForm({
  lost_reason: ''
})

const stageColors: Record<string, string> = {
  new: 'bg-blue-100 text-blue-800',
  qualified: 'bg-green-100 text-green-800',
  proposal: 'bg-yellow-100 text-yellow-800',
  negotiation: 'bg-orange-100 text-orange-800',
  closed: 'bg-gray-100 text-gray-800'
}

const statusColors: Record<string, string> = {
  open: 'bg-blue-100 text-blue-800',
  won: 'bg-green-100 text-green-800',
  lost: 'bg-red-100 text-red-800'
}

const markAsWon = () => {
  winForm.post(route('deals.win', props.deal.id), {
    onSuccess: () => {
      toast.success('Deal marked as won!')
      showWinDialog.value = false
    },
    onError: () => {
      toast.error('Failed to mark deal as won')
    }
  })
}

const markAsLost = () => {
  loseForm.post(route('deals.lose', props.deal.id), {
    onSuccess: () => {
      toast.success('Deal marked as lost')
      showLoseDialog.value = false
    },
    onError: () => {
      toast.error('Failed to mark deal as lost')
    }
  })
}

const deleteDeal = () => {
  if (confirm('Are you sure you want to archive this deal?')) {
    router.delete(route('deals.destroy', props.deal.id), {
      onSuccess: () => {
        toast.success('Deal archived successfully')
      },
      onError: () => {
        toast.error('Failed to archive deal')
      }
    })
  }
}

const formatDate = (dateString: string | null) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

const formatAmount = (amount: string | null, currency: string) => {
  if (!amount) return 'N/A'
  return `${currency} ${Number(amount).toLocaleString()}`
}
</script>

<template>
  <Head :title="deal.title" />

  <AppLayout>
    <div class="px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
          <h1 class="text-2xl font-semibold text-gray-900">{{ deal.title }}</h1>
          <div class="mt-2 flex items-center space-x-4">
            <Badge :class="stageColors[deal.stage] || 'bg-gray-100 text-gray-800'">
              {{ deal.stage }}
            </Badge>
            <Badge :class="statusColors[deal.status] || 'bg-gray-100 text-gray-800'">
              {{ deal.status }}
            </Badge>
          </div>
        </div>
        <div class="mt-4 sm:mt-0 sm:flex-none flex space-x-2">
          <Button
            @click="router.visit(route('deals.edit', deal.id))"
            variant="outline"
          >
            Edit
          </Button>
          
          <Dialog v-model:open="showWinDialog" v-if="deal.status === 'open'">
            <DialogTrigger asChild>
              <Button class="bg-green-600 hover:bg-green-700">
                Mark as Won
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Mark Deal as Won</DialogTitle>
                <DialogDescription>
                  Confirm the won amount for this deal.
                </DialogDescription>
              </DialogHeader>
              <form @submit.prevent="markAsWon" class="space-y-4">
                <div>
                  <Label for="won_amount">Won Amount</Label>
                  <Input
                    id="won_amount"
                    v-model="winForm.won_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    :placeholder="deal.amount || '0.00'"
                  />
                  <div v-if="winForm.errors.won_amount" class="mt-1 text-sm text-red-600">
                    {{ winForm.errors.won_amount }}
                  </div>
                </div>
                <div class="flex justify-end space-x-2">
                  <Button type="button" variant="outline" @click="showWinDialog = false">
                    Cancel
                  </Button>
                  <Button type="submit" :disabled="winForm.processing" class="bg-green-600 hover:bg-green-700">
                    Mark as Won
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>

          <Dialog v-model:open="showLoseDialog" v-if="deal.status === 'open'">
            <DialogTrigger asChild>
              <Button variant="outline" class="border-red-300 text-red-700 hover:bg-red-50">
                Mark as Lost
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Mark Deal as Lost</DialogTitle>
                <DialogDescription>
                  Please provide a reason for losing this deal.
                </DialogDescription>
              </DialogHeader>
              <form @submit.prevent="markAsLost" class="space-y-4">
                <div>
                  <Label for="lost_reason">Reason for Loss *</Label>
                  <Textarea
                    id="lost_reason"
                    v-model="loseForm.lost_reason"
                    placeholder="Enter reason for losing the deal..."
                    required
                  />
                  <div v-if="loseForm.errors.lost_reason" class="mt-1 text-sm text-red-600">
                    {{ loseForm.errors.lost_reason }}
                  </div>
                </div>
                <div class="flex justify-end space-x-2">
                  <Button type="button" variant="outline" @click="showLoseDialog = false">
                    Cancel
                  </Button>
                  <Button type="submit" :disabled="loseForm.processing" class="bg-red-600 hover:bg-red-700">
                    Mark as Lost
                  </Button>
                </div>
              </form>
            </DialogContent>
          </Dialog>

          <Button
            @click="deleteDeal"
            variant="outline"
            class="border-red-300 text-red-700 hover:bg-red-50"
          >
            Archive
          </Button>
        </div>
      </div>

      <!-- Deal Details -->
      <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Information -->
        <div class="lg:col-span-2 space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Deal Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="deal.description">
                <h4 class="text-sm font-medium text-gray-500">Description</h4>
                <p class="mt-1 text-sm text-gray-900">{{ deal.description }}</p>
              </div>
              
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Amount</h4>
                  <p class="mt-1 text-sm text-gray-900">{{ formatAmount(deal.amount, deal.currency) }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Expected Close</h4>
                  <p class="mt-1 text-sm text-gray-900">{{ formatDate(deal.expected_close_date) }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Probability</h4>
                  <p class="mt-1 text-sm text-gray-900">{{ deal.probability ? deal.probability + '%' : 'N/A' }}</p>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-500">Source</h4>
                  <p class="mt-1 text-sm text-gray-900">{{ deal.source.replace('_', ' ') }}</p>
                </div>
              </div>

              <div v-if="deal.won_amount && deal.status === 'won'">
                <h4 class="text-sm font-medium text-gray-500">Won Amount</h4>
                <p class="mt-1 text-sm text-green-600 font-semibold">{{ formatAmount(deal.won_amount, deal.currency) }}</p>
              </div>

              <div v-if="deal.lost_reason && deal.status === 'lost'">
                <h4 class="text-sm font-medium text-gray-500">Lost Reason</h4>
                <p class="mt-1 text-sm text-red-600">{{ deal.lost_reason }}</p>
              </div>

              <div v-if="deal.closed_at">
                <h4 class="text-sm font-medium text-gray-500">Closed Date</h4>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(deal.closed_at) }}</p>
              </div>

              <div v-if="deal.notes">
                <h4 class="text-sm font-medium text-gray-500">Notes</h4>
                <p class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ deal.notes }}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Related Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div>
                <h4 class="text-sm font-medium text-gray-500">Contact</h4>
                <div class="mt-1">
                  <a 
                    :href="route('contacts.show', deal.contact.id)"
                    class="text-sm text-blue-600 hover:text-blue-800"
                  >
                    {{ deal.contact.name }}
                  </a>
                  <p class="text-sm text-gray-500">{{ deal.contact.email }}</p>
                </div>
              </div>

              <div v-if="deal.product">
                <h4 class="text-sm font-medium text-gray-500">Product</h4>
                <p class="mt-1 text-sm text-gray-900">{{ deal.product.name }}</p>
              </div>

              <div>
                <h4 class="text-sm font-medium text-gray-500">Owner</h4>
                <p class="mt-1 text-sm text-gray-900">{{ deal.owner?.name || 'Unassigned' }}</p>
              </div>

              <div>
                <h4 class="text-sm font-medium text-gray-500">Created by</h4>
                <p class="mt-1 text-sm text-gray-900">{{ deal.creator.name }}</p>
              </div>

              <div>
                <h4 class="text-sm font-medium text-gray-500">Created</h4>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(deal.created_at) }}</p>
              </div>

              <div>
                <h4 class="text-sm font-medium text-gray-500">Last Updated</h4>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(deal.updated_at) }}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>