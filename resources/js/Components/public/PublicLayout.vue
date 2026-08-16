<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Menu, X } from 'lucide-vue-next';
import SendoraLogo from '@/Components/icons/SendoraLogo.vue';

defineProps<{
  contained?: boolean;
}>();

const menuOpen = ref(false);

function closeMenu(): void {
  menuOpen.value = false;
}
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <a
      class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-50 focus:rounded-md focus:bg-primary focus:px-3 focus:py-2 focus:text-sm focus:font-medium focus:text-white focus:outline-none"
      href="#main"
    >
      Skip to content
    </a>

    <header class="sticky top-0 z-40 border-b border-border bg-white/95 backdrop-blur">
      <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-4 px-4 lg:px-8">
        <Link class="flex items-center gap-3" :href="route('home')" @click="closeMenu">
          <SendoraLogo class="size-9" />
          <span class="text-sm font-semibold tracking-tight">Sendora</span>
        </Link>

        <nav class="hidden items-center gap-7 text-sm text-muted lg:flex" aria-label="Primary">
          <a class="transition-colors hover:text-foreground" href="/#workflow">Workflow</a>
          <a class="transition-colors hover:text-foreground" href="/#control">Control</a>
          <a class="transition-colors hover:text-foreground" href="/#trust">Trust</a>
          <Link class="transition-colors hover:text-foreground" :href="route('privacy')">Privacy</Link>
          <Link class="transition-colors hover:text-foreground" :href="route('terms')">Terms</Link>
        </nav>

        <div class="flex items-center gap-2">
          <Link
            class="hidden h-9 items-center rounded-md border border-border bg-white px-3 text-sm font-medium transition-colors hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 sm:inline-flex"
            :href="route('login')"
          >
            Log in
          </Link>
          <button
            class="inline-flex size-9 items-center justify-center rounded-md border border-border bg-white text-foreground transition-colors hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 lg:hidden"
            type="button"
            :aria-expanded="menuOpen"
            aria-controls="public-navigation"
            :aria-label="menuOpen ? 'Close navigation' : 'Open navigation'"
            @click="menuOpen = !menuOpen"
          >
            <X v-if="menuOpen" class="size-4" aria-hidden="true" focusable="false" />
            <Menu v-else class="size-4" aria-hidden="true" focusable="false" />
          </button>
        </div>
      </div>

      <nav
        v-if="menuOpen"
        id="public-navigation"
        class="border-t border-border bg-white px-4 py-3 lg:hidden"
        aria-label="Mobile primary"
      >
        <div class="mx-auto grid max-w-7xl gap-1">
          <a class="rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-50" href="/#workflow" @click="closeMenu">Workflow</a>
          <a class="rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-50" href="/#control" @click="closeMenu">Control</a>
          <a class="rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-50" href="/#trust" @click="closeMenu">Trust</a>
          <Link class="rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-50" :href="route('privacy')" @click="closeMenu">Privacy</Link>
          <Link class="rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-50" :href="route('terms')" @click="closeMenu">Terms</Link>
          <Link class="rounded-md px-3 py-2 text-sm font-medium hover:bg-gray-50 sm:hidden" :href="route('login')" @click="closeMenu">Log in</Link>
        </div>
      </nav>
    </header>

    <main id="main" :class="contained ? 'mx-auto w-full max-w-4xl px-4 py-12 lg:px-8' : ''">
      <slot />
    </main>

    <footer class="border-t border-border bg-white">
      <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-muted sm:flex-row sm:items-center sm:justify-between lg:px-8">
        <p>Sendora by Codezela Technologies</p>
        <nav class="flex flex-wrap gap-4" aria-label="Legal">
          <Link class="transition-colors hover:text-foreground" :href="route('privacy')">Privacy</Link>
          <Link class="transition-colors hover:text-foreground" :href="route('terms')">Terms</Link>
          <Link class="transition-colors hover:text-foreground" :href="route('login')">Log in</Link>
        </nav>
      </div>
    </footer>
  </div>
</template>
