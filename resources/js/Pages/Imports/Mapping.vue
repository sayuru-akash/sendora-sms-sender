<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Alert from '@/Components/ui/Alert.vue';
import MultiSelectCombobox from '@/Components/common/MultiSelectCombobox.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle, Tags } from 'lucide-vue-next';
import type { ImportColumnMapping } from '@/types/import';
import type { ListModel, Tag } from '@/types';

const props = defineProps<{
  import_id: number;
  columns: ImportColumnMapping;
  lists: ListModel[];
  tags: Tag[];
  options: {
    duplicate_handling: string;
    list_ids: number[];
    tag_ids: number[];
  };
}>();

const contactFields = [
  { label: '-- Skip this column --', value: '' },
  { label: 'First Name', value: 'first_name' },
  { label: 'Last Name', value: 'last_name' },
  { label: 'Full Name', value: 'full_name' },
  { label: 'Phone Number', value: 'phone' },
  { label: 'Email', value: 'email' },
  { label: 'Company', value: 'company' },
  { label: 'Job Title', value: 'job_title' },
  { label: 'City', value: 'city' },
  { label: 'District', value: 'district' },
  { label: 'Address', value: 'address' },
  { label: 'Notes', value: 'notes' },
];

function mappedFieldForColumn(column: string): string {
  const entry = Object.entries(props.columns.mapping).find(([, mappedColumn]) => mappedColumn === column);

  return entry?.[0] ?? '';
}

const mapping = ref<Record<string, string>>(
  Object.fromEntries(props.columns.file_columns.map((col: string) => [col, mappedFieldForColumn(col)]))
);

const phoneColumn = ref(props.columns.mapping.phone ?? '');

const phoneOptions = computed(() => [
  { label: '-- Select phone column --', value: '' },
  ...props.columns.file_columns.map((col: string) => ({ label: col, value: col })),
]);

const form = useForm({
  mapping: mapping.value,
  phone_column: '',
  duplicate_handling: props.options.duplicate_handling,
  list_ids: [...props.options.list_ids],
  tag_ids: [...props.options.tag_ids],
});
const formErrors = computed(() => form.errors as Record<string, string | undefined>);

const duplicateOptions = [
  { label: 'Skip duplicates', value: 'skip' },
  { label: 'Update existing', value: 'update' },
  { label: 'Add to selected lists only', value: 'add_to_list' },
];

function submit() {
  // Transform mapping from file_column->contact_field to contact_field->file_column
  const columnMapping: Record<string, string> = {};
  for (const [fileCol, contactField] of Object.entries(mapping.value)) {
    if (contactField) {
      columnMapping[contactField] = fileCol;
    }
  }

  if (phoneColumn.value) {
    columnMapping.phone = phoneColumn.value;
  }

  form.transform((data) => ({
    column_mapping: columnMapping,
    phone_column: phoneColumn.value,
    options: {
      duplicate_handling: data.duplicate_handling,
    },
    list_ids: data.list_ids,
    tag_ids: data.tag_ids,
  })).post(route('imports.confirm', props.import_id));
}

const isValid = computed(() => {
  return Boolean(phoneColumn.value);
});
</script>

<template>
  <Head title="Map Columns" />

  <AppLayout :breadcrumbs="[
    { label: 'Imports', href: route('imports.index') },
    { label: 'Map Columns' },
  ]">
    <PageHeader title="Map Columns" subtitle="Map your file columns to contact fields." />

    <div class="max-w-4xl space-y-6">
      <Alert variant="info">
        Match each column from your file to the corresponding contact field. The phone column is required.
      </Alert>

      <!-- Phone Column Selection -->
      <Card>
        <CardContent class="pt-6">
          <div class="space-y-1.5">
            <Label required>Phone Number Column</Label>
            <Select v-model="phoneColumn" :options="phoneOptions" />
            <p v-if="formErrors['column_mapping.phone']" class="text-xs text-danger">
              {{ formErrors['column_mapping.phone'] }}
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Tags class="h-4 w-4 text-primary" />
            Lists and Tags
          </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="space-y-1.5">
            <Label>Duplicate Handling</Label>
            <Select v-model="form.duplicate_handling" :options="duplicateOptions" />
          </div>

          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="space-y-1.5">
              <Label>Assign to Lists</Label>
              <MultiSelectCombobox
                v-model="form.list_ids"
                :options="lists"
                placeholder="Choose lists"
                search-placeholder="Search lists..."
                empty-text="No matching lists"
              />
              <p v-if="form.errors.list_ids" class="text-xs text-danger">{{ form.errors.list_ids }}</p>
            </div>

            <div class="space-y-1.5">
              <Label>Assign Tags</Label>
              <MultiSelectCombobox
                v-model="form.tag_ids"
                :options="tags"
                placeholder="Choose tags"
                search-placeholder="Search tags..."
                empty-text="No matching tags"
              />
              <p v-if="form.errors.tag_ids" class="text-xs text-danger">{{ form.errors.tag_ids }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Column Mapping -->
      <Card>
        <CardHeader>
          <CardTitle>Column Mapping</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="space-y-4">
            <div
              v-for="col in columns.file_columns"
              :key="col"
              class="flex items-center gap-4 p-3 rounded-lg border border-border hover:bg-gray-50 transition-colors"
            >
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-foreground truncate">{{ col }}</p>
                <p v-if="columns.preview_data.length" class="text-xs text-muted truncate">
                  Preview: {{ columns.preview_data[0]?.[col] ?? '—' }}
                </p>
              </div>

              <ArrowRight class="h-4 w-4 text-muted-foreground shrink-0" />

              <div class="w-48 shrink-0">
                <Select v-model="mapping[col]" :options="contactFields" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Preview Table -->
      <Card v-if="columns.preview_data.length">
        <CardHeader>
          <CardTitle>Data Preview (first 5 rows)</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full text-xs">
              <thead>
                <tr class="border-b border-border">
                  <th v-for="col in columns.file_columns" :key="col" class="px-3 py-2 text-left font-medium text-muted uppercase">
                    {{ col }}
                    <span v-if="mapping[col]" class="text-primary block normal-case">→ {{ mapping[col] }}</span>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in columns.preview_data.slice(0, 5)" :key="i" class="border-b border-border">
                  <td v-for="col in columns.file_columns" :key="col" class="px-3 py-2 text-foreground">
                    {{ row[col] ?? '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <Button variant="outline" @click="$inertia.visit(route('imports.index'))">Cancel</Button>
        <Button :loading="form.processing" :disabled="!phoneColumn" @click="submit">
          <CheckCircle class="h-4 w-4" />
          Start Import
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
