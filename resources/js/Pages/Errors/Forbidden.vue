<script setup lang="ts">
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Home, ShieldAlert } from 'lucide-vue-next';
</script>

<template>
  <GuestLayout>
    <Head title="Access Denied" />

    <div class="text-center py-4">
      <ShieldAlert class="mx-auto h-12 w-12 text-danger mb-4" />
      <p class="text-6xl font-bold text-primary mb-2">403</p>
      <h2 class="text-xl font-semibold text-foreground mb-2">Access Denied</h2>
      <p class="text-sm text-muted mb-6">
        You don't have permission to access this page. Contact your administrator if you believe this is an error.
      </p>
      <Link :href="route('home')">
        <Button>
          <Home class="h-4 w-4" />
          Back to home
        </Button>
      </Link>
    </div>
  </GuestLayout>
</template>
