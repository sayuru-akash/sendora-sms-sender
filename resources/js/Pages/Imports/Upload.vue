<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Alert from '@/Components/ui/Alert.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Upload, FileSpreadsheet, X, Check } from 'lucide-vue-next';
import type { ListModel, Tag } from '@/types';
import { cn } from '@/lib/utils';

defineProps<{
  lists: ListModel[];
  tags: Tag[];
}>();

const form = useForm({
  file: null as File | null,
  duplicate_handling: 'skip' as string,
  list_ids: [] as number[],
  tag_ids: [] as number[],
});

const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const fileName = ref('');
const fileError = ref('');

const duplicateOptions = [
  { label: 'Skip duplicates', value: 'skip' },
  { label: 'Update existing', value: 'update' },
  { label: 'Add anyway', value: 'add' },
];

function handleDragOver(e: DragEvent) {
  e.preventDefault();
  isDragging.value = true;
}

function handleDragLeave() {
  isDragging.value = false;
}

function handleDrop(e: DragEvent) {
  e.preventDefault();
  isDragging.value = false;
  const file = e.dataTransfer?.files[0];
  if (file) handleFile(file);
}

function handleFileInput(e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (file) handleFile(file);
}

function handleFile(file: File) {
  fileError.value = '';
  const allowedTypes = [
    'text/csv',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
  ];
  const allowedExtensions = ['.csv', '.xlsx', '.xls'];

  const ext = '.' + file.name.split('.').pop()?.toLowerCase();

  if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(ext)) {
    fileError.value = 'Please upload a CSV or XLSX file.';
    return;
  }

  if (file.size > 10 * 1024 * 1024) {
    fileError.value = 'File size must be less than 10MB.';
    return;
  }

  form.file = file;
  fileName.value = file.name;
}

function removeFile() {
  form.file = null;
  fileName.value = '';
  if (fileInput.value) fileInput.value.value = '';
}

function submit() {
  if (!form.file) {
    fileError.value = 'Please select a file to upload.';
    return;
  }
  form.post(route('imports.upload'));
}
</script>

<template>
  <Head title="Upload Import File" />

  <AppLayout :breadcrumbs="[
    { label: 'Imports', href: route('imports.index') },
    { label: 'Upload' },
  ]">
    <PageHeader title="Upload Import File" subtitle="Upload a CSV or XLSX file to import contacts." />

    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
      <!-- Drop Zone -->
      <Card>
        <CardContent class="pt-6">
          <div
            @dragover="handleDragOver"
            @dragleave="handleDragLeave"
            @drop="handleDrop"
            @click="fileInput?.click()"
            :class="
              cn(
                'flex flex-col items-center justify-center rounded-xl border-2 border-dashed p-12 cursor-pointer transition-all duration-200',
                isDragging ? 'border-primary bg-primary-light' : 'border-border hover:border-muted-foreground hover:bg-gray-50',
                fileError && 'border-danger'
              )
            "
          >
            <input
              ref="fileInput"
              type="file"
              accept=".csv,.xlsx,.xls"
              class="hidden"
              @change="handleFileInput"
            />

            <div v-if="!fileName" class="text-center">
              <Upload class="mx-auto h-10 w-10 text-muted-foreground mb-3" />
              <p class="text-sm font-medium text-foreground">
                Drop your file here, or <span class="text-primary">browse</span>
              </p>
              <p class="mt-1 text-xs text-muted">Supports CSV and XLSX files up to 10MB</p>
            </div>

            <div v-else class="flex items-center gap-3">
              <FileSpreadsheet class="h-8 w-8 text-primary" />
              <div>
                <p class="text-sm font-medium text-foreground">{{ fileName }}</p>
                <p class="text-xs text-muted">{{ form.file ? (form.file.size / 1024).toFixed(1) : 0 }} KB</p>
              </div>
              <button
                type="button"
                @click.stop="removeFile"
                class="ml-2 rounded-full p-1 hover:bg-gray-100 transition-colors"
              >
                <X class="h-4 w-4 text-muted-foreground" />
              </button>
            </div>
          </div>

          <p v-if="fileError" class="mt-2 text-xs text-danger">{{ fileError }}</p>
          <p v-if="form.errors.file" class="mt-2 text-xs text-danger">{{ form.errors.file }}</p>
        </CardContent>
      </Card>

      <!-- Options -->
      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground">Import Options</h3>

          <div class="space-y-1.5">
            <Label>Duplicate Handling</Label>
            <Select v-model="form.duplicate_handling" :options="duplicateOptions" />
          </div>

          <div class="space-y-1.5">
            <Label>Assign to Lists</Label>
            <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
              <label
                v-for="list in lists"
                :key="list.id"
                class="inline-flex items-center gap-1.5 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="list.id"
                  v-model="form.list_ids"
                  class="h-3.5 w-3.5 rounded border-border text-primary"
                />
                <span class="text-sm text-foreground">{{ list.name }}</span>
              </label>
              <span v-if="!lists.length" class="text-sm text-muted">No lists available</span>
            </div>
          </div>

          <div class="space-y-1.5">
            <Label>Assign Tags</Label>
            <div class="flex flex-wrap gap-2 p-3 rounded-lg border border-border bg-white min-h-[44px]">
              <label
                v-for="tag in tags"
                :key="tag.id"
                class="inline-flex items-center gap-1.5 cursor-pointer"
              >
                <input
                  type="checkbox"
                  :value="tag.id"
                  v-model="form.tag_ids"
                  class="h-3.5 w-3.5 rounded border-border text-primary"
                />
                <span class="text-sm text-foreground">{{ tag.name }}</span>
              </label>
              <span v-if="!tags.length" class="text-sm text-muted">No tags available</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <Button variant="outline" @click="$inertia.visit(route('imports.index'))">Cancel</Button>
        <Button :loading="form.processing" :disabled="!form.file" @click="submit">
          <Upload class="h-4 w-4" />
          Upload & Continue
        </Button>
      </div>
    </form>
  </AppLayout>
</template>
