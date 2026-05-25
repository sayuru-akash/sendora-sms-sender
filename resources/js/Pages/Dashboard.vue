<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatCard from '@/Components/common/StatCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import { Link } from '@inertiajs/vue3';
import {
  Users,
  Send,
  AlertTriangle,
  Zap,
  UserPlus,
  Upload,
  Megaphone,
  FileText,
  ArrowRight,
  Clock,
} from 'lucide-vue-next';
import { Head } from '@inertiajs/vue3';
import { formatNumber, formatDate, formatRelativeTime } from '@/lib/utils';
import type { DashboardProps } from '@/types';
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart, BarChart } from 'echarts/charts';
import {
  TitleComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
} from 'echarts/components';

use([CanvasRenderer, LineChart, BarChart, TitleComponent, TooltipComponent, GridComponent, LegendComponent]);

const props = defineProps<Partial<DashboardProps>>();

const emptyStats: DashboardProps['stats'] = {
  total_contacts: 0,
  sms_sent_this_month: 0,
  failed_sms: 0,
  active_campaigns: 0,
};

const safeStats = computed(() => props.stats ?? emptyStats);
const contactGrowth = computed(() => props.contact_growth ?? []);
const campaignPerformance = computed(() => props.campaign_performance ?? []);
const recentCampaigns = computed(() => props.recent_campaigns ?? []);
const recentImports = computed(() => props.recent_imports ?? []);
const topLists = computed(() => props.top_lists ?? []);
const activityLog = computed(() => props.activity_log ?? []);
const activityLogTotal = computed(() => props.activity_log_total ?? activityLog.value.length);

const contactGrowthOption = computed(() => ({
  tooltip: {
    trigger: 'axis',
    backgroundColor: '#fff',
    borderColor: '#e5e7eb',
    textStyle: { color: '#1a1a1a', fontSize: 12 },
  },
  grid: { top: 10, right: 16, bottom: 24, left: 48 },
  xAxis: {
    type: 'category',
    data: contactGrowth.value.map((d) => d.month),
    axisLine: { lineStyle: { color: '#e5e7eb' } },
    axisTick: { show: false },
    axisLabel: { color: '#6b7280', fontSize: 11 },
  },
  yAxis: {
    type: 'value',
    axisLine: { show: false },
    axisTick: { show: false },
    splitLine: { lineStyle: { color: '#f3f4f6' } },
    axisLabel: { color: '#6b7280', fontSize: 11 },
  },
  series: [
    {
      type: 'line',
      data: contactGrowth.value.map((d) => d.count),
      smooth: true,
      lineStyle: { color: '#4f46e5', width: 2 },
      areaStyle: {
        color: {
          type: 'linear',
          x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(79,70,229,0.15)' },
            { offset: 1, color: 'rgba(79,70,229,0)' },
          ],
        },
      },
      itemStyle: { color: '#4f46e5' },
      symbol: 'circle',
      symbolSize: 6,
    },
  ],
}));

const campaignPerformanceOption = computed(() => ({
  tooltip: {
    trigger: 'axis',
    backgroundColor: '#fff',
    borderColor: '#e5e7eb',
    textStyle: { color: '#1a1a1a', fontSize: 12 },
  },
  legend: {
    data: ['Sent', 'Failed'],
    bottom: 0,
    textStyle: { color: '#6b7280', fontSize: 11 },
  },
  grid: { top: 10, right: 16, bottom: 36, left: 48 },
  xAxis: {
    type: 'category',
    data: campaignPerformance.value.map((d) => d.name),
    axisLine: { lineStyle: { color: '#e5e7eb' } },
    axisTick: { show: false },
    axisLabel: { color: '#6b7280', fontSize: 11, rotate: 20 },
  },
  yAxis: {
    type: 'value',
    axisLine: { show: false },
    axisTick: { show: false },
    splitLine: { lineStyle: { color: '#f3f4f6' } },
    axisLabel: { color: '#6b7280', fontSize: 11 },
  },
  series: [
    {
      name: 'Sent',
      type: 'bar',
      data: campaignPerformance.value.map((d) => d.sent),
      itemStyle: { color: '#10b981', borderRadius: [4, 4, 0, 0] },
      barWidth: '35%',
    },
    {
      name: 'Failed',
      type: 'bar',
      data: campaignPerformance.value.map((d) => d.failed),
      itemStyle: { color: '#ef4444', borderRadius: [4, 4, 0, 0] },
      barWidth: '35%',
    },
  ],
}));

