<script setup lang="ts">
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Register" />

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <Label for="name" required>Full name</Label>
        <Input
          id="name"
          v-model="form.name"
          type="text"
          placeholder="John Doe"
          autocomplete="name"
          :error="form.errors.name"
          autofocus
        />
        <p v-if="form.errors.name" class="text-xs text-danger">{{ form.errors.name }}</p>
      </div>

      <div class="space-y-1.5">
        <Label for="email" required>Email address</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          placeholder="you@company.com"
          autocomplete="username"
          :error="form.errors.email"
        />
        <p v-if="form.errors.email" class="text-xs text-danger">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-1.5">
        <Label for="password" required>Password</Label>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="At least 8 characters"
          autocomplete="new-password"
          :error="form.errors.password"
        />
        <p v-if="form.errors.password" class="text-xs text-danger">{{ form.errors.password }}</p>
      </div>

      <div class="space-y-1.5">
        <Label for="password_confirmation" required>Confirm password</Label>
        <Input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          placeholder="Repeat your password"
          autocomplete="new-password"
        />
      </div>

      <Button
        type="submit"
        class="w-full"
        :loading="form.processing"
        :disabled="form.processing"
      >
        Create account
      </Button>

      <p class="text-center text-sm text-muted">
        Already have an account?
        <Link :href="route('login')" class="font-medium text-primary hover:text-primary-hover transition-colors">
          Sign in
        </Link>
      </p>
    </form>
  </GuestLayout>
</template>
