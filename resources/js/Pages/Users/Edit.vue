<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import type { User } from '@/types';
import { USER_ROLES } from '@/types/user';

const props = defineProps<{
  user: User;
}>();

const page = usePage();
const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
  password_confirmation: '',
  role: props.user.role,
  status: props.user.status,
});

const roleOptions = computed(() => USER_ROLES
  .filter((role) => page.props.auth.user.role === 'owner' || !['owner', 'admin'].includes(role.value))
  .map((role) => ({ label: role.label, value: role.value })));
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
  { label: 'Suspended', value: 'suspended' },
];

function submit() {
  form.put(route('users.update', props.user.id));
}
</script>

<template>
  <Head :title="`Edit: ${user.name}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Settings', href: route('settings.index') },
    { label: 'Users', href: route('users.index') },
    { label: user.name, href: route('users.show', user.id) },
    { label: 'Edit' },
  ]">
    <PageHeader :title="`Edit: ${user.name}`" subtitle="Update user account details.">
      <template #actions>
        <Link :href="route('users.index')" class="text-sm text-muted hover:text-foreground transition-colors">
          ← Back to Users
        </Link>
        <Button variant="outline" @click="$inertia.visit(route('users.show', user.id))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Update User
        </Button>
      </template>
    </PageHeader>

    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Account Information</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label for="name" required>Full Name</Label>
              <Input id="name" v-model="form.name" :error="form.errors.name" />
              <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="email" required>Email</Label>
              <Input id="email" v-model="form.email" type="email" :error="form.errors.email" />
              <p v-if="form.errors.email" class="text-xs text-danger">{{ form.errors.email }}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Security</h3>
          <div class="space-y-1.5">
            <Label for="password">Password</Label>
            <Input id="password" v-model="form.password" type="password" placeholder="Leave blank to keep current password" :error="form.errors.password" />
            <p v-if="form.errors.password" class="text-xs text-danger">{{ form.errors.password }}</p>
            <p v-else class="text-xs text-muted">Leave empty to keep the current password.</p>
          </div>
          <div class="space-y-1.5">
            <Label for="password_confirmation">Confirm Password</Label>
            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="Repeat the new password" />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent class="pt-6 space-y-4">
          <h3 class="text-sm font-semibold text-foreground mb-4">Role & Status</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1.5">
              <Label>Role</Label>
              <Select v-model="form.role" :options="roleOptions" />
              <p v-if="form.errors.role" class="text-xs text-danger">{{ form.errors.role }}</p>
            </div>
            <div class="space-y-1.5">
              <Label>Status</Label>
              <Select v-model="form.status" :options="statusOptions" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Actions -->
      <div class="flex items-center justify-end gap-3">
        <Link :href="route('users.show', user.id)" class="text-sm text-muted hover:text-foreground transition-colors">
          Cancel
        </Link>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Update User
        </Button>
      </div>
    </form>
  </AppLayout>
</template>