const hasContactGrowthData = computed(() => contactGrowth.value.some((item) => item.count > 0));
const hasCampaignPerformanceData = computed(() => campaignPerformance.value.some((item) => item.sent > 0 || item.failed > 0));

const quickActions = [
  { label: 'Add Contact', icon: UserPlus, href: route('contacts.create'), color: 'bg-indigo-50 text-indigo-600' },
  { label: 'Import Contacts', icon: Upload, href: route('imports.create'), color: 'bg-emerald-50 text-emerald-600' },
  { label: 'Create Campaign', icon: Megaphone, href: route('campaigns.builder'), color: 'bg-amber-50 text-amber-600' },
  { label: 'Create Template', icon: FileText, href: route('templates.create'), color: 'bg-sky-50 text-sky-600' },
];
</script>

<template>
  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="[{ label: 'Dashboard' }]">
    <PageHeader title="Dashboard" subtitle="Welcome back. Here's your overview.">
      <template #actions>
        <Link :href="route('campaigns.builder')">
          <Button>
            <Send class="h-4 w-4" />
            New Campaign
          </Button>
        </Link>
      </template>
    </PageHeader>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-8">
      <Link
        v-for="action in quickActions"
        :key="action.label"
        :href="action.href"
        class="flex items-center gap-3 rounded-xl border border-border bg-white p-4 hover:shadow-sm transition-all duration-200 group"
      >
        <div :class="['flex h-10 w-10 items-center justify-center rounded-xl shrink-0', action.color]">
          <component :is="action.icon" class="h-5 w-5" />
        </div>
        <span class="text-sm font-medium text-foreground group-hover:text-primary transition-colors">
          {{ action.label }}
        </span>
      </Link>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      <StatCard
        label="Total Contacts"
        :value="safeStats.total_contacts"
        :icon="Users"
        icon-color="text-primary"
        icon-bg="bg-primary-light"
      />
      <StatCard
        label="SMS Sent This Month"
        :value="safeStats.sms_sent_this_month"
        :icon="Send"
        icon-color="text-success"
        icon-bg="bg-success-light"
      />
      <StatCard
        label="Failed SMS"
        :value="safeStats.failed_sms"
        :icon="AlertTriangle"
        icon-color="text-danger"
        icon-bg="bg-danger-light"
      />
      <StatCard
        label="Active Campaigns"
        :value="safeStats.active_campaigns"
        :icon="Zap"
        icon-color="text-warning"
        icon-bg="bg-warning-light"
      />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
      <Card>
        <CardHeader>
          <CardTitle>Contact Growth</CardTitle>
        </CardHeader>
        <CardContent>
          <VChart
            v-if="hasContactGrowthData"
            :option="contactGrowthOption"
            :autoresize="true"
            style="height: 280px"
          />
          <div v-else role="status" class="flex h-[280px] flex-col items-center justify-center rounded-lg border border-dashed border-border bg-gray-50/60 px-6 text-center">
            <Users class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">No contact growth yet</p>
            <p class="mt-1 text-xs text-muted">Imported or created contacts will appear here.</p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Campaign Performance</CardTitle>
        </CardHeader>
        <CardContent>
          <VChart
            v-if="hasCampaignPerformanceData"
            :option="campaignPerformanceOption"
            :autoresize="true"
            style="height: 280px"
          />
          <div v-else role="status" class="flex h-[280px] flex-col items-center justify-center rounded-lg border border-dashed border-border bg-gray-50/60 px-6 text-center">
            <Send class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">No campaign performance yet</p>
            <p class="mt-1 text-xs text-muted">Sent campaign results will appear here.</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Data Rows -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
      <!-- Recent Campaigns -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle>Recent Campaigns</CardTitle>
          <Link :href="route('campaigns.index')" class="text-xs text-primary hover:text-primary-hover">
            View all →
          </Link>
        </CardHeader>
        <CardContent>
          <div v-if="recentCampaigns.length === 0" class="py-8 text-center text-sm text-muted">
            No campaigns yet
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="campaign in recentCampaigns"
              :key="campaign.id"
              class="flex items-center justify-between py-2 border-b border-border last:border-0"
            >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-foreground truncate">{{ campaign.name }}</p>
                <p class="text-xs text-muted">{{ formatDate(campaign.created_at) }}</p>
              </div>
              <div class="flex items-center gap-2 ml-3">
                <StatusBadge :status="campaign.status" />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Recent Imports -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle>Recent Imports</CardTitle>
          <Link :href="route('imports.index')" class="text-xs text-primary hover:text-primary-hover">
            View all →
          </Link>
        </CardHeader>
        <CardContent>
          <div v-if="recentImports.length === 0" class="py-8 text-center text-sm text-muted">
            No imports yet
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="imp in recentImports"
              :key="imp.id"
              class="flex items-center justify-between py-2 border-b border-border last:border-0"
            >
              <div class="min-w-0 flex-1">
                <p class="text-sm font-medium text-foreground truncate">{{ imp.original_filename }}</p>
                <p class="text-xs text-muted">{{ formatNumber(imp.successful_rows) }} / {{ formatNumber(imp.total_rows) }} contacts</p>
              </div>
              <StatusBadge :status="imp.status" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Top Lists -->
      <Card>
        <CardHeader class="flex flex-row items-center justify-between">
          <CardTitle>Top Lists</CardTitle>
          <Link :href="route('lists.index')" class="text-xs text-primary hover:text-primary-hover">
            View all →
          </Link>
        </CardHeader>
        <CardContent>
          <div v-if="topLists.length === 0" class="py-8 text-center text-sm text-muted">
            No lists yet
          </div>
          <div v-else class="space-y-3">
            <div
              v-for="list in topLists"
              :key="list.id"
              class="flex items-center justify-between py-2 border-b border-border last:border-0"
            >
              <div class="flex items-center gap-2 min-w-0">
                <div class="h-2.5 w-2.5 rounded-full shrink-0" :style="{ backgroundColor: list.color }" />
                <p class="text-sm font-medium text-foreground truncate">{{ list.name }}</p>
              </div>
              <span class="text-sm text-muted ml-2">{{ formatNumber(list.contacts_count) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Activity Log -->
    <Card>
      <CardHeader class="flex flex-row items-center justify-between">
        <div>
          <CardTitle>Recent Activity</CardTitle>
          <p v-if="activityLogTotal > activityLog.length" class="mt-1 text-xs text-muted">
            Latest {{ activityLog.length }} of {{ formatNumber(activityLogTotal) }}
          </p>
        </div>
        <Link :href="route('activity-logs.index')" class="text-xs text-primary hover:text-primary-hover">
          View all →
        </Link>
      </CardHeader>
      <CardContent>
        <div v-if="activityLog.length === 0" class="py-8 text-center text-sm text-muted">
          No recent activity
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="log in activityLog"
            :key="log.id"
            class="flex items-start gap-3 py-2 border-b border-border last:border-0"
          >
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 shrink-0 mt-0.5">
              <Clock class="h-4 w-4 text-muted-foreground" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="text-sm text-foreground">{{ log.description }}</p>
              <div class="flex flex-wrap items-center gap-2 mt-0.5">
                <span v-if="log.causer_name" class="text-xs text-muted">{{ log.causer_name }}</span>
                <span class="text-xs text-muted-foreground">{{ formatRelativeTime(log.created_at) }}</span>
                <Link
                  v-if="log.subject_url"
                  :href="log.subject_url"
                  class="text-xs font-medium text-primary hover:text-primary-hover"
                >
                  {{ log.subject_action_label ?? 'Open' }}
                </Link>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </AppLayout>
</template>
