<script setup lang="ts">
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
import { Head, useForm, router } from '@inertiajs/vue3';
import { Save, Trash2 } from 'lucide-vue-next';
import type { SavedSegment } from '@/types';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  segment: SavedSegment;
}>();

const { confirm } = useConfirm();

const form = useForm({
  name: props.segment.name,
  description: props.segment.description ?? '',
  filters: {
    status: props.segment.filters?.status ?? '',
    source: props.segment.filters?.source ?? '',
    district: props.segment.filters?.district ?? '',
    city: props.segment.filters?.city ?? '',
    gender: props.segment.filters?.gender ?? '',
    date_from: props.segment.filters?.date_from ?? '',
    date_to: props.segment.filters?.date_to ?? '',
    tag_ids: props.segment.filters?.tag_ids ?? ([] as number[]),
    list_ids: props.segment.filters?.list_ids ?? ([] as number[]),
  },
});

const statusOptions = [
  { label: 'Any', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Unsubscribed', value: 'unsubscribed' },
  { label: 'Blocked', value: 'blocked' },
  { label: 'Invalid', value: 'invalid' },
  { label: 'Bounced', value: 'bounced' },
];

const genderOptions = [
  { label: 'Any', value: '' },
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
];

function submit() {
  form.put(route('segments.update', props.segment.id));
}

async function handleDelete() {
  const confirmed = await confirm({
    title: 'Delete Segment',
    message: `Delete saved segment "${props.segment.name}"? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('segments.destroy', props.segment.id));
  }
}
</script>

<template>
  <Head :title="`Edit: ${segment.name}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Segments', href: route('segments.index') },
    { label: segment.name, href: route('segments.show', segment.id) },
    { label: 'Edit' },
  ]">
    <PageHeader :title="`Edit: ${segment.name}`">
      <template #actions>
        <Button variant="ghost" @click="handleDelete">
          <Trash2 class="h-4 w-4 text-danger" />
        </Button>
        <Button variant="outline" @click="$inertia.visit(route('segments.show', segment.id))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Update Segment
        </Button>
      </template>
    </PageHeader>

    <div class="max-w-3xl space-y-6">
      <!-- Details -->
      <Card>
        <CardHeader>
          <CardTitle>Segment Details</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label for="segment_name" required>Name</Label>
            <Input id="segment_name" v-model="form.name" :error="form.errors.name" />
            <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
          </div>
          <div class="space-y-1.5">
            <Label for="segment_desc">Description</Label>
            <Textarea id="segment_desc" v-model="form.description" :rows="2" />
          </div>
        </CardContent>
      </Card>

      <!-- Filter Criteria -->
      <Card>
        <CardHeader>
          <CardTitle>Filter Criteria</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label>Status</Label>
              <Select v-model="form.filters.status" :options="statusOptions" />
            </div>
            <div class="space-y-1.5">
              <Label>Source</Label>
              <Input v-model="form.filters.source" placeholder="e.g. import, api" />
            </div>
            <div class="space-y-1.5">
              <Label>District</Label>
              <Input v-model="form.filters.district" placeholder="Filter by district" />
            </div>
            <div class="space-y-1.5">
              <Label>City</Label>
              <Input v-model="form.filters.city" placeholder="Filter by city" />
            </div>
            <div class="space-y-1.5">
              <Label>Gender</Label>
              <Select v-model="form.filters.gender" :options="genderOptions" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="date_from">Created From</Label>
              <Input id="date_from" v-model="form.filters.date_from" type="date" />
            </div>
            <div class="space-y-1.5">
              <Label for="date_to">Created To</Label>
              <Input id="date_to" v-model="form.filters.date_to" type="date" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
