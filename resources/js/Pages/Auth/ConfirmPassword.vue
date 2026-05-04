<script setup lang="ts">
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
  password: '',
});

const submit = () => {
  form.post(route('password.confirm'), {
    onFinish: () => form.reset(),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Confirm Password" />

    <div class="mb-4 text-sm text-muted">
      This is a secure area of the application. Please confirm your password before continuing.
    </div>

    <form @submit.prevent="submit" class="space-y-5">
      <div class="space-y-1.5">
        <Label for="password" required>Password</Label>
        <Input
          id="password"
          v-model="form.password"
          type="password"
          placeholder="Enter your password"
          autocomplete="current-password"
          :error="form.errors.password"
          autofocus
        />
        <p v-if="form.errors.password" class="text-xs text-danger">{{ form.errors.password }}</p>
      </div>

      <Button
        type="submit"
        class="w-full"
        :loading="form.processing"
        :disabled="form.processing"
      >
        Confirm
      </Button>
    </form>
  </GuestLayout>
</template>
