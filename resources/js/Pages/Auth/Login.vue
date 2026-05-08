<script setup lang="ts">
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Checkbox from '@/Components/ui/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
  canResetPassword?: boolean;
  status?: string;
}>();

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Log in" />

    <div v-if="status" class="mb-4 rounded-lg bg-success-light border border-success/20 p-3 text-sm font-medium text-success">
      {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <Label for="email" required>Email address</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          placeholder="you@company.com"
          autocomplete="username"
          :error="form.errors.email"
          autofocus
        />
        <p v-if="form.errors.email" class="text-xs text-danger">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-1.5">
        <div class="flex items-center justify-between">
          <Label for="password" required>Password</Label>
          <Link
            v-if="canResetPassword"
            :href="route('password.request')"
            class="text-xs text-primary hover:text-primary-hover transition-colors"
          >
            Forgot password?
          </Link>
        </div>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="Enter your password"
          autocomplete="current-password"
          :error="form.errors.password"
        />
        <p v-if="form.errors.password" class="text-xs text-danger">{{ form.errors.password }}</p>
      </div>

      <label class="flex items-center gap-2 cursor-pointer">
        <Checkbox v-model="form.remember" />
        <span class="text-sm text-muted">Remember me</span>
      </label>

      <Button
        type="submit"
        class="w-full"
        :loading="form.processing"
        :disabled="form.processing"
      >
        Sign in
      </Button>

      <p class="text-center text-sm text-muted">
        Accounts are created by an administrator.
      </p>
    </form>
  </GuestLayout>
</template>
