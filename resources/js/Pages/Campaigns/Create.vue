<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import CharacterCounter from '@/Components/common/CharacterCounter.vue';
import ManualContactSelector from '@/Components/campaigns/ManualContactSelector.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import type { Tag, ListModel, SavedSegment } from '@/types';
import type { Template } from '@/types/template';


const props = defineProps<{
  lists: ListModel[];
  tags: Tag[];
  segments: SavedSegment[];
  templates: Template[];
}>();

const form = useForm({
  name: '',
  sender_id: '',
  message_body: '',
  target_type: 'all_contacts',
  list_ids: [] as number[],
  tag_ids: [] as number[],
  contact_ids: [] as number[],
  template_id: null as number | null,
  scheduled_at: '',
  notes: '',
});

const targetOptions = [
  { label: 'All Contacts', value: 'all_contacts' },
  { label: 'By List', value: 'list' },
  { label: 'By Tag', value: 'tag' },
  { label: 'By Saved Segment', value: 'saved_segment' },
  { label: 'Manual Selection', value: 'manual_selection' },
  { label: 'Advanced Filter', value: 'advanced_filter' },
];

const templateOptions = [
  { label: 'No Template', value: '' },
  ...props.templates.map((t) => ({ label: t.name, value: String(t.id) })),
];

const audienceError = computed(() => {
  if (form.target_type === 'list' && form.list_ids.length === 0) {
    return 'Select at least one list.';
  }

  if (form.target_type === 'tag' && form.tag_ids.length === 0) {
    return 'Select at least one tag.';
  }

  if (form.target_type === 'manual_selection' && form.contact_ids.length === 0) {
    return 'Select at least one contact.';
  }

  return '';
});

function selectTemplate(templateId: string | number) {
  const id = Number(templateId);
  form.template_id = id || null;
  const tpl = props.templates.find((t) => t.id === id);
  if (tpl) {
    form.message_body = tpl.body;
  }
}

function submit() {
  if (audienceError.value) {
    return;
  }

  form.transform((data) => ({
    name: data.name,
    sender_id: data.sender_id,
    message_body: data.message_body,
    target_type: data.target_type,
    target_filters: {
      list_ids: data.target_type === 'list' ? data.list_ids : undefined,
      tag_ids: data.target_type === 'tag' ? data.tag_ids : undefined,
      contact_ids: data.target_type === 'manual_selection' ? data.contact_ids : undefined,
    },
    template_id: data.template_id,
    notes: data.notes,
    scheduled_at: data.scheduled_at || undefined,
    status: data.scheduled_at ? 'scheduled' : 'draft',
  })).post(route('campaigns.store'));
}

function validationError(field: string): string | undefined {
  return (form.errors as Record<string, string | undefined>)[field];
}
</script>

