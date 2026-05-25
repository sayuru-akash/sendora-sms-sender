<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import StatCard from '@/Components/common/StatCard.vue';
import { Head } from '@inertiajs/vue3';
import { Users, Send, CheckCircle, XCircle } from 'lucide-vue-next';
import VChart from 'vue-echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { LineChart, BarChart, PieChart } from 'echarts/charts';
import { TitleComponent, TooltipComponent, GridComponent, LegendComponent } from 'echarts/components';
import { formatNumber } from '@/lib/utils';

use([CanvasRenderer, LineChart, BarChart, PieChart, TitleComponent, TooltipComponent, GridComponent, LegendComponent]);

interface ReportStats {
  total_contacts: number;
  total_sms_sent: number;
  total_campaigns: number;
  avg_success_rate: number;
}

const props = defineProps<{
  stats?: ReportStats;
  sms_over_time?: { month: string; sent: number; failed: number }[];
  contacts_by_source?: { source: string; count: number }[];
  contacts_by_status?: { status: string; count: number }[];
  top_lists?: { name: string; count: number }[];
  top_tags?: { name: string; count: number }[];
}>();

const emptyStats: ReportStats = {
  total_contacts: 0,
  total_sms_sent: 0,
  total_campaigns: 0,
  avg_success_rate: 0,
};

const safeStats = computed(() => props.stats ?? emptyStats);
const smsOverTime = computed(() => props.sms_over_time ?? []);
const contactsBySource = computed(() => props.contacts_by_source ?? []);
const contactsByStatus = computed(() => props.contacts_by_status ?? []);
const topLists = computed(() => props.top_lists ?? []);
const topTags = computed(() => props.top_tags ?? []);

const smsChartOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  legend: { data: ['Sent', 'Failed'], bottom: 0, textStyle: { color: '#6b7280', fontSize: 11 } },
  grid: { top: 10, right: 16, bottom: 36, left: 48 },
  xAxis: { type: 'category', data: smsOverTime.value.map((d) => d.month), axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  yAxis: { type: 'value', axisLine: { show: false }, axisTick: { show: false }, splitLine: { lineStyle: { color: '#f3f4f6' } }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  series: [
    { name: 'Sent', type: 'line', data: smsOverTime.value.map((d) => d.sent), smooth: true, lineStyle: { color: '#4f46e5', width: 2 }, itemStyle: { color: '#4f46e5' }, areaStyle: { color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: 'rgba(79,70,229,0.15)' }, { offset: 1, color: 'rgba(79,70,229,0)' }] } } },
    { name: 'Failed', type: 'line', data: smsOverTime.value.map((d) => d.failed), smooth: true, lineStyle: { color: '#ef4444', width: 2 }, itemStyle: { color: '#ef4444' } },
  ],
}));

const sourceChartOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  grid: { top: 10, right: 16, bottom: 24, left: 80 },
  xAxis: { type: 'value', axisLine: { show: false }, axisTick: { show: false }, splitLine: { lineStyle: { color: '#f3f4f6' } }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  yAxis: { type: 'category', data: contactsBySource.value.map((d) => d.source), axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  series: [{ type: 'bar', data: contactsBySource.value.map((d) => d.count), itemStyle: { color: '#4f46e5', borderRadius: [0, 4, 4, 0] }, barWidth: '50%' }],
}));

const statusChartOption = computed(() => ({
  tooltip: { trigger: 'item', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  legend: { bottom: 0, textStyle: { color: '#6b7280', fontSize: 11 } },
  series: [{
    type: 'pie', radius: ['45%', '70%'],
    label: { show: false },
    data: contactsByStatus.value.map((d, i) => ({
      value: d.count, name: d.status,
      itemStyle: { color: ['#10b981', '#6b7280', '#f59e0b', '#ef4444', '#dc2626'][i] || '#6b7280' },
    })),
  }],
}));

const listChartOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  grid: { top: 10, right: 16, bottom: 24, left: 120 },
  xAxis: { type: 'value', axisLine: { show: false }, axisTick: { show: false }, splitLine: { lineStyle: { color: '#f3f4f6' } }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  yAxis: { type: 'category', data: topLists.value.map((d) => d.name), axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  series: [{ type: 'bar', data: topLists.value.map((d) => d.count), itemStyle: { color: '#4f46e5', borderRadius: [0, 4, 4, 0] }, barWidth: '50%' }],
}));

