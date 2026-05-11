<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Search, LogOut, User, Menu, X, ChevronDown, RefreshCw, Loader2, ArrowUpRight } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
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
const searchOpen = ref(false);
const searchLoading = ref(false);
const searchError = ref('');
const isRefreshing = ref(false);
const activeResultIndex = ref(0);
const searchGroups = ref<SearchGroup[]>([]);
let searchTimer: ReturnType<typeof setTimeout> | undefined;
let abortController: AbortController | undefined;

interface SearchResult {
  id: string;
  title: string;
  subtitle: string;
  badge: string;
  url: string;
}

interface SearchGroup {
  label: string;
  items: SearchResult[];
}

const flatResults = computed(() => searchGroups.value.flatMap((group) => group.items));

watch(searchQuery, (value) => {
  if (searchTimer) window.clearTimeout(searchTimer);

  const query = value.trim();
  searchOpen.value = query.length > 0;
  activeResultIndex.value = 0;

  if (query.length < 2) {
    searchGroups.value = [];
    searchLoading.value = false;
    searchError.value = '';
    abortController?.abort();
    return;
  }

  searchTimer = window.setTimeout(() => {
    fetchSearchResults(query);
  }, 220);
});

onBeforeUnmount(() => {
  if (searchTimer) window.clearTimeout(searchTimer);
  abortController?.abort();
});

async function fetchSearchResults(query: string) {
  abortController?.abort();
  abortController = new AbortController();
  searchLoading.value = true;
  searchError.value = '';

  try {
    const response = await fetch(route('global-search', { q: query }), {
      headers: { Accept: 'application/json' },
      signal: abortController.signal,
    });

    if (!response.ok) {
      throw new Error('Search failed');
    }

    const data = await response.json();
    searchGroups.value = data.groups ?? [];
  } catch (error) {
    if ((error as DOMException).name !== 'AbortError') {
      searchGroups.value = [];
      searchError.value = 'Search is unavailable right now.';
    }
  } finally {
    searchLoading.value = false;
  }
}

function handleSearch() {
  const query = searchQuery.value.trim();
  if (!query) return;

  if (flatResults.value[activeResultIndex.value]) {
    navigateToResult(flatResults.value[activeResultIndex.value]);
    return;
  }

  router.get(route('contacts.index', { search: query }));
  resetSearch();
}

function navigateToResult(result: SearchResult) {
  router.visit(result.url);
  resetSearch();
}

function resetSearch() {
  searchQuery.value = '';
  searchGroups.value = [];
  searchOpen.value = false;
  activeResultIndex.value = 0;
}

function closeSearchSoon() {
  window.setTimeout(() => {
    searchOpen.value = false;
  }, 120);
}

function handleSearchKeydown(event: KeyboardEvent) {
  if (!searchOpen.value || flatResults.value.length === 0) return;

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    activeResultIndex.value = (activeResultIndex.value + 1) % flatResults.value.length;
  }

  if (event.key === 'ArrowUp') {
    event.preventDefault();
    activeResultIndex.value = activeResultIndex.value === 0 ? flatResults.value.length - 1 : activeResultIndex.value - 1;
  }

  if (event.key === 'Escape') {
    searchOpen.value = false;
  }
}

function refreshPage() {
  router.reload({
    onStart: () => {
      isRefreshing.value = true;
    },
    onFinish: () => {
      isRefreshing.value = false;
    },
  });
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
          autocomplete="off"
          class="h-9 w-full rounded-lg border border-border bg-gray-50 pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 focus:bg-white transition-colors"
          @focus="searchOpen = searchQuery.trim().length > 0"
          @blur="closeSearchSoon"
          @keydown="handleSearchKeydown"
        />
        <div
          v-if="searchOpen"
          class="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-border bg-white shadow-xl"
        >
          <div v-if="searchQuery.trim().length < 2" class="px-4 py-3 text-xs text-muted">
            Type at least 2 characters.
          </div>
          <div v-else-if="searchLoading" class="flex items-center gap-2 px-4 py-3 text-xs text-muted">
            <Loader2 class="h-3.5 w-3.5 animate-spin" />
            Searching...
          </div>
          <div v-else-if="searchError" class="px-4 py-3 text-xs text-danger">
            {{ searchError }}
          </div>
          <div v-else-if="searchGroups.length === 0" class="px-4 py-3 text-xs text-muted">
            No matching records.
          </div>
          <div v-else class="max-h-[420px] overflow-y-auto py-2">
            <div v-for="group in searchGroups" :key="group.label" class="py-1">
              <p class="px-3 pb-1 text-[11px] font-semibold uppercase tracking-wide text-muted">{{ group.label }}</p>
              <button
                v-for="item in group.items"
                :key="item.id"
                type="button"
                :class="
                  cn(
                    'flex w-full items-center justify-between gap-3 px-3 py-2 text-left transition-colors',
                    flatResults[activeResultIndex]?.id === item.id ? 'bg-primary/5' : 'hover:bg-gray-50'
                  )
                "
                @mousedown.prevent="navigateToResult(item)"
              >
                <span class="min-w-0">
                  <span class="block truncate text-sm font-medium text-foreground">{{ item.title }}</span>
                  <span class="mt-0.5 block truncate text-xs text-muted">{{ item.subtitle }}</span>
                </span>
                <span class="flex shrink-0 items-center gap-2">
                  <span class="max-w-24 truncate rounded-full border border-border bg-gray-50 px-2 py-0.5 text-[11px] capitalize text-muted">
                    {{ item.badge }}
                  </span>
                  <ArrowUpRight class="h-3.5 w-3.5 text-muted-foreground" />
                </span>
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Right: User Menu (simple CSS dropdown) -->
    <div class="flex items-center gap-2">
      <Button
        variant="ghost"
        size="icon-sm"
        class="text-muted-foreground hover:text-foreground"
        :disabled="isRefreshing"
        title="Refresh page"
        aria-label="Refresh page"
        @click="refreshPage"
      >
        <RefreshCw :class="cn('h-4 w-4', isRefreshing && 'animate-spin')" />
      </Button>

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
    </div>
  </header>
</template>
