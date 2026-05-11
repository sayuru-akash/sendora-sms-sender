<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, Plus, MoreHorizontal, Pencil, Trash2, Copy, Send } from 'lucide-vue-next';
import type { Template } from '@/types/template';
import { truncate, formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  templates: Template[];
}>();

const { confirm } = useConfirm();
const search = ref('');

const filtered = () => {
  if (!search.value) return props.templates;
  const q = search.value.toLowerCase();
  return props.templates.filter((t) => t.name.toLowerCase().includes(q) || t.category.toLowerCase().includes(q));
};

async function deleteTemplate(template: Template) {
  const confirmed = await confirm({
    title: 'Delete Template',
    message: `Delete template "${template.name}"?`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('templates.destroy', template.id));
  }
}

function duplicateTemplate(template: Template) {
  router.post(route('templates.duplicate', template.id));
}
</script>

<template>
  <Head title="SMS Templates" />

  <AppLayout :breadcrumbs="[{ label: 'SMS Templates' }]">
    <PageHeader title="SMS Templates" :subtitle="`${templates.length} templates`">
      <template #actions>
        <Link :href="route('templates.create')">
          <Button>
            <Plus class="h-4 w-4" />
            Create Template
          </Button>
        </Link>
      </template>
    </PageHeader>

    <SearchInput v-model="search" placeholder="Search templates..." class="max-w-sm mb-4" />

    <div v-if="filtered().length === 0" class="py-8">
      <EmptyState
        :icon="FileText"
        title="No templates found"
        description="Create SMS templates to reuse across your campaigns."
      >
        <template #action>
          <Link :href="route('templates.create')">
            <Button size="sm">
              <Plus class="h-4 w-4" />
              Create Template
            </Button>
          </Link>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <Card
        v-for="template in filtered()"
        :key="template.id"
        class="hover:shadow-sm transition-shadow duration-200"
      >
        <CardContent class="pt-6">
          <div class="flex items-start justify-between mb-3">
            <div>
              <Link :href="route('templates.edit', template.id)" class="text-sm font-semibold text-foreground hover:text-primary transition-colors">
                {{ template.name }}
              </Link>
              <div class="flex items-center gap-2 mt-1">
                <Badge variant="secondary">{{ template.category }}</Badge>
                <StatusBadge :status="template.status" />
              </div>
            </div>
            <DropdownMenu>
              <template #trigger>
                <Button variant="ghost" size="icon-sm">
                  <MoreHorizontal class="h-4 w-4" />
                </Button>
              </template>
              <DropdownMenuItem @select="router.get(route('templates.edit', template.id))">
                <Pencil class="h-4 w-4" /> Edit
              </DropdownMenuItem>
              <DropdownMenuItem @select="duplicateTemplate(template)">
                <Copy class="h-4 w-4" /> Duplicate
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem destructive @select="deleteTemplate(template)">
                <Trash2 class="h-4 w-4" /> Delete
              </DropdownMenuItem>
            </DropdownMenu>
          </div>

          <p class="text-xs text-muted mb-3 line-clamp-3 whitespace-pre-wrap">
            {{ truncate(template.body, 150) }}
          </p>

          <div class="flex items-center gap-3 text-xs text-muted-foreground">
            <span>{{ template.character_count }} chars</span>
            <span>·</span>
            <span>{{ template.sms_segments }} segment{{ template.sms_segments !== 1 ? 's' : '' }}</span>
            <span>·</span>
            <span>{{ template.sms_encoding }}</span>
            <span>·</span>
            <span>Used {{ formatNumber(template.usage_count) }}x</span>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
