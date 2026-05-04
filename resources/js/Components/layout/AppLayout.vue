<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/layout/Sidebar.vue';
import Topbar from '@/Components/layout/Topbar.vue';
import ConfirmDialog from '@/Components/common/ConfirmDialog.vue';
import { Toaster, toast } from 'vue-sonner';
import type { BreadcrumbItem } from '@/types';
import { useConfirm } from '@/composables/useConfirm';

defineProps<{
  title?: string;
  breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const sidebarCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const { isOpen: confirmOpen, options: confirmOptions, handleConfirm, handleCancel } = useConfirm();

// Flash messages
watch(
  () => page.props.flash,
  (flash) => {
    if (flash?.success) toast.success(flash.success);
    if (flash?.error) toast.error(flash.error);
    if (flash?.info) toast.info(flash.info);
  },
  { immediate: true }
);

function toggleSidebar() {
  sidebarCollapsed.value = !sidebarCollapsed.value;
}

function toggleMobileMenu() {
  mobileMenuOpen.value = !mobileMenuOpen.value;
}
</script>

<template>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar (Desktop) -->
    <div class="hidden lg:block shrink-0">
      <Sidebar :collapsed="sidebarCollapsed" @toggle="toggleSidebar" />
    </div>

    <!-- Sidebar Overlay (Mobile) -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-opacity duration-300"
        leave-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        leave-to-class="opacity-0"
      >
        <div
          v-if="mobileMenuOpen"
          class="fixed inset-0 z-40 bg-black/50 lg:hidden"
          @click="mobileMenuOpen = false"
        />
      </Transition>
      <Transition
        enter-active-class="transition-transform duration-300 ease-out"
        leave-active-class="transition-transform duration-300 ease-in"
        enter-from-class="-translate-x-full"
        leave-to-class="-translate-x-full"
      >
        <div v-if="mobileMenuOpen" class="fixed inset-y-0 left-0 z-50 lg:hidden">
          <Sidebar @toggle="mobileMenuOpen = false" />
        </div>
      </Transition>
    </Teleport>

    <!-- Main Content -->
    <div class="flex flex-1 flex-col overflow-hidden">
      <Topbar
        :breadcrumbs="breadcrumbs"
        :show-mobile-menu="mobileMenuOpen"
        @toggle-mobile="toggleMobileMenu"
      />

      <main class="flex-1 overflow-y-auto">
        <div class="px-4 py-6 lg:px-8 max-w-7xl mx-auto">
          <slot />
        </div>
      </main>
    </div>

    <!-- Toast -->
    <Toaster
      position="top-right"
      :toast-options="{
        style: { fontFamily: 'Inter, sans-serif', fontSize: '14px' },
        class: 'shadow-lg',
      }"
      rich-colors
    />

    <!-- Global Confirm Dialog -->
    <ConfirmDialog
      :open="confirmOpen"
      :title="confirmOptions.title"
      :message="confirmOptions.message"
      :confirm-label="confirmOptions.confirmLabel"
      :cancel-label="confirmOptions.cancelLabel"
      :variant="confirmOptions.variant"
      @confirm="handleConfirm"
      @cancel="handleCancel"
      @update:open="(val: boolean) => !val && handleCancel()"
    />
  </div>
</template>
