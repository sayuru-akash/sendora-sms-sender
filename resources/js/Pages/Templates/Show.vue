<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Separator from '@/Components/ui/Separator.vue';
import CharacterCounter from '@/Components/common/CharacterCounter.vue';
import SmsPreview from '@/Components/common/SmsPreview.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2, Copy, FileText, User, Calendar, Hash, MessageSquare } from 'lucide-vue-next';
import type { Template } from '@/types/template';
import { formatDate, formatDateTime } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  template: Template;
}>();

const { confirm } = useConfirm();

async function handleDelete() {
  const confirmed = await confirm({
    title: 'Delete Template',
    message: `Delete template "${props.template.name}"? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('templates.destroy', props.template.id));
  }
}

async function handleDuplicate() {
  const confirmed = await confirm({
    title: 'Duplicate Template',
    message: `Create a copy of "${props.template.name}"?`,
    confirmLabel: 'Duplicate',
  });
  if (confirmed) {
    router.post(route('templates.duplicate', props.template.id));
  }
}
</script>

<template>
  <Head :title="template.name" />

  <AppLayout :breadcrumbs="[
    { label: 'SMS Templates', href: route('templates.index') },
    { label: template.name },
  ]">
    <PageHeader :title="template.name" :subtitle="`Template · ${template.category}`">
      <template #actions>
        <Link :href="route('templates.index')" class="text-sm text-muted hover:text-foreground transition-colors">
          ← Back to Templates
        </Link>
        <Button variant="outline" @click="handleDuplicate">
          <Copy class="h-4 w-4" />
          Duplicate
        </Button>
        <Link :href="route('templates.edit', template.id)">
          <Button variant="outline">
            <Pencil class="h-4 w-4" />
            Edit
          </Button>
        </Link>
        <Button variant="ghost" @click="handleDelete">
          <Trash2 class="h-4 w-4 text-danger" />
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Template Details -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Meta Card -->
        <Card>
          <CardContent class="pt-6">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                  <FileText class="h-4 w-4 text-primary" />
                </div>
                <div>
                  <p class="text-xs text-muted">Category</p>
                  <p class="text-sm font-medium text-foreground">{{ template.category }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                  <Hash class="h-4 w-4 text-primary" />
                </div>
                <div>
                  <p class="text-xs text-muted">Characters</p>
                  <p class="text-sm font-medium text-foreground">{{ template.character_count }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                  <MessageSquare class="h-4 w-4 text-primary" />
                </div>
                <div>
                  <p class="text-xs text-muted">SMS Segments</p>
                  <p class="text-sm font-medium text-foreground">{{ template.sms_segments }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                  <MessageSquare class="h-4 w-4 text-primary" />
                </div>
                <div>
                  <p class="text-xs text-muted">Encoding</p>
                  <p class="text-sm font-medium text-foreground">{{ template.sms_encoding }}</p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                  <User class="h-4 w-4 text-primary" />
                </div>
                <div>
                  <p class="text-xs text-muted">Created By</p>
                  <p class="text-sm font-medium text-foreground">{{ template.created_by_name ?? '—' }}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Body Card -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Message Body</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="rounded-lg border border-border bg-gray-50 p-4">
              <p class="text-sm text-foreground whitespace-pre-wrap leading-relaxed">{{ template.body }}</p>
            </div>
            <CharacterCounter :text="template.body" />

            <div v-if="template.variables.length" class="mt-4">
              <p class="text-xs text-muted mb-2">Variables used:</p>
              <div class="flex flex-wrap gap-1.5">
                <Badge v-for="variable in template.variables" :key="variable" variant="secondary">
                  {{ variable }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Additional Info -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Details</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-muted">Status</span>
                <StatusBadge :status="template.status" />
              </div>
              <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-muted">Usage Count</span>
                <span class="text-sm font-medium text-foreground">{{ template.usage_count }} times</span>
              </div>
              <div class="flex items-center justify-between py-2 border-b border-border">
                <span class="text-sm text-muted">Created</span>
                <span class="text-sm text-foreground">{{ formatDateTime(template.created_at) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-muted">Last Updated</span>
                <span class="text-sm text-foreground">{{ formatDateTime(template.updated_at) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- SMS Preview -->
      <div class="lg:col-span-1">
        <Card class="sticky top-6">
          <CardHeader>
            <CardTitle class="text-base">SMS Preview</CardTitle>
          </CardHeader>
          <CardContent>
            <SmsPreview :message="template.body" />
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
