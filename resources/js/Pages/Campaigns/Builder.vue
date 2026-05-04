<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import Badge from '@/Components/ui/Badge.vue';
import CharacterCounter from '@/Components/common/CharacterCounter.vue';
import SmsPreview from '@/Components/common/SmsPreview.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Send, Save, Calendar } from 'lucide-vue-next';
import type { Tag, ListModel } from '@/types';
import type { Template } from '@/types/template';
import { TEMPLATE_VARIABLES } from '@/types/template';
import { cn, formatNumber } from '@/lib/utils';

const props = defineProps<{
  tags: Tag[];
  lists: ListModel[];
  templates: Template[];
  estimated_count: number;
  default_sender_id: string;
}>();

const currentStep = ref(1);
const totalSteps = 5;

const form = useForm({
  name: '',
  sender_id: props.default_sender_id,
  message_body: '',
  target_type: 'all',
  list_ids: [] as number[],
  tag_ids: [] as number[],
  contact_ids: [] as number[],
  template_id: null as number | null,
  notes: '',
  scheduled_at: '',
  schedule_enabled: false,
});

const steps = [
  { number: 1, label: 'Details' },
  { number: 2, label: 'Audience' },
  { number: 3, label: 'Message' },
  { number: 4, label: 'Preview' },
  { number: 5, label: 'Confirm' },
];

const targetOptions = [
  { label: 'All Contacts', value: 'all' },
  { label: 'By Lists', value: 'lists' },
  { label: 'By Tags', value: 'tags' },
];

function nextStep() {
  if (currentStep.value < totalSteps) currentStep.value++;
}

function prevStep() {
  if (currentStep.value > 1) currentStep.value--;
}

function selectTemplate(id: number) {
  const tpl = props.templates.find((t) => t.id === id);
  if (tpl) {
    form.template_id = tpl.id;
    form.message_body = tpl.body;
  }
}

function insertVariable(key: string) {
  const textarea = document.getElementById('message_body') as HTMLTextAreaElement;
  if (textarea) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    form.message_body = form.message_body.substring(0, start) + key + form.message_body.substring(end);
  } else {
    form.message_body += key;
  }
}

function submit() {
  form.post(route('campaigns.store'));
}

function saveDraft() {
  form.transform((data) => ({ ...data, status: 'draft' })).post(route('campaigns.store'));
}
</script>

