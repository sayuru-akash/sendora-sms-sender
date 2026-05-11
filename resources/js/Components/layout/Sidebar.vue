<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
  LayoutDashboard,
  Users,
  List,
  Tag,
  Upload,
  FileText,
  Send,
  BarChart3,
  Activity,
  Settings,
  UserCog,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next';
import SendoraLogo from '@/Components/icons/SendoraLogo.vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  collapsed?: boolean;
}>();

const emit = defineEmits<{
  toggle: [];
}>();

const page = usePage();

interface NavItem {
  label: string;
  href: string;
  icon: typeof LayoutDashboard;
  routeName: string;
  visible?: boolean;
}

const navItems = computed<NavItem[]>(() => [
  { label: 'Dashboard', href: route('dashboard'), icon: LayoutDashboard, routeName: 'dashboard' },
  { label: 'Contacts', href: route('contacts.index'), icon: Users, routeName: 'contacts.*' },
  { label: 'Lists', href: route('lists.index'), icon: List, routeName: 'lists.*' },
  { label: 'Tags', href: route('tags.index'), icon: Tag, routeName: 'tags.*' },
  { label: 'Imports', href: route('imports.index'), icon: Upload, routeName: 'imports.*' },
  { label: 'SMS Templates', href: route('templates.index'), icon: FileText, routeName: 'templates.*' },
  { label: 'Campaigns', href: route('campaigns.index'), icon: Send, routeName: 'campaigns.*' },
  { label: 'Reports', href: route('reports.index'), icon: BarChart3, routeName: 'reports.*' },
  { label: 'Activity Logs', href: route('activity-logs.index'), icon: Activity, routeName: 'activity-logs.*' },
]);

const bottomNavItems = computed<NavItem[]>(() => {
  const items: NavItem[] = [
    { label: 'Settings', href: route('settings.index'), icon: Settings, routeName: 'settings.*' },
  ];
  const role = page.props.auth.user?.role;
  if (role === 'owner' || role === 'admin') {
    items.unshift({
      label: 'Users',
      href: route('users.index'),
      icon: UserCog,
      routeName: 'users.*',
    });
  }
  return items;
});

function isActive(routeName: string): boolean {
  return route().current(routeName);
}
</script>

<template>
  <aside
    :class="
      cn(
        'flex flex-col h-screen bg-sidebar text-white transition-all duration-300 ease-in-out',
        collapsed ? 'w-[68px]' : 'w-[260px]'
      )
    "
  >
    <!-- Logo -->
    <div class="flex items-center gap-3 px-4 h-16 border-b border-white/10 shrink-0">
      <SendoraLogo class="h-8 w-8 shrink-0" />
      <transition
        enter-active-class="transition-opacity duration-200"
        leave-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-to-class="opacity-0"
      >
        <span v-if="!collapsed" class="text-lg font-semibold tracking-tight">Sendora</span>
      </transition>
    </div>

    <!-- Main Navigation -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 scrollbar-thin">
      <ul class="space-y-1">
        <li v-for="item in navItems" :key="item.routeName">
          <Link
            :href="item.href"
            :class="
              cn(
                'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150',
                isActive(item.routeName)
                  ? 'bg-sidebar-active text-white shadow-sm'
                  : 'text-gray-300 hover:bg-sidebar-hover hover:text-white'
              )
            "
          >
            <component :is="item.icon" class="h-5 w-5 shrink-0" />
            <transition
              enter-active-class="transition-opacity duration-150"
              leave-active-class="transition-opacity duration-150"
              enter-from-class="opacity-0"
              leave-to-class="opacity-0"
            >
              <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
            </transition>
          </Link>
        </li>
      </ul>
    </nav>

    <!-- Bottom Navigation -->
    <div class="border-t border-white/10 px-3 py-3 space-y-1">
      <Link
        v-for="item in bottomNavItems"
        :key="item.routeName"
        :href="item.href"
        :class="
          cn(
            'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-150',
            isActive(item.routeName)
              ? 'bg-sidebar-active text-white shadow-sm'
              : 'text-gray-300 hover:bg-sidebar-hover hover:text-white'
          )
        "
      >
        <component :is="item.icon" class="h-5 w-5 shrink-0" />
        <transition
          enter-active-class="transition-opacity duration-150"
          leave-active-class="transition-opacity duration-150"
          enter-from-class="opacity-0"
          leave-to-class="opacity-0"
        >
          <span v-if="!collapsed" class="truncate">{{ item.label }}</span>
        </transition>
      </Link>

      <!-- Collapse Toggle -->
      <button
        @click="emit('toggle')"
        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-400 hover:bg-sidebar-hover hover:text-white transition-all duration-150 w-full"
      >
        <component
          :is="collapsed ? ChevronRight : ChevronLeft"
          class="h-5 w-5 shrink-0"
        />
        <span v-if="!collapsed" class="truncate">Collapse</span>
      </button>
    </div>
  </aside>
</template>
