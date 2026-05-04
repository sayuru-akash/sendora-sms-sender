<script setup lang="ts">
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Alert from '@/Components/ui/Alert.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
  status?: string;
}>();

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(route('password.email'));
};
</script>

<template>
  <GuestLayout>
    <Head title="Forgot Password" />

    <p class="mb-6 text-sm text-muted">
      Enter your email address and we'll send you a link to reset your password.
    </p>

    <Alert v-if="status" variant="success" class="mb-4">
      {{ status }}
    </Alert>

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

      <Button
        type="submit"
        class="w-full"
        :loading="form.processing"
        :disabled="form.processing"
      >
        Send reset link
      </Button>
    </form>
  </GuestLayout>
</template>
