<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Search, LogOut, User, Menu, X } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import Avatar from '@/Components/ui/Avatar.vue';
import Button from '@/Components/ui/Button.vue';
import {
  DropdownMenuRoot,
  DropdownMenuTrigger,
  DropdownMenuPortal,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from 'reka-ui';
import type { BreadcrumbItem } from '@/types';

defineProps<{
  breadcrumbs?: BreadcrumbItem[];
  showMobileMenu?: boolean;
}>();

const emit = defineEmits<{
  'toggle-mobile': [];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const searchQuery = ref('');

function handleSearch() {
  if (searchQuery.value.trim()) {
    router.get(route('contacts.index', { search: searchQuery.value }));
    searchQuery.value = '';
  }
}

function logout() {
  router.post(route('logout'));
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

    <!-- Right: User Menu -->
    <div class="flex items-center gap-2">
      <DropdownMenuRoot>
        <DropdownMenuTrigger as-child>
          <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-100 transition-colors">
            <Avatar
              :alt="user.name"
              size="sm"
            />
            <span class="hidden sm:block text-sm font-medium text-foreground">{{ user.name }}</span>
          </button>
        </DropdownMenuTrigger>

        <DropdownMenuPortal>
          <DropdownMenuContent
            align="end"
            :side-offset="4"
            class="z-50 min-w-[200px] overflow-hidden rounded-lg border border-border bg-white p-1 shadow-md"
          >
            <div class="px-2 py-1.5">
              <p class="text-sm font-medium text-foreground">{{ user.name }}</p>
              <p class="text-xs text-muted">{{ user.email }}</p>
            </div>
            <DropdownMenuSeparator class="-mx-1 my-1 h-px bg-border" />
            <DropdownMenuItem
              @select="router.get(route('profile.edit'))"
              class="relative flex cursor-pointer select-none items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none focus:bg-gray-100 focus:text-foreground"
            >
              <User class="h-4 w-4" />
              Profile
            </DropdownMenuItem>
            <DropdownMenuSeparator class="-mx-1 my-1 h-px bg-border" />
            <DropdownMenuItem
              @select="logout"
              class="relative flex cursor-pointer select-none items-center gap-2 rounded-md px-2 py-1.5 text-sm outline-none text-danger focus:bg-danger-light focus:text-danger"
            >
              <LogOut class="h-4 w-4" />
              Log Out
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenuPortal>
      </DropdownMenuRoot>
    </div>
  </header>
</template>