const hasSmsChartData = computed(() => smsOverTime.value.some((item) => item.sent > 0 || item.failed > 0));
const hasSourceChartData = computed(() => contactsBySource.value.some((item) => item.count > 0));
const hasStatusChartData = computed(() => contactsByStatus.value.some((item) => item.count > 0));
const hasListChartData = computed(() => topLists.value.some((item) => item.count > 0));
const hasTagListData = computed(() => topTags.value.some((item) => item.count > 0));
</script>

<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="[{ label: 'Reports' }]">
    <PageHeader title="Reports" subtitle="Analytics and insights for your SMS campaigns." />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard label="Total Contacts" :value="safeStats.total_contacts" :icon="Users" icon-color="text-primary" icon-bg="bg-primary-light" />
      <StatCard label="Total SMS Sent" :value="safeStats.total_sms_sent" :icon="Send" icon-color="text-success" icon-bg="bg-success-light" />
      <StatCard label="Total Campaigns" :value="safeStats.total_campaigns" :icon="CheckCircle" icon-color="text-info" icon-bg="bg-info-light" />
      <StatCard label="Avg Success Rate" :value="safeStats.avg_success_rate" :icon="CheckCircle" icon-color="text-success" icon-bg="bg-success-light" format="percent" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <Card>
        <CardHeader><CardTitle>SMS Sent Over Time</CardTitle></CardHeader>
        <CardContent>
          <VChart v-if="hasSmsChartData" :option="smsChartOption" :autoresize="true" style="height: 300px" />
          <div v-else role="status" class="flex h-[300px] flex-col items-center justify-center rounded-lg border border-dashed border-border bg-gray-50/60 px-6 text-center">
            <Send class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">No SMS activity yet</p>
            <p class="mt-1 text-xs text-muted">Monthly send and failure trends will appear here.</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Contacts by Source</CardTitle></CardHeader>
        <CardContent>
          <VChart v-if="hasSourceChartData" :option="sourceChartOption" :autoresize="true" style="height: 300px" />
          <div v-else role="status" class="flex h-[300px] flex-col items-center justify-center rounded-lg border border-dashed border-border bg-gray-50/60 px-6 text-center">
            <Users class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">No contact sources yet</p>
            <p class="mt-1 text-xs text-muted">Imported and manually added contacts will be grouped here.</p>
          </div>
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card>
        <CardHeader><CardTitle>Contacts by Status</CardTitle></CardHeader>
        <CardContent>
          <VChart v-if="hasStatusChartData" :option="statusChartOption" :autoresize="true" style="height: 280px" />
          <div v-else role="status" class="flex h-[280px] flex-col items-center justify-center rounded-lg border border-dashed border-border bg-gray-50/60 px-6 text-center">
            <CheckCircle class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">No contact statuses yet</p>
            <p class="mt-1 text-xs text-muted">Active, unsubscribed, and blocked counts will appear here.</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Top Lists by Size</CardTitle></CardHeader>
        <CardContent>
          <VChart v-if="hasListChartData" :option="listChartOption" :autoresize="true" style="height: 280px" />
          <div v-else role="status" class="flex h-[280px] flex-col items-center justify-center rounded-lg border border-dashed border-border bg-gray-50/60 px-6 text-center">
            <Users class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">No list data yet</p>
            <p class="mt-1 text-xs text-muted">Lists with contacts will appear here.</p>
          </div>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Top Tags by Size</CardTitle></CardHeader>
        <CardContent>
          <div v-if="hasTagListData" class="min-h-[280px] space-y-3">
            <div v-for="tag in topTags" :key="tag.name" class="flex items-center justify-between">
              <span class="text-sm text-foreground">{{ tag.name }}</span>
              <span class="text-sm text-muted font-medium">{{ formatNumber(tag.count) }}</span>
            </div>
          </div>
          <div v-else role="status" class="flex h-[280px] flex-col items-center justify-center rounded-lg border border-dashed border-border bg-gray-50/60 px-6 text-center">
            <XCircle class="mb-3 h-8 w-8 text-muted-foreground" />
            <p class="text-sm font-medium text-foreground">No tag data yet</p>
            <p class="mt-1 text-xs text-muted">Tags with contacts will appear here.</p>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
