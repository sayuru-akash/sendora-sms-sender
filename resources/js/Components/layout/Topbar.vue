<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Search, LogOut, User, Menu, X, ChevronDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import Avatar from '@/Components/ui/Avatar.vue';
import Button from '@/Components/ui/Button.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
  breadcrumbs?: BreadcrumbItem[];
  showMobileMenu?: boolean;
}>();

const emit = defineEmits<{
  'toggle-mobile': [];
}>();

const page = usePage();
const user = computed(() => page.props.auth?.user ?? { name: 'User', email: '', role: 'staff' });
const searchQuery = ref('');
const menuOpen = ref(false);

function handleSearch() {
  if (searchQuery.value.trim()) {
    router.get(route('contacts.index', { search: searchQuery.value }));
    searchQuery.value = '';
  }
}

function logout() {
  router.post(route('logout'));
}

function toggleMenu() {
  menuOpen.value = !menuOpen.value;
}

function closeMenu() {
  menuOpen.value = false;
}
</script>

<template>
  <header class="h-16 border-b border-border bg-white flex items-center justify-between px-4 lg:px-6 shrink-0">
    <!-- Left: Mobile menu button + Breadcrumbs -->
    <div class="flex items-center gap-3">
      <button
        @click="emit('toggle-mobile')"
        class="lg:hidden rounded-md p-2 text-muted-foreground hover:bg-gray-100 hover:text-foreground transition-colors"
      >
        <Menu v-if="!showMobileMenu" class="h-5 w-5" />
        <X v-else class="h-5 w-5" />
      </button>

      <nav v-if="breadcrumbs?.length" class="hidden sm:flex items-center gap-1.5 text-sm">
        <Link
          :href="route('dashboard')"
          class="text-muted hover:text-foreground transition-colors"
        >
          Home
        </Link>
        <template v-for="(crumb, index) in breadcrumbs" :key="index">
          <span class="text-muted-foreground">/</span>
          <Link
            v-if="crumb.href && index < breadcrumbs.length - 1"
            :href="crumb.href"
            class="text-muted hover:text-foreground transition-colors"
          >
            {{ crumb.label }}
          </Link>
          <span v-else class="text-foreground font-medium">{{ crumb.label }}</span>
        </template>
      </nav>
    </div>

    <!-- Center: Search -->
    <div class="hidden md:flex flex-1 max-w-md mx-4">
      <form @submit.prevent="handleSearch" class="relative w-full">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search contacts, campaigns, templates..."
          class="h-9 w-full rounded-lg border border-border bg-gray-50 pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-colors"
        />
      </form>
    </div>

    <!-- Right: User Menu (simple CSS dropdown) -->
    <div class="relative">
      <button
        @click="toggleMenu"
        class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-100 transition-colors"
      >
        <div class="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-medium shrink-0">
          {{ (user.name || 'U').charAt(0).toUpperCase() }}
        </div>
        <span class="hidden sm:block text-sm font-medium text-foreground">{{ user.name || 'User' }}</span>
        <ChevronDown class="h-3.5 w-3.5 text-muted-foreground hidden sm:block" />
      </button>

      <!-- Dropdown menu -->
      <div
        v-if="menuOpen"
        class="absolute right-0 top-full mt-1 z-50 min-w-[200px] rounded-lg border border-border bg-white p-1 shadow-md"
      >
        <div class="px-2 py-1.5 border-b border-border mb-1">
          <p class="text-sm font-medium text-foreground">{{ user.name || 'User' }}</p>
          <p class="text-xs text-muted">{{ user.email || '' }}</p>
        </div>
        <Link
          :href="route('profile.edit')"
          class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-foreground hover:bg-gray-100 transition-colors"
          @click="closeMenu"
        >
          <User class="h-4 w-4" />
          Profile
        </Link>
        <button
          @click="logout"
          class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-danger hover:bg-danger-light w-full text-left transition-colors"
        >
          <LogOut class="h-4 w-4" />
          Log Out
        </button>
      </div>

      <!-- Backdrop to close menu -->
      <div
        v-if="menuOpen"
        class="fixed inset-0 z-40"
        @click="closeMenu"
      />
    </div>
  </header>
</template>
