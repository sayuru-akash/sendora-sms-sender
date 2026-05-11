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
import CharacterCounter from '@/Components/common/CharacterCounter.vue';
import SmsPreview from '@/Components/common/SmsPreview.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Braces } from 'lucide-vue-next';
import { TEMPLATE_VARIABLES, TEMPLATE_CATEGORIES } from '@/types/template';

const form = useForm({
  name: '',
  category: 'Custom',
  body: '',
  status: 'active',
});

const categoryOptions = TEMPLATE_CATEGORIES.map((c) => ({ label: c, value: c }));
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

function insertVariable(key: string) {
  const textarea = document.getElementById('template_body') as HTMLTextAreaElement;
  if (textarea) {
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    form.body = form.body.substring(0, start) + key + form.body.substring(end);
  } else {
    form.body += key;
  }
}

function submit() {
  form.post(route('templates.store'));
}
</script>

<template>
  <Head title="Create Template" />

  <AppLayout :breadcrumbs="[
    { label: 'SMS Templates', href: route('templates.index') },
    { label: 'Create' },
  ]">
    <PageHeader title="Create Template" subtitle="Create a reusable SMS template.">
      <template #actions>
        <Button variant="outline" @click="$inertia.visit(route('templates.index'))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Save Template
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div class="lg:col-span-2 space-y-6">
        <Card>
          <CardContent class="pt-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1.5">
                <Label for="name" required>Template Name</Label>
                <Input id="name" v-model="form.name" placeholder="e.g. Welcome Message" :error="form.errors.name" />
                <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
              </div>
              <div class="space-y-1.5">
                <Label>Category</Label>
                <Select v-model="form.category" :options="categoryOptions" />
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
                  <Braces class="h-3 w-3" />
                  {{ v.label }}
                </button>
              </div>
            </div>

            <div class="space-y-1.5">
              <Label for="template_body" required>Message Body</Label>
              <Textarea
                id="template_body"
                v-model="form.body"
                :rows="6"
                placeholder="Type your SMS message here..."
                :error="form.errors.body"
              />
              <p v-if="form.errors.body" class="text-xs text-danger">{{ form.errors.body }}</p>
              <CharacterCounter :text="form.body" />
            </div>

            <div class="space-y-1.5">
              <Label>Status</Label>
              <Select v-model="form.status" :options="statusOptions" />
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Preview -->
      <div class="lg:col-span-1">
        <Card class="sticky top-6">
          <CardHeader>
            <CardTitle>Preview</CardTitle>
          </CardHeader>
          <CardContent>
            <SmsPreview :message="form.body" />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
