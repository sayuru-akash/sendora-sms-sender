<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import SendoraLogo from '@/Components/icons/SendoraLogo.vue';
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-background px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Logo & Branding -->
      <div class="mb-8 flex flex-col items-center">
        <Link :href="route('home')" class="mb-4">
          <SendoraLogo class="h-12 w-12" />
        </Link>
        <h1 class="text-2xl font-bold text-foreground tracking-tight">Sendora</h1>
        <p class="mt-1 text-sm text-muted">SMS Campaign Management Platform</p>
      </div>

      <!-- Content Card -->
      <div class="rounded-xl border border-border bg-white p-6 shadow-sm">
        <slot />
      </div>

      <!-- Footer -->
      <p class="mt-6 text-center text-xs text-muted-foreground">
        &copy; {{ new Date().getFullYear() }} Sendora. All rights reserved.
      </p>
    </div>
  </div>
</template>
