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

const props = defineProps<{
  stats: {
    total_contacts: number;
    total_sms_sent: number;
    total_campaigns: number;
    avg_success_rate: number;
  };
  sms_over_time: { month: string; sent: number; failed: number }[];
  contacts_by_source: { source: string; count: number }[];
  contacts_by_status: { status: string; count: number }[];
  top_lists: { name: string; count: number }[];
  top_tags: { name: string; count: number }[];
}>();

const smsChartOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  legend: { data: ['Sent', 'Failed'], bottom: 0, textStyle: { color: '#6b7280', fontSize: 11 } },
  grid: { top: 10, right: 16, bottom: 36, left: 48 },
  xAxis: { type: 'category', data: props.sms_over_time.map((d) => d.month), axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  yAxis: { type: 'value', axisLine: { show: false }, axisTick: { show: false }, splitLine: { lineStyle: { color: '#f3f4f6' } }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  series: [
    { name: 'Sent', type: 'line', data: props.sms_over_time.map((d) => d.sent), smooth: true, lineStyle: { color: '#4f46e5', width: 2 }, itemStyle: { color: '#4f46e5' }, areaStyle: { color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: 'rgba(79,70,229,0.15)' }, { offset: 1, color: 'rgba(79,70,229,0)' }] } } },
    { name: 'Failed', type: 'line', data: props.sms_over_time.map((d) => d.failed), smooth: true, lineStyle: { color: '#ef4444', width: 2 }, itemStyle: { color: '#ef4444' } },
  ],
}));

const sourceChartOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  grid: { top: 10, right: 16, bottom: 24, left: 80 },
  xAxis: { type: 'value', axisLine: { show: false }, axisTick: { show: false }, splitLine: { lineStyle: { color: '#f3f4f6' } }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  yAxis: { type: 'category', data: props.contacts_by_source.map((d) => d.source), axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  series: [{ type: 'bar', data: props.contacts_by_source.map((d) => d.count), itemStyle: { color: '#4f46e5', borderRadius: [0, 4, 4, 0] }, barWidth: '50%' }],
}));

const statusChartOption = computed(() => ({
  tooltip: { trigger: 'item', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  legend: { bottom: 0, textStyle: { color: '#6b7280', fontSize: 11 } },
  series: [{
    type: 'pie', radius: ['45%', '70%'],
    label: { show: false },
    data: props.contacts_by_status.map((d, i) => ({
      value: d.count, name: d.status,
      itemStyle: { color: ['#10b981', '#6b7280', '#f59e0b', '#ef4444', '#dc2626'][i] || '#6b7280' },
    })),
  }],
}));

const listChartOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  grid: { top: 10, right: 16, bottom: 24, left: 120 },
  xAxis: { type: 'value', axisLine: { show: false }, axisTick: { show: false }, splitLine: { lineStyle: { color: '#f3f4f6' } }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  yAxis: { type: 'category', data: props.top_lists.map((d) => d.name), axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  series: [{ type: 'bar', data: props.top_lists.map((d) => d.count), itemStyle: { color: '#4f46e5', borderRadius: [0, 4, 4, 0] }, barWidth: '50%' }],
}));
</script>

<template>
  <Head title="Reports" />

  <AppLayout :breadcrumbs="[{ label: 'Reports' }]">
    <PageHeader title="Reports" subtitle="Analytics and insights for your SMS campaigns." />

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard label="Total Contacts" :value="stats.total_contacts" :icon="Users" icon-color="text-primary" icon-bg="bg-primary-light" />
      <StatCard label="Total SMS Sent" :value="stats.total_sms_sent" :icon="Send" icon-color="text-success" icon-bg="bg-success-light" />
      <StatCard label="Total Campaigns" :value="stats.total_campaigns" :icon="CheckCircle" icon-color="text-info" icon-bg="bg-info-light" />
      <StatCard label="Avg Success Rate" :value="stats.avg_success_rate" :icon="CheckCircle" icon-color="text-success" icon-bg="bg-success-light" format="percent" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <Card>
        <CardHeader><CardTitle>SMS Sent Over Time</CardTitle></CardHeader>
        <CardContent>
          <VChart :option="smsChartOption" :autoresize="true" style="height: 300px" />
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Contacts by Source</CardTitle></CardHeader>
        <CardContent>
          <VChart :option="sourceChartOption" :autoresize="true" style="height: 300px" />
        </CardContent>
      </Card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <Card>
        <CardHeader><CardTitle>Contacts by Status</CardTitle></CardHeader>
        <CardContent>
          <VChart :option="statusChartOption" :autoresize="true" style="height: 280px" />
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Top Lists by Size</CardTitle></CardHeader>
        <CardContent>
          <VChart :option="listChartOption" :autoresize="true" style="height: 280px" />
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Top Tags by Size</CardTitle></CardHeader>
        <CardContent>
          <div class="space-y-3">
            <div v-for="tag in top_tags" :key="tag.name" class="flex items-center justify-between">
              <span class="text-sm text-foreground">{{ tag.name }}</span>
              <span class="text-sm text-muted font-medium">{{ formatNumber(tag.count) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