<template>
  <Head title="Create Campaign" />

  <AppLayout :breadcrumbs="[
    { label: 'Campaigns', href: route('campaigns.index') },
    { label: 'Create' },
  ]">
    <PageHeader title="Create Campaign" subtitle="Set up a new SMS campaign to reach your contacts.">
      <template #actions>
        <Button variant="outline" @click="$inertia.visit(route('campaigns.index'))">Cancel</Button>
        <Button :disabled="Boolean(audienceError)" :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Save Campaign
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <!-- Details -->
        <Card>
          <CardHeader>
            <CardTitle>Campaign Details</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="campaign_name" required>Campaign Name</Label>
                <Input id="campaign_name" v-model="form.name" placeholder="e.g. January Promotion" :error="form.errors.name" />
                <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
              </div>
              <div class="space-y-1.5">
                <Label for="sender_id" required>Sender ID</Label>
                <Input id="sender_id" v-model="form.sender_id" placeholder="e.g. SENDORA" :error="form.errors.sender_id" />
                <p v-if="form.errors.sender_id" class="text-xs text-danger">{{ form.errors.sender_id }}</p>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="notes">Internal Notes</Label>
              <Textarea id="notes" v-model="form.notes" :rows="2" placeholder="Optional internal notes..." />
            </div>
          </CardContent>
        </Card>

        <!-- Audience -->
        <Card>
          <CardHeader>
            <CardTitle>Target Audience</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-1.5">
              <Label>Target Type</Label>
              <Select v-model="form.target_type" :options="targetOptions" />
              <p v-if="form.errors.target_type" class="text-xs text-danger">{{ form.errors.target_type }}</p>
            </div>

            <div v-if="form.target_type === 'list'" class="space-y-1.5">
              <Label>Select Lists</Label>
              <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
                <label v-for="list in lists" :key="list.id" class="inline-flex items-center gap-1.5 cursor-pointer">
                  <input type="checkbox" :value="list.id" v-model="form.list_ids" class="h-3.5 w-3.5 rounded border-border text-primary" />
                  <span class="text-sm text-foreground">{{ list.name }}</span>
                </label>
              </div>
              <p v-if="form.errors.list_ids || validationError('target_filters.list_ids')" class="text-xs text-danger">
                {{ form.errors.list_ids || validationError('target_filters.list_ids') }}
              </p>
              <p v-if="audienceError && form.target_type === 'list'" class="text-xs text-danger">{{ audienceError }}</p>
            </div>

            <div v-if="form.target_type === 'tag'" class="space-y-1.5">
              <Label>Select Tags</Label>
              <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
                <label v-for="tag in tags" :key="tag.id" class="inline-flex items-center gap-1.5 cursor-pointer">
                  <input type="checkbox" :value="tag.id" v-model="form.tag_ids" class="h-3.5 w-3.5 rounded border-border text-primary" />
                  <span class="text-sm text-foreground">{{ tag.name }}</span>
                </label>
              </div>
              <p v-if="form.errors.tag_ids || validationError('target_filters.tag_ids')" class="text-xs text-danger">
                {{ form.errors.tag_ids || validationError('target_filters.tag_ids') }}
              </p>
              <p v-if="audienceError && form.target_type === 'tag'" class="text-xs text-danger">{{ audienceError }}</p>
            </div>

            <div v-if="form.target_type === 'saved_segment'" class="rounded-lg bg-gray-50 p-4">
              <p class="text-sm text-muted">
                Saved segment targeting is available in the multi-step campaign builder for a better experience.
              </p>
            </div>

            <div v-if="form.target_type === 'manual_selection'" class="space-y-1.5">
              <Label>Select Contacts</Label>
              <ManualContactSelector
                v-model="form.contact_ids"
                :error="validationError('target_filters.contact_ids') || audienceError"
              />
            </div>

            <div v-if="form.target_type === 'advanced_filter'" class="rounded-lg bg-gray-50 p-4">
              <p class="text-sm text-muted">
                Advanced filter allows complex filtering. Use the multi-step campaign builder for this option.
              </p>
            </div>
          </CardContent>
        </Card>

        <!-- Message -->
        <Card>
          <CardHeader>
            <CardTitle>Message</CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-1.5">
              <Label>Use Template</Label>
              <Select
                :model-value="form.template_id ? String(form.template_id) : ''"
                :options="templateOptions"
                @update:model-value="selectTemplate"
              />
            </div>

            <div class="space-y-1.5">
              <Label for="message_body" required>Message Body</Label>
              <Textarea
                id="message_body"
                v-model="form.message_body"
                :rows="6"
                placeholder="Type your SMS message here..."
                :error="form.errors.message_body"
              />
              <p v-if="form.errors.message_body" class="text-xs text-danger">{{ form.errors.message_body }}</p>
              <CharacterCounter :text="form.message_body" />
            </div>
          </CardContent>
        </Card>

        <!-- Schedule -->
        <Card>
          <CardHeader>
            <CardTitle>Schedule</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-1.5">
              <Label for="scheduled_at">Schedule Date & Time (optional)</Label>
              <Input id="scheduled_at" v-model="form.scheduled_at" type="datetime-local" />
              <p class="text-xs text-muted">Leave empty to save as a draft for manual sending later.</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Sidebar summary -->
      <div class="lg:col-span-1">
        <Card class="sticky top-6">
          <CardHeader>
            <CardTitle>Summary</CardTitle>
          </CardHeader>
          <CardContent>
            <dl class="space-y-3 text-sm">
              <div>
                <dt class="text-muted">Name</dt>
                <dd class="font-medium text-foreground mt-0.5">{{ form.name || '—' }}</dd>
              </div>
              <div>
                <dt class="text-muted">Sender ID</dt>
                <dd class="font-medium text-foreground mt-0.5">{{ form.sender_id || '—' }}</dd>
              </div>
              <div>
                <dt class="text-muted">Target</dt>
                <dd class="font-medium text-foreground mt-0.5 capitalize">{{ form.target_type.replace(/_/g, ' ') }}</dd>
              </div>
              <div v-if="form.target_type === 'list' && form.list_ids.length">
                <dt class="text-muted">Lists</dt>
                <dd class="font-medium text-foreground mt-0.5">{{ form.list_ids.length }} selected</dd>
              </div>
              <div v-if="form.target_type === 'tag' && form.tag_ids.length">
                <dt class="text-muted">Tags</dt>
                <dd class="font-medium text-foreground mt-0.5">{{ form.tag_ids.length }} selected</dd>
              </div>
              <div v-if="form.target_type === 'manual_selection' && form.contact_ids.length">
                <dt class="text-muted">Contacts</dt>
                <dd class="font-medium text-foreground mt-0.5">{{ form.contact_ids.length }} selected</dd>
              </div>
              <div v-if="form.scheduled_at">
                <dt class="text-muted">Scheduled</dt>
                <dd class="font-medium text-foreground mt-0.5">{{ form.scheduled_at }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
