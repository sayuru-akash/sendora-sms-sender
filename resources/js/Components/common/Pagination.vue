<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import type { Pagination as PaginationType } from '@/types';

const props = defineProps<{
  meta: PaginationType;
}>();

const pages = computed(() => {
  const total = props.meta.last_page;
  const current = props.meta.current_page;
  const delta = 2;
  const pages: (number | string)[] = [];

  for (let i = 1; i <= total; i++) {
    if (
      i === 1 ||
      i === total ||
      (i >= current - delta && i <= current + delta)
    ) {
      pages.push(i);
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...');
    }
  }

  return pages;
});

function paginationHref(page: number | string): string {
  const currentRouteName = route().current();

  if (currentRouteName) {
    return route(currentRouteName, { ...route().params, page });
  }

  const url = new URL(window.location.href);
  url.searchParams.set('page', String(page));

  return `${url.pathname}${url.search}${url.hash}`;
}
</script>

<template>
  <div v-if="meta.last_page > 1" class="flex items-center justify-between px-2 py-4">
    <p class="text-sm text-muted">
      Showing <span class="font-medium text-foreground">{{ meta.from }}</span>
      to <span class="font-medium text-foreground">{{ meta.to }}</span>
      of <span class="font-medium text-foreground">{{ meta.total }}</span> results
    </p>

    <nav class="flex items-center gap-1">
      <Link
        v-if="meta.current_page > 1"
        :href="paginationHref(meta.current_page - 1)"
        preserve-scroll
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-white text-sm text-foreground hover:bg-gray-50 transition-colors"
      >
        <ChevronLeft class="h-4 w-4" />
      </Link>
      <span v-else class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-gray-50 text-sm text-muted-foreground cursor-not-allowed">
        <ChevronLeft class="h-4 w-4" />
      </span>

      <template v-for="(page, index) in pages" :key="index">
        <span
          v-if="page === '...'"
          class="inline-flex h-9 w-9 items-center justify-center text-sm text-muted"
        >
          ...
        </span>
        <Link
          v-else
          :href="paginationHref(page)"
          preserve-scroll
          :class="
            cn(
              'inline-flex h-9 w-9 items-center justify-center rounded-lg text-sm font-medium transition-colors',
              page === meta.current_page
                ? 'bg-primary text-white shadow-sm'
                : 'border border-border bg-white text-foreground hover:bg-gray-50'
            )
          "
        >
          {{ page }}
        </Link>
      </template>

      <Link
        v-if="meta.current_page < meta.last_page"
        :href="paginationHref(meta.current_page + 1)"
        preserve-scroll
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-white text-sm text-foreground hover:bg-gray-50 transition-colors"
      >
        <ChevronRight class="h-4 w-4" />
      </Link>
      <span v-else class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-gray-50 text-sm text-muted-foreground cursor-not-allowed">
        <ChevronRight class="h-4 w-4" />
      </span>
    </nav>
  </div>
</template>