<template>
  <Head title="Create Campaign" />

  <AppLayout :breadcrumbs="[
    { label: 'Campaigns', href: route('campaigns.index') },
    { label: 'Create' },
  ]">
    <PageHeader title="Create Campaign" subtitle="Set up and send your SMS campaign." />

    <!-- Progress Steps -->
    <div class="flex items-center justify-center mb-8">
      <div class="flex items-center gap-0">
        <template v-for="(step, index) in steps" :key="step.number">
          <div class="flex items-center">
            <div
              :class="
                cn(
                  'flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium transition-colors',
                  currentStep >= step.number
                    ? 'bg-primary text-white'
                    : 'bg-gray-100 text-muted'
                )
              "
            >
              {{ step.number }}
            </div>
            <span
              :class="
                cn(
                  'ml-2 text-sm font-medium hidden sm:block',
                  currentStep >= step.number ? 'text-foreground' : 'text-muted'
                )
              "
            >
              {{ step.label }}
            </span>
          </div>
          <div v-if="index < steps.length - 1" class="mx-3 h-px w-8 bg-border" />
        </template>
      </div>
    </div>

    <!-- Step 1: Campaign Details -->
    <Card v-show="currentStep === 1" class="mb-6">
      <CardHeader>
        <CardTitle>Campaign Details</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1.5">
            <Label for="name" required>Campaign Name</Label>
            <Input id="name" v-model="form.name" placeholder="e.g. January Promotion" :error="form.errors.name" />
            <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
          </div>
          <div class="space-y-1.5">
            <Label for="sender_id" required>Sender ID</Label>
            <Input id="sender_id" v-model="form.sender_id" :error="form.errors.sender_id" />
          </div>
        </div>
        <div class="space-y-1.5">
          <Label for="notes">Internal Notes</Label>
          <Textarea id="notes" v-model="form.notes" :rows="2" placeholder="Optional notes..." />
        </div>
      </CardContent>
    </Card>

    <!-- Step 2: Audience Selection -->
    <Card v-show="currentStep === 2" class="mb-6">
      <CardHeader>
        <CardTitle>Audience Selection</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <div class="space-y-1.5">
          <Label>Target Type</Label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="option in targetOptions"
              :key="option.value"
              type="button"
              @click="form.target_type = option.value"
              :class="
                cn(
                  'rounded-lg border px-4 py-2 text-sm font-medium transition-colors',
                  form.target_type === option.value
                    ? 'border-primary bg-primary-light text-primary'
                    : 'border-border bg-white text-foreground hover:bg-gray-50'
                )
              "
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <div v-if="form.target_type === 'lists'" class="space-y-1.5">
          <Label>Select Lists</Label>
          <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
            <label v-for="list in lists" :key="list.id" class="inline-flex items-center gap-1.5 cursor-pointer">
              <input type="checkbox" :value="list.id" v-model="form.list_ids" class="h-3.5 w-3.5 rounded border-border text-primary" />
              <span class="text-sm text-foreground">{{ list.name }}</span>
            </label>
          </div>
        </div>

        <div v-if="form.target_type === 'tags'" class="space-y-1.5">
          <Label>Select Tags</Label>
          <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
            <label v-for="tag in tags" :key="tag.id" class="inline-flex items-center gap-1.5 cursor-pointer">
              <input type="checkbox" :value="tag.id" v-model="form.tag_ids" class="h-3.5 w-3.5 rounded border-border text-primary" />
              <span class="text-sm text-foreground">{{ tag.name }}</span>
            </label>
          </div>
        </div>

        <div class="rounded-lg bg-gray-50 p-3">
          <p class="text-sm text-muted">
            Estimated recipients: <span class="font-semibold text-foreground">{{ formatNumber(estimated_count) }}</span>
          </p>
        </div>
      </CardContent>
    </Card>

    <!-- Step 3: Message -->
    <div v-show="currentStep === 3" class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <div class="lg:col-span-2">
        <Card>
          <CardHeader>
            <CardTitle>Message</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-1.5">
              <Label>Use Template</Label>
              <div class="flex flex-wrap gap-2">
                <button
                  v-for="tpl in templates"
                  :key="tpl.id"
                  type="button"
                  @click="selectTemplate(tpl.id)"
                  :class="
                    cn(
                      'rounded-lg border px-3 py-1.5 text-xs font-medium transition-colors',
                      form.template_id === tpl.id
                        ? 'border-primary bg-primary-light text-primary'
                        : 'border-border bg-white text-muted hover:bg-gray-50'
                    )
                  "
                >
                  {{ tpl.name }}
                </button>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label>Variables</Label>
              <div class="flex flex-wrap gap-1.5">
                <button
                  v-for="v in TEMPLATE_VARIABLES"
                  :key="v.key"
                  type="button"
                  @click="insertVariable(v.key)"
                  class="inline-flex items-center gap-1 rounded-md border border-border bg-gray-50 px-2 py-1 text-xs text-muted hover:bg-gray-100 hover:text-foreground transition-colors"
                >
                  {{ v.label }}
                </button>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="message_body" required>Message Body</Label>
              <Textarea id="message_body" v-model="form.message_body" :rows="6" placeholder="Type your message..." :error="form.errors.message_body" />
              <p v-if="form.errors.message_body" class="text-xs text-danger">{{ form.errors.message_body }}</p>
              <CharacterCounter :text="form.message_body" />
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="lg:col-span-1">
        <Card class="sticky top-6">
          <CardHeader>
            <CardTitle>Preview</CardTitle>
          </CardHeader>
          <CardContent>
            <SmsPreview :message="form.message_body" :sender-id="form.sender_id" />
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Step 4: Preview -->
    <Card v-show="currentStep === 4" class="mb-6">
      <CardHeader>
        <CardTitle>Recipient Preview</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="rounded-lg bg-gray-50 p-4 mb-4">
          <p class="text-sm text-muted">
            Your campaign will be sent to approximately
            <span class="font-semibold text-foreground">{{ formatNumber(estimated_count) }}</span>
            contacts.
          </p>
        </div>
        <p class="text-sm text-muted">
          Unsubscribed, blocked, and invalid contacts will be automatically excluded from the campaign.
        </p>
      </CardContent>
    </Card>

    <!-- Step 5: Confirmation -->
    <Card v-show="currentStep === 5" class="mb-6">
      <CardHeader>
        <CardTitle>Confirm & Send</CardTitle>
      </CardHeader>
      <CardContent class="space-y-4">
        <dl class="space-y-3 text-sm">
          <div class="flex justify-between">
            <dt class="text-muted">Campaign Name</dt>
            <dd class="font-medium text-foreground">{{ form.name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-muted">Sender ID</dt>
            <dd class="font-medium text-foreground">{{ form.sender_id }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-muted">Target</dt>
            <dd class="font-medium text-foreground capitalize">{{ form.target_type }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-muted">Estimated Recipients</dt>
            <dd class="font-medium text-foreground">{{ formatNumber(estimated_count) }}</dd>
          </div>
        </dl>

        <div class="rounded-lg border border-border bg-gray-50 p-4">
          <p class="text-xs text-muted mb-2">Message Preview:</p>
          <p class="text-sm text-foreground whitespace-pre-wrap">{{ form.message_body }}</p>
          <CharacterCounter :text="form.message_body" class="mt-2" />
        </div>

        <!-- Schedule Option -->
        <div class="space-y-3 pt-2 border-t border-border">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" v-model="form.schedule_enabled" class="h-4 w-4 rounded border-border text-primary" />
            <span class="text-sm text-foreground">Schedule for later</span>
          </label>
          <div v-if="form.schedule_enabled" class="space-y-1.5">
            <Label for="scheduled_at">Schedule Date & Time</Label>
            <Input id="scheduled_at" v-model="form.scheduled_at" type="datetime-local" />
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Navigation -->
    <div class="flex items-center justify-between">
      <Button variant="outline" @click="prevStep" :disabled="currentStep === 1">
        <ChevronLeft class="h-4 w-4" />
        Previous
      </Button>

      <div class="flex items-center gap-2">
        <Button v-if="currentStep === totalSteps" variant="outline" @click="saveDraft" :loading="form.processing">
          <Save class="h-4 w-4" />
          Save Draft
        </Button>
        <Button v-if="currentStep < totalSteps" @click="nextStep">
          Next
          <ChevronRight class="h-4 w-4" />
        </Button>
        <Button v-else @click="submit" :loading="form.processing">
          <Send class="h-4 w-4" />
          {{ form.schedule_enabled ? 'Schedule Campaign' : 'Send Campaign' }}
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
