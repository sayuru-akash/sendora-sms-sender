<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import MultiSelectCombobox from '@/Components/common/MultiSelectCombobox.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import type { Tag, ListModel } from '@/types';

const props = defineProps<{
  tags: Tag[];
  lists: ListModel[];
}>();

const form = useForm({
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  company: '',
  job_title: '',
  city: '',
  district: '',
  address: '',
  source: 'manual',
  status: 'active',
  notes: '',
  tags: [] as number[],
  lists: [] as number[],
});

const sourceOptions = [
  { label: 'Manual', value: 'manual' },
  { label: 'Import', value: 'import' },
  { label: 'API', value: 'api' },
  { label: 'Web', value: 'web' },
  { label: 'Referral', value: 'referral' },
];

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

function submit() {
  form.post(route('contacts.store'));
}
</script>

<template>
  <Head title="Add Contact" />

  <AppLayout :breadcrumbs="[
    { label: 'Contacts', href: route('contacts.index') },
    { label: 'Add Contact' },
  ]">
    <PageHeader title="Add Contact" subtitle="Create a new contact record.">
      <template #actions>
        <Button variant="outline" @click="$inertia.visit(route('contacts.index'))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Save Contact
        </Button>
      </template>
    </PageHeader>

    <form @submit.prevent="submit" class="max-w-3xl space-y-6">
      <!-- Personal Info -->
      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Personal Information</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="first_name" required>First Name</Label>
              <Input id="first_name" v-model="form.first_name" :error="form.errors.first_name" />
              <p v-if="form.errors.first_name" class="text-xs text-danger">{{ form.errors.first_name }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="last_name" required>Last Name</Label>
              <Input id="last_name" v-model="form.last_name" :error="form.errors.last_name" />
              <p v-if="form.errors.last_name" class="text-xs text-danger">{{ form.errors.last_name }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="phone" required>Phone Number</Label>
              <Input id="phone" v-model="form.phone" placeholder="+94XXXXXXXXX" :error="form.errors.phone" />
              <p v-if="form.errors.phone" class="text-xs text-danger">{{ form.errors.phone }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="email">Email Address</Label>
              <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
              <p v-if="form.errors.email" class="text-xs text-danger">{{ form.errors.email }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Professional Info -->
      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Professional Information</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="company">Company</Label>
              <Input id="company" v-model="form.company" />
            </div>
            <div class="space-y-1.5">
              <Label for="job_title">Job Title</Label>
              <Input id="job_title" v-model="form.job_title" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Location -->
      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Location</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="city">City</Label>
              <Input id="city" v-model="form.city" />
            </div>
            <div class="space-y-1.5">
              <Label for="district">District</Label>
              <Input id="district" v-model="form.district" />
            </div>
          </div>
          <div class="space-y-1.5">
            <Label for="address">Address</Label>
            <Textarea id="address" v-model="form.address" :rows="2" />
          </div>
        </CardContent>
      </Card>

      <!-- Classification -->
      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Classification</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label>Source</Label>
              <Select v-model="form.source" :options="sourceOptions" />
            </div>
            <div class="space-y-1.5">
              <Label>Status</Label>
              <Select v-model="form.status" :options="statusOptions" />
            </div>
          </div>

          <div class="space-y-1.5">
            <Label>Tags</Label>
            <MultiSelectCombobox
              v-model="form.tags"
              :options="tags"
              placeholder="Choose tags"
              search-placeholder="Search tags..."
              empty-text="No matching tags"
            />
            <p v-if="form.errors.tags" class="text-xs text-danger">{{ form.errors.tags }}</p>
          </div>

          <div class="space-y-1.5">
            <Label>Lists</Label>
            <MultiSelectCombobox
              v-model="form.lists"
              :options="lists"
              placeholder="Choose lists"
              search-placeholder="Search lists..."
              empty-text="No matching lists"
            />
            <p v-if="form.errors.lists" class="text-xs text-danger">{{ form.errors.lists }}</p>
          </div>
        </CardContent>
      </Card>

      <!-- Notes -->
      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Notes</h3>
          <div class="space-y-1.5">
            <Label for="notes">Internal Notes</Label>
            <Textarea id="notes" v-model="form.notes" :rows="3" placeholder="Add any internal notes about this contact..." />
          </div>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <Button variant="outline" @click="$inertia.visit(route('contacts.index'))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Save Contact
        </Button>
      </div>
    </form>
  </AppLayout>
</template>
