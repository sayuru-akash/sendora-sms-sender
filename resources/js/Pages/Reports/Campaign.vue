<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatCard from '@/Components/common/StatCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
  Users,
  CheckCircle,
  XCircle,
  SkipForward,
  ArrowLeft,
  Percent,
} from 'lucide-vue-next';
import type { Campaign } from '@/types/campaign';
import type { SmsRecord } from '@/types';
import { formatNumber, formatDateTime } from '@/lib/utils';
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart, BarChart, PieChart } from 'echarts/charts';
import {
  TitleComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
} from 'echarts/components';

use([CanvasRenderer, LineChart, BarChart, PieChart, TitleComponent, TooltipComponent, GridComponent, LegendComponent]);

interface Props {
  campaign: Campaign;
  status_counts: {
    sent: number;
    failed: number;
    skipped: number;
    pending: number;
    delivered?: number;
  };
  hourly_data: { hour: string; count: number }[];
  failed_messages: SmsRecord[];
}

const props = defineProps<Props>();

const totalRecipients = computed(() => {
  return Object.values(props.status_counts).reduce((sum, count) => sum + count, 0);
});

const successRate = computed(() => {
  if (totalRecipients.value === 0) return 0;
  const sent = props.status_counts.sent + (props.status_counts.delivered ?? 0);
  return Math.round((sent / totalRecipients.value) * 100);
});

const statCards = computed(() => [
  {
    label: 'Total Recipients',
    value: totalRecipients.value,
    icon: Users,
    iconColor: 'text-primary',
    iconBg: 'bg-primary-light',
  },
  {
    label: 'Sent',
    value: props.status_counts.sent,
    icon: CheckCircle,
    iconColor: 'text-success',
    iconBg: 'bg-success-light',
  },
  {
    label: 'Failed',
    value: props.status_counts.failed,
    icon: XCircle,
    iconColor: 'text-danger',
    iconBg: 'bg-danger-light',
  },
  {
    label: 'Skipped',
    value: props.status_counts.skipped,
    icon: SkipForward,
    iconColor: 'text-warning',
    iconBg: 'bg-warning-light',
  },
  {
    label: 'Success Rate',
    value: successRate.value,
    icon: Percent,
    iconColor: 'text-success',
    iconBg: 'bg-success-light',
    format: 'percent' as const,
  },
]);

const hourlyChartOption = computed(() => ({
  tooltip: {
    trigger: 'axis',
    backgroundColor: '#fff',
    borderColor: '#e5e7eb',
    textStyle: { color: '#1a1a1a', fontSize: 12 },
    formatter: (params: { name: string; value: number }[]) => {
      const point = params[0];
      return `<strong>${point.name}</strong><br/>Messages: ${formatNumber(point.value)}`;
    },
  },
  grid: { top: 10, right: 16, bottom: 24, left: 48 },
  xAxis: {
    type: 'category',
    data: props.hourly_data.map((d) => d.hour),
    axisLine: { lineStyle: { color: '#e5e7eb' } },
    axisTick: { show: false },
    axisLabel: { color: '#6b7280', fontSize: 11, rotate: 30 },
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
      type: 'bar',
      data: props.hourly_data.map((d) => d.count),
      itemStyle: {
        color: '#4f46e5',
        borderRadius: [4, 4, 0, 0],
      },
      barWidth: '60%',
    },
  ],
}));

const statusColors: Record<string, string> = {
  sent: '#10b981',
  delivered: '#059669',
  failed: '#ef4444',
  skipped: '#f59e0b',
  pending: '#d1d5db',
};

const statusChartOption = computed(() => ({
  tooltip: {
    trigger: 'item',
    backgroundColor: '#fff',
    borderColor: '#e5e7eb',
    textStyle: { color: '#1a1a1a', fontSize: 12 },
    formatter: '{b}: {c} ({d}%)',
  },
  legend: {
    bottom: 0,
    textStyle: { color: '#6b7280', fontSize: 11 },
  },
  series: [
    {
      type: 'pie',
      radius: ['45%', '70%'],
      avoidLabelOverlap: false,
      label: { show: false },
      data: Object.entries(props.status_counts)
        .filter(([, value]) => value > 0)
        .map(([key, value]) => ({
          value,
          name: key.charAt(0).toUpperCase() + key.slice(1),
          itemStyle: { color: statusColors[key] ?? '#6b7280' },
        })),
    },
  ],
}));
</script>

<template>
  <Head :title="`Report: ${campaign.name}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Reports', href: route('reports.index') },
    { label: campaign.name },
  ]">
    <PageHeader
      :title="campaign.name"
      :subtitle="`Campaign report \u2022 ${campaign.status.charAt(0).toUpperCase() + campaign.status.slice(1)}`"
    >
      <template #actions>
        <Link :href="route('campaigns.show', campaign.id)">
          <Button variant="outline">
            <ArrowLeft class="h-4 w-4" />
            Back to Campaign
          </Button>
        </Link>
      </template>
    </PageHeader>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
      <StatCard
        v-for="stat in statCards"
        :key="stat.label"
        :label="stat.label"
        :value="stat.value"
        :icon="stat.icon"
        :icon-color="stat.iconColor"
        :icon-bg="stat.iconBg"
        :format="stat.format"
      />
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <Card>
        <CardHeader>
          <CardTitle>Messages Sent Over Time</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="hourly_data.length === 0" class="flex h-[300px] items-center justify-center text-sm text-muted">
            No hourly data available yet.
          </div>
          <VChart
            v-else
            :option="hourlyChartOption"
            :autoresize="true"
            style="height: 300px"
          />
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Status Breakdown</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="totalRecipients === 0" class="flex h-[300px] items-center justify-center text-sm text-muted">
            No status data available yet.
          </div>
          <VChart
            v-else
            :option="statusChartOption"
            :autoresize="true"
            style="height: 300px"
          />
        </CardContent>
      </Card>
    </div>

    <!-- Failed Messages Table -->
    <Card>
      <CardHeader>
        <div class="flex items-center justify-between">
          <CardTitle>Failed Messages</CardTitle>
          <Badge variant="danger">{{ formatNumber(failed_messages.length) }} failed</Badge>
        </div>
      </CardHeader>
      <CardContent>
        <div v-if="failed_messages.length === 0" class="py-8 text-center text-sm text-muted">
          No failed messages. All messages were sent successfully.
        </div>
        <div v-else class="rounded-xl border border-border overflow-hidden">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-border bg-gray-50/50">
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Contact</th>
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Phone</th>
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Error</th>
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Sent At</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="message in failed_messages"
                :key="message.id"
                class="border-b border-border last:border-0 hover:bg-gray-50/50 transition-colors"
              >
                <td class="px-4 py-3 text-sm font-medium text-foreground">
                  {{ message.contact_name }}
                </td>
                <td class="px-4 py-3 text-sm text-foreground">
                  {{ message.contact_phone }}
                </td>
                <td class="px-4 py-3">
                  <span class="text-sm text-danger">
                    {{ message.error_message ?? 'Unknown error' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-muted">
                  {{ message.sent_at ? formatDateTime(message.sent_at) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </AppLayout>
</template>
