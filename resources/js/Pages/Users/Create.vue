<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import { USER_ROLES } from '@/types/user';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: 'staff',
  status: 'active',
});

const roleOptions = USER_ROLES.map((r) => ({ label: r.label, value: r.value }));
const statusOptions = [
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
];

function submit() {
  form.post(route('users.store'));
}
</script>

<template>
  <Head title="Add User" />

  <AppLayout :breadcrumbs="[
    { label: 'Settings', href: route('settings.index') },
    { label: 'Users', href: route('users.index') },
    { label: 'Add User' },
  ]">
    <PageHeader title="Add User" subtitle="Create a new user account.">
      <template #actions>
        <Button variant="outline" @click="$inertia.visit(route('users.index'))">Cancel</Button>
        <Button :loading="form.processing" @click="submit">
          <Save class="h-4 w-4" />
          Create User
        </Button>
      </template>
    </PageHeader>

    <Card class="max-w-2xl">
      <CardContent class="pt-6 space-y-4">
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
          <div class="space-y-1.5">
            <Label for="password" required>Password</Label>
            <Input id="password" v-model="form.password" type="password" :error="form.errors.password" />
            <p v-if="form.errors.password" class="text-xs text-danger">{{ form.errors.password }}</p>
          </div>
          <div class="space-y-1.5">
            <Label for="password_confirmation" required>Confirm Password</Label>
            <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
          </div>
          <div class="space-y-1.5">
            <Label>Role</Label>
            <Select v-model="form.role" :options="roleOptions" />
          </div>
          <div class="space-y-1.5">
            <Label>Status</Label>
            <Select v-model="form.status" :options="statusOptions" />
          </div>
        </div>
      </CardContent>
    </Card>
  </AppLayout>
</template>
