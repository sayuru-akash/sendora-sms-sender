<script setup lang="ts">
import { ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
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
import { List as ListIcon, Plus, MoreHorizontal, Pencil, Trash2, Send, Users, Archive } from 'lucide-vue-next';
import type { ListModel } from '@/types';
import { formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  lists: ListModel[];
}>();

const { confirm } = useConfirm();
const search = ref('');
const createOpen = ref(false);

const form = useForm({
  name: '',
  color: '#4f46e5',
  description: '',
});

const filteredLists = () => {
  if (!search.value) return props.lists;
  const q = search.value.toLowerCase();
  return props.lists.filter((l) => l.name.toLowerCase().includes(q));
};

function createList() {
  form.post(route('lists.store'), {
    onSuccess: () => {
      createOpen.value = false;
      form.reset();
    },
  });
}

async function deleteList(list: ListModel) {
  const confirmed = await confirm({
    title: 'Delete List',
    message: `Delete list "${list.name}"? Contacts will not be deleted, only removed from this list.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('lists.destroy', list.id));
  }
}

const listColors = [
  '#4f46e5', '#7c3aed', '#2563eb', '#0891b2', '#059669',
  '#d97706', '#dc2626', '#db2777', '#6366f1', '#14b8a6',
];
</script>

<template>
  <Head title="Lists" />

  <AppLayout :breadcrumbs="[{ label: 'Lists' }]">
    <PageHeader title="Lists" :subtitle="`${lists.length} lists`">
      <template #actions>
        <Button @click="createOpen = true">
          <Plus class="h-4 w-4" />
          Create List
        </Button>
      </template>
    </PageHeader>

    <SearchInput v-model="search" placeholder="Search lists..." class="max-w-sm mb-4" />

    <div v-if="filteredLists().length === 0" class="py-8">
      <EmptyState
        :icon="ListIcon"
        title="No lists found"
        description="Create lists to group your contacts for targeted campaigns."
      >
        <template #action>
          <Button size="sm" @click="createOpen = true">
            <Plus class="h-4 w-4" />
            Create List
          </Button>
        </template>
      </EmptyState>
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <Card
        v-for="list in filteredLists()"
        :key="list.id"
        class="hover:shadow-sm transition-shadow duration-200 overflow-hidden"
      >
        <div class="h-1" :style="{ backgroundColor: list.color }" />
        <div class="p-4">
          <div class="flex items-start justify-between mb-2">
            <Link :href="route('lists.show', list.id)" class="text-sm font-semibold text-foreground hover:text-primary transition-colors">
              {{ list.name }}
            </Link>
            <DropdownMenu>
              <template #trigger>
                <Button variant="ghost" size="icon-sm">
                  <MoreHorizontal class="h-4 w-4" />
                </Button>
              </template>
              <DropdownMenuItem @select="router.get(route('lists.show', list.id))">
                <Users class="h-4 w-4" /> View Contacts
              </DropdownMenuItem>
              <DropdownMenuItem @select="router.get(route('campaigns.builder', { list_id: list.id }))">
                <Send class="h-4 w-4" /> Send Campaign
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem destructive @select="deleteList(list)">
                <Trash2 class="h-4 w-4" /> Delete
              </DropdownMenuItem>
            </DropdownMenu>
          </div>
          <p v-if="list.description" class="text-xs text-muted mb-2 line-clamp-2">{{ list.description }}</p>
          <div class="flex items-center justify-between">
            <span class="flex items-center gap-1 text-xs text-muted">
              <Users class="h-3 w-3" />
              {{ formatNumber(list.contacts_count) }} contacts
            </span>
            <StatusBadge :status="list.status" />
          </div>
        </div>
      </Card>
    </div>

    <!-- Create List Dialog -->
    <Dialog
      v-model:open="createOpen"
      title="Create List"
      description="Create a new contact list for organizing your audience."
    >
      <form @submit.prevent="createList" class="space-y-4 mt-4">
        <div class="space-y-1.5">
          <Label for="list_name" required>List Name</Label>
          <Input id="list_name" v-model="form.name" placeholder="e.g. Newsletter Subscribers" :error="form.errors.name" />
          <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
        </div>
        <div class="space-y-1.5">
          <Label>Color</Label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="color in listColors"
              :key="color"
              type="button"
              class="h-7 w-7 rounded-full border-2 transition-all"
              :style="{ backgroundColor: color, borderColor: form.color === color ? '#1a1a1a' : 'transparent' }"
              @click="form.color = color"
            />
          </div>
        </div>
        <div class="space-y-1.5">
          <Label for="list_desc">Description</Label>
          <Textarea id="list_desc" v-model="form.description" :rows="2" placeholder="Optional description..." />
        </div>
        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="createOpen = false">Cancel</Button>
          <Button type="submit" :loading="form.processing">Create List</Button>
        </div>
      </form>
    </Dialog>
  </AppLayout>
</template>
