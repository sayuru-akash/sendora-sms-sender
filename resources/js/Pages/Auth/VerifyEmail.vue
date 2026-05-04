<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import Alert from '@/Components/ui/Alert.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
  status?: string;
}>();

const form = useForm({});

const submit = () => {
  form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
  <GuestLayout>
    <Head title="Email Verification" />

    <p class="mb-4 text-sm text-muted">
      Before getting started, please verify your email address by clicking the link we sent you.
    </p>

    <Alert v-if="verificationLinkSent" variant="success" class="mb-4">
      A new verification link has been sent to your email address.
    </Alert>

    <form @submit.prevent="submit" class="space-y-4">
      <Button
        type="submit"
        class="w-full"
        :loading="form.processing"
        :disabled="form.processing"
      >
        Resend verification email
      </Button>

      <div class="flex items-center justify-between">
        <Link
          :href="route('profile.edit')"
          class="text-sm text-primary hover:text-primary-hover transition-colors"
        >
          Edit profile
        </Link>
        <Link
          :href="route('logout')"
          method="post"
          as="button"
          class="text-sm text-muted hover:text-foreground transition-colors"
        >
          Log out
        </Link>
      </div>
    </form>
  </GuestLayout>
</template>
