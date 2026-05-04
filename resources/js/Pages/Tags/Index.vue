<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import Card from '@/Components/ui/Card.vue';
import Button from '@/Components/ui/Button.vue';
import Dialog from '@/Components/ui/Dialog.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Textarea from '@/Components/ui/Textarea.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Tag as TagIcon, Plus, MoreHorizontal, Pencil, Trash2, Send, Users } from 'lucide-vue-next';
import type { Tag } from '@/types';
import { formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  tags: Tag[];
}>();

const { confirm } = useConfirm();
const search = ref('');
const createOpen = ref(false);

const form = useForm({
  name: '',
  color: '#4f46e5',
  description: '',
});

const filteredTags = () => {
  if (!search.value) return props.tags;
  const q = search.value.toLowerCase();
  return props.tags.filter((t) => t.name.toLowerCase().includes(q));
};

function createTag() {
  form.post(route('tags.store'), {
    onSuccess: () => {
      createOpen.value = false;
      form.reset();
    },
  });
}

async function deleteTag(tag: Tag) {
  const confirmed = await confirm({
    title: 'Delete Tag',
    message: `Delete tag "${tag.name}"? This will remove it from all contacts.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('tags.destroy', tag.id));
  }
}

const tagColors = [
  '#4f46e5', '#7c3aed', '#2563eb', '#0891b2', '#059669',
  '#d97706', '#dc2626', '#db2777', '#6366f1', '#14b8a6',
];
</script>

<template>
  <Head title="Tags" />

  <AppLayout :breadcrumbs="[{ label: 'Tags' }]">
    <PageHeader title="Tags" :subtitle="`${tags.length} tags`">
      <template #actions>
        <Button @click="createOpen = true">
          <Plus class="h-4 w-4" />
          Create Tag
        </Button>
      </template>
    </PageHeader>

    <SearchInput v-model="search" placeholder="Search tags..." class="max-w-sm mb-4" />

    <div v-if="filteredTags().length === 0" class="py-8">
      <EmptyState
        :icon="TagIcon"
        title="No tags found"
        description="Create tags to organize and segment your contacts."
      >
        <template #action>
          <Button size="sm" @click="createOpen = true">
            <Plus class="h-4 w-4" />
            Create Tag
          </Button>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <Card
        v-for="tag in filteredTags()"
        :key="tag.id"
        class="hover:shadow-sm transition-shadow duration-200 overflow-hidden"
      >
        <div class="h-1" :style="{ backgroundColor: tag.color }" />
        <div class="p-4">
          <div class="flex items-start justify-between mb-2">
            <Link :href="route('tags.show', tag.id)" class="text-sm font-semibold text-foreground hover:text-primary transition-colors">
              {{ tag.name }}
            </Link>
            <DropdownMenu>
              <template #trigger>
                <Button variant="ghost" size="icon-sm">
                  <MoreHorizontal class="h-4 w-4" />
                </Button>
              </template>
              <DropdownMenuItem @select="router.get(route('tags.show', tag.id))">
                <Users class="h-4 w-4" /> View Contacts
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem destructive @select="deleteTag(tag)">
                <Trash2 class="h-4 w-4" /> Delete
              </DropdownMenuItem>
            </DropdownMenu>
          </div>
          <p v-if="tag.description" class="text-xs text-muted mb-2 line-clamp-2">{{ tag.description }}</p>
          <div class="flex items-center gap-1 text-xs text-muted">
            <Users class="h-3 w-3" />
            {{ formatNumber(tag.contacts_count) }} contacts
          </div>
        </div>
      </Card>
    </div>

    <!-- Create Tag Dialog -->
    <Dialog
      v-model:open="createOpen"
      title="Create Tag"
      description="Add a new tag to organize your contacts."
    >
      <form @submit.prevent="createTag" class="space-y-4 mt-4">
        <div class="space-y-1.5">
          <Label for="tag_name" required>Tag Name</Label>
          <Input id="tag_name" v-model="form.name" placeholder="e.g. VIP Customer" :error="form.errors.name" />
          <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
        </div>
        <div class="space-y-1.5">
          <Label>Color</Label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="color in tagColors"
              :key="color"
              type="button"
              class="h-7 w-7 rounded-full border-2 transition-all"
              :style="{ backgroundColor: color, borderColor: form.color === color ? '#1a1a1a' : 'transparent' }"
              @click="form.color = color"
            />
          </div>
        </div>
        <div class="space-y-1.5">
          <Label for="tag_desc">Description</Label>
          <Textarea id="tag_desc" v-model="form.description" :rows="2" placeholder="Optional description..." />
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="createOpen = false">Cancel</Button>
          <Button type="submit" :loading="form.processing">Create Tag</Button>
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
