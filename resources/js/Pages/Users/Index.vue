<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import Pagination from '@/Components/common/Pagination.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import Select from '@/Components/ui/Select.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FilterX, MoreHorizontal, Pencil, Trash2, UserCog, UserPlus } from 'lucide-vue-next';
import type { Pagination as PaginationType, User } from '@/types';
import { ROLE_COLORS, USER_ROLES } from '@/types/user';
import { formatRelativeTime } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  users?: { data: User[]; meta: PaginationType };
  filters?: {
    search?: string;
    role?: string;
    status?: string;
    per_page?: string | number;
  };
  filterOptions?: {
    roles?: string[];
    statuses?: string[];
    perPage?: number[];
  };
}>();

const { confirm } = useConfirm();
const emptyMeta: PaginationType = {
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  from: null,
  to: null,
};
const safeUsers = computed(() => props.users ?? { data: [], meta: emptyMeta });
const safeFilters = computed(() => props.filters ?? {});
const safeFilterOptions = computed(() => ({
  roles: props.filterOptions?.roles ?? ['owner', 'admin', 'manager', 'staff', 'viewer'],
  statuses: props.filterOptions?.statuses ?? ['active', 'inactive', 'suspended'],
  perPage: props.filterOptions?.perPage ?? [10, 25, 50, 100],
}));
const search = ref(safeFilters.value.search ?? '');
const selectedRole = ref(safeFilters.value.role ?? '');
const selectedStatus = ref(safeFilters.value.status ?? '');
const selectedPerPage = ref(safeFilters.value.per_page ? String(safeFilters.value.per_page) : String(safeUsers.value.meta.per_page ?? 25));
const hasFilters = computed(() => Boolean(search.value || selectedRole.value || selectedStatus.value));

const roleOptions = computed(() => [
  { label: 'All roles', value: '' },
  ...safeFilterOptions.value.roles.map((role) => ({
    label: USER_ROLES.find((item) => item.value === role)?.label ?? role,
    value: role,
  })),
]);

const statusOptions = computed(() => [
  { label: 'All statuses', value: '' },
  ...safeFilterOptions.value.statuses.map((status) => ({ label: status.charAt(0).toUpperCase() + status.slice(1), value: status })),
]);

const perPageOptions = computed(() => safeFilterOptions.value.perPage.map((value) => ({ label: `${value} / page`, value: String(value) })));

function applyFilters(overrides: Record<string, string | number | undefined> = {}) {
  router.get(
    route('users.index'),
    {
      search: search.value || undefined,
      role: selectedRole.value || undefined,
      status: selectedStatus.value || undefined,
      per_page: selectedPerPage.value || undefined,
      ...overrides,
    },
    { preserveState: true, preserveScroll: true, replace: true },
  );
}

function clearFilters() {
  search.value = '';
  selectedRole.value = '';
  selectedStatus.value = '';
  selectedPerPage.value = '25';
  router.get(route('users.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
}

async function deleteUser(user: User) {
  const confirmed = await confirm({
    title: 'Delete User',
    message: `Delete user "${user.name}"? They will lose access immediately.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });

  if (confirmed) {
    router.delete(route('users.destroy', user.id));
  }
}
</script>

<template>
  <Head title="Users" />

  <AppLayout :breadcrumbs="[
    { label: 'Settings', href: route('settings.index') },
    { label: 'Users' },
  ]">
    <PageHeader title="Users" :subtitle="`${safeUsers.meta.total} users`">
      <template #actions>
        <Link :href="route('users.create')">
          <Button>
            <UserPlus class="h-4 w-4" />
            Add User
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,20rem)_10rem_10rem_8rem]">
        <SearchInput
          :model-value="search"
          placeholder="Search users..."
          @update:model-value="(value) => { search = value; applyFilters({ page: undefined }); }"
        />
        <Select
          :model-value="selectedRole"
          :options="roleOptions"
          @update:model-value="(value) => { selectedRole = String(value); applyFilters({ page: undefined }); }"
        />
        <Select
          :model-value="selectedStatus"
          :options="statusOptions"
          @update:model-value="(value) => { selectedStatus = String(value); applyFilters({ page: undefined }); }"
        />
        <Select
          :model-value="selectedPerPage"
          :options="perPageOptions"
          @update:model-value="(value) => { selectedPerPage = String(value); applyFilters({ page: undefined }); }"
        />
      </div>
      <Button
        v-if="hasFilters"
        variant="ghost"
        size="sm"
        class="text-muted-foreground"
        @click="clearFilters"
      >
        <FilterX class="h-4 w-4" />
        Clear
      </Button>
    </div>

    <div v-if="safeUsers.data.length === 0" class="rounded-xl border border-border bg-white py-12">
      <EmptyState
        :icon="UserCog"
        :title="hasFilters ? 'No users found' : 'No users yet'"
        :description="hasFilters ? 'Change the filters to see more users.' : 'Create the first operator account from here.'"
      />
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-border bg-white">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-sm">
          <thead>
            <tr class="border-b border-border">
              <th class="h-10 px-4 text-left text-xs font-medium uppercase tracking-wider text-muted">User</th>
              <th class="h-10 px-4 text-left text-xs font-medium uppercase tracking-wider text-muted">Role</th>
              <th class="h-10 px-4 text-left text-xs font-medium uppercase tracking-wider text-muted">Status</th>
              <th class="h-10 px-4 text-left text-xs font-medium uppercase tracking-wider text-muted">Last Login</th>
              <th class="h-10 w-10 px-4"></th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="user in safeUsers.data"
              :key="user.id"
              class="border-b border-border transition-colors hover:bg-gray-50/50"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <Avatar :alt="user.name" size="sm" />
                  <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-foreground">{{ user.name }}</p>
                    <p class="truncate text-xs text-muted">{{ user.email }}</p>
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
                    <Button variant="ghost" size="icon-sm" aria-label="User actions">
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
    </div>

    <Pagination :meta="safeUsers.meta" />
  </AppLayout>
</template>
