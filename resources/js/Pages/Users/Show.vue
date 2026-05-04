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
import Avatar from '@/Components/ui/Avatar.vue';
import Separator from '@/Components/ui/Separator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2, Mail, Shield, Calendar, Clock } from 'lucide-vue-next';
import type { User } from '@/types';
import { ROLE_COLORS } from '@/types/user';
import { formatDateTime, formatRelativeTime } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  user: User;
}>();

const { confirm } = useConfirm();

async function handleDelete() {
  const confirmed = await confirm({
    title: 'Delete User',
    message: `Delete user "${props.user.name}"? They will lose access immediately. This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('users.destroy', props.user.id));
  }
}
</script>

<template>
  <Head :title="user.name" />

  <AppLayout :breadcrumbs="[
    { label: 'Settings', href: route('settings.index') },
    { label: 'Users', href: route('users.index') },
    { label: user.name },
  ]">
    <PageHeader :title="user.name" :subtitle="user.email">
      <template #actions>
        <Link :href="route('users.index')" class="text-sm text-muted hover:text-foreground transition-colors">
          ← Back to Users
        </Link>
        <Link :href="route('users.edit', user.id)">
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

    <div class="max-w-2xl space-y-6">
      <!-- Profile Card -->
      <Card>
        <CardContent class="pt-6">
          <div class="flex items-center gap-4 mb-6">
            <Avatar :alt="user.name" size="xl" />
            <div>
              <h2 class="text-lg font-semibold text-foreground">{{ user.name }}</h2>
              <p class="text-sm text-muted">{{ user.email }}</p>
              <div class="flex items-center gap-2 mt-2">
                <Badge :class="ROLE_COLORS[user.role]" variant="outline">
                  {{ user.role }}
                </Badge>
                <StatusBadge :status="user.status" />
              </div>
            </div>
          </div>

          <Separator class="my-4" />

          <div class="space-y-4">
            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                <Mail class="h-4 w-4 text-primary" />
              </div>
              <div>
                <p class="text-xs text-muted">Email</p>
                <p class="text-sm font-medium text-foreground">{{ user.email }}</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                <Shield class="h-4 w-4 text-primary" />
              </div>
              <div>
                <p class="text-xs text-muted">Role</p>
                <p class="text-sm font-medium text-foreground capitalize">{{ user.role }}</p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                <Clock class="h-4 w-4 text-primary" />
              </div>
              <div>
                <p class="text-xs text-muted">Last Login</p>
                <p class="text-sm font-medium text-foreground">
                  {{ user.last_login_at ? formatRelativeTime(user.last_login_at) : 'Never' }}
                </p>
                <p v-if="user.last_login_at" class="text-xs text-muted">
                  {{ formatDateTime(user.last_login_at) }}
                </p>
              </div>
            </div>

            <div class="flex items-center gap-3">
              <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10">
                <Calendar class="h-4 w-4 text-primary" />
              </div>
              <div>
                <p class="text-xs text-muted">Created</p>
                <p class="text-sm font-medium text-foreground">{{ formatDateTime(user.created_at) }}</p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
