<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import type { Tag, ListModel } from '@/types';
import type { Contact } from '@/types/contact';

const props = defineProps<{
  contact: Contact;
  tags: Tag[];
  lists: ListModel[];
}>();

const form = useForm({
  first_name: props.contact.first_name,
  last_name: props.contact.last_name,
  phone: props.contact.phone,
  email: props.contact.email ?? '',
  company: props.contact.company ?? '',
  job_title: props.contact.job_title ?? '',
  country: '',
  district: props.contact.district ?? '',
  city: props.contact.city ?? '',
  gender: '',
  date_of_birth: '',
  source: props.contact.source ?? 'manual',
  status: props.contact.status ?? 'active',
  notes: props.contact.notes ?? '',
  tags: props.contact.tags.map((t) => t.id),
  lists: props.contact.lists.map((l) => l.id),
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

const genderOptions = [
  { label: 'Male', value: 'male' },
  { label: 'Female', value: 'female' },
  { label: 'Other', value: 'other' },
];

function submit() {
  form.put(route('contacts.update', props.contact.id));
}
</script>

<template>
  <Head :title="`Edit: ${contact.full_name}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Contacts', href: route('contacts.index') },
    { label: contact.full_name, href: route('contacts.show', contact.id) },
    { label: 'Edit' },
  ]">
    <PageHeader :title="`Edit: ${contact.full_name}`" subtitle="Update the contact details.">
      <template #actions>
        <Link :href="route('contacts.index')" class="text-sm text-muted hover:text-foreground transition-colors">
          ← Back to Contacts
        </Link>
        <Button variant="outline" @click="$inertia.visit(route('contacts.show', contact.id))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Update Contact
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
            <div class="space-y-1.5">
              <Label for="gender">Gender</Label>
              <Select id="gender" v-model="form.gender" :options="genderOptions" placeholder="Select gender" />
            </div>
            <div class="space-y-1.5">
              <Label for="date_of_birth">Date of Birth</Label>
              <Input id="date_of_birth" v-model="form.date_of_birth" type="date" />
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
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="space-y-1.5">
              <Label for="country">Country</Label>
              <Input id="country" v-model="form.country" />
            </div>
            <div class="space-y-1.5">
              <Label for="district">District</Label>
              <Input id="district" v-model="form.district" />
            </div>
            <div class="space-y-1.5">
              <Label for="city">City</Label>
              <Input id="city" v-model="form.city" />
            </div>
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

          <!-- Tags Multi-Select -->
          <div class="space-y-1.5">
            <Label>Tags</Label>
            <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
              <label
                v-for="tag in tags"
                :key="tag.id"
                class="inline-flex items-center gap-1.5 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="tag.id"
                  v-model="form.tags"
                  class="h-3.5 w-3.5 rounded border-border text-primary"
                />
                <span class="text-sm text-foreground">{{ tag.name }}</span>
              </label>
              <span v-if="!tags.length" class="text-sm text-muted">No tags available</span>
            </div>
          </div>

          <!-- Lists Multi-Select -->
          <div class="space-y-1.5">
            <Label>Lists</Label>
            <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
              <label
                v-for="list in lists"
                :key="list.id"
                class="inline-flex items-center gap-1.5 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="list.id"
                  v-model="form.lists"
                  class="h-3.5 w-3.5 rounded border-border text-primary"
                />
                <span class="text-sm text-foreground">{{ list.name }}</span>
              </label>
              <span v-if="!lists.length" class="text-sm text-muted">No lists available</span>
            </div>
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
        <Link :href="route('contacts.show', contact.id)" class="text-sm text-muted hover:text-foreground transition-colors">
          Cancel
        </Link>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Update Contact
        </Button>
      </div>
    </form>
  </AppLayout>
</template>
