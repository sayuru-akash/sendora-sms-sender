<script setup lang="ts">
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
  email: string;
  token: string;
}>();

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(route('password.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Reset Password" />

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <Label for="email" required>Email address</Label>
        <Input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="username"
          :error="form.errors.email"
        />
        <p v-if="form.errors.email" class="text-xs text-danger">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-1.5">
        <Label for="password" required>New password</Label>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="At least 8 characters"
          autocomplete="new-password"
          :error="form.errors.password"
          autofocus
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
        Reset password
      </Button>
    </form>
  </GuestLayout>
</template>
