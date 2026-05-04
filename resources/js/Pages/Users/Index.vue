<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { UserPlus, MoreHorizontal, Pencil, Trash2, UserCog } from 'lucide-vue-next';
import type { User } from '@/types';
import { ROLE_COLORS } from '@/types/user';
import { formatDateTime, formatRelativeTime } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  users: User[];
}>();

const { confirm } = useConfirm();

async function deleteUser(user: User) {
  const confirmed = await confirm({
    title: 'Delete User',
    message: `Delete user "${user.name}"? They will lose access immediately.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) router.delete(route('users.destroy', user.id));
}
</script>

<template>
  <Head title="Users" />

  <AppLayout :breadcrumbs="[
    { label: 'Settings', href: route('settings.index') },
    { label: 'Users' },
  ]">
    <PageHeader title="Users" :subtitle="`${users.length} users`">
      <template #actions>
        <Link :href="route('users.create')">
          <Button>
            <UserPlus class="h-4 w-4" />
            Add User
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="rounded-xl border border-border bg-white overflow-hidden">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-border">
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">User</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Role</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Status</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Last Login</th>
            <th class="h-10 px-4 w-10"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="users.length === 0">
            <td colspan="5" class="px-4 py-12 text-center text-sm text-muted">No users found.</td>
          </tr>
          <tr
            v-for="user in users"
            :key="user.id"
            class="border-b border-border hover:bg-gray-50/50 transition-colors"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <Avatar :alt="user.name" size="sm" />
                <div>
                  <p class="text-sm font-medium text-foreground">{{ user.name }}</p>
                  <p class="text-xs text-muted">{{ user.email }}</p>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <Badge :class="ROLE_COLORS[user.role]" variant="outline">
                {{ user.role }}
              </Badge>
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="user.status" />
            </td>
            <td class="px-4 py-3 text-sm text-muted">
              {{ user.last_login_at ? formatRelativeTime(user.last_login_at) : 'Never' }}
            </td>
            <td class="px-4 py-3">
              <DropdownMenu>
                <template #trigger>
                  <Button variant="ghost" size="icon-sm">
                    <MoreHorizontal class="h-4 w-4" />
                  </Button>
                </template>
                <DropdownMenuItem @select="router.get(route('users.edit', user.id))">
                  <Pencil class="h-4 w-4" /> Edit
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem destructive @select="deleteUser(user)">
                  <Trash2 class="h-4 w-4" /> Delete
                </DropdownMenuItem>
              </DropdownMenu>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>
