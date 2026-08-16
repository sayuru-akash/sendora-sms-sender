<script setup lang="ts">
import GuestLayout from '@/Components/layout/GuestLayout.vue';
import Button from '@/Components/ui/Button.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Home } from 'lucide-vue-next';
</script>

<template>
  <GuestLayout>
    <Head title="Page Not Found" />

    <div class="text-center py-4">
      <p class="text-6xl font-bold text-primary mb-2">404</p>
      <h2 class="text-xl font-semibold text-foreground mb-2">Page not found</h2>
      <p class="text-sm text-muted mb-6">
        The page you're looking for doesn't exist or has been moved.
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
