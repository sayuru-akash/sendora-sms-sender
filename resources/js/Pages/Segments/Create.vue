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
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';

const form = useForm({
  name: '',
  description: '',
  filters: {
    status: '',
    source: '',
    district: '',
    city: '',
    gender: '',
    date_from: '',
    date_to: '',
    tag_ids: [] as number[],
    list_ids: [] as number[],
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
  form.post(route('segments.store'));
}
</script>

<template>
  <Head title="Create Segment" />

  <AppLayout :breadcrumbs="[
    { label: 'Segments', href: route('segments.index') },
    { label: 'Create' },
  ]">
    <PageHeader title="Create Segment" subtitle="Define filter criteria to create a reusable contact segment.">
      <template #actions>
        <Button variant="outline" @click="$inertia.visit(route('segments.index'))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Save Segment
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
            <Input id="segment_name" v-model="form.name" placeholder="e.g. Colombo Active Customers" :error="form.errors.name" />
            <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
          </div>
          <div class="space-y-1.5">
            <Label for="segment_desc">Description</Label>
            <Textarea id="segment_desc" v-model="form.description" :rows="2" placeholder="Describe what this segment captures..." />
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
