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

const form = useForm({
  name: '',
  color: '#8b5cf6',
  description: '',
  status: 'active',
});

const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Archived', value: 'archived' },
];

const listColors = [
  '#4f46e5', '#7c3aed', '#2563eb', '#0891b2', '#059669',
  '#d97706', '#dc2626', '#db2777', '#6366f1', '#14b8a6',
];

function submit() {
  form.post(route('lists.store'));
}
</script>

<template>
  <Head title="Create List" />

  <AppLayout :breadcrumbs="[
    { label: 'Lists', href: route('lists.index') },
    { label: 'Create List' },
  ]">
    <PageHeader title="Create List" subtitle="Create a new contact list for organizing your audience.">
      <template #actions>
        <Link :href="route('lists.index')" class="text-sm text-muted hover:text-foreground transition-colors">
          ← Back to Lists
        </Link>
        <Button variant="outline" @click="$inertia.visit(route('lists.index'))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Create List
        </Button>
      </template>
    </PageHeader>

    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
      <Card>
        <CardContent class="pt-6 space-y-4">
          <div class="space-y-1.5">
            <Label for="name" required>List Name</Label>
            <Input id="name" v-model="form.name" placeholder="e.g. Newsletter Subscribers" :error="form.errors.name" />
            <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
          </div>

          <div class="space-y-1.5">
            <Label>Color</Label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="color in listColors"
                :key="color"
                type="button"
                class="h-8 w-8 rounded-full border-2 transition-all"
                :style="{ backgroundColor: color, borderColor: form.color === color ? '#1a1a1a' : 'transparent' }"
                @click="form.color = color"
              />
              <div class="flex items-center gap-2 ml-2">
                <input
                  type="color"
                  v-model="form.color"
                  class="h-8 w-8 rounded cursor-pointer border border-border"
                />
                <span class="text-xs text-muted">{{ form.color }}</span>
              </div>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label for="description">Description</Label>
            <Textarea id="description" v-model="form.description" :rows="3" placeholder="Optional description for this list..." />
          </div>

          <div class="space-y-1.5">
            <Label>Status</Label>
            <Select v-model="form.status" :options="statusOptions" />
          </div>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <Link :href="route('lists.index')" class="text-sm text-muted hover:text-foreground transition-colors">
          Cancel
        </Link>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Create List
        </Button>
      </div>
    </form>
  </AppLayout>
</template>
