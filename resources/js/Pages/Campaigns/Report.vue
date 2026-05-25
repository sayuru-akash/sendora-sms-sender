<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import StatCard from '@/Components/common/StatCard.vue';
import { Head, router } from '@inertiajs/vue3';
import { Download, CheckCircle, XCircle, Clock, RotateCcw } from 'lucide-vue-next';
import type { Campaign, CampaignStats } from '@/types/campaign';
import { formatNumber } from '@/lib/utils';
import VChart from 'vue-echarts';
import { useConfirm } from '@/composables/useConfirm';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart, LineChart } from 'echarts/charts';
import { TitleComponent, TooltipComponent, GridComponent, LegendComponent } from 'echarts/components';

use([CanvasRenderer, PieChart, LineChart, TitleComponent, TooltipComponent, GridComponent, LegendComponent]);

const props = defineProps<{
  campaign: Campaign;
  stats?: CampaignStats;
}>();

const emptyStats: CampaignStats = {
  total: 0,
  sent: 0,
  failed: 0,
  skipped: 0,
  pending: 0,
  success_rate: 0,
  timeline: [],
};

const safeStats = computed(() => props.stats ?? emptyStats);
const { confirm } = useConfirm();
const isResendingFailed = ref(false);
const canResendFailed = computed(() => ['completed', 'failed'].includes(props.campaign.status) && safeStats.value.failed > 0);

const pieOption = computed(() => ({
  tooltip: { trigger: 'item', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  legend: { bottom: 0, textStyle: { color: '#6b7280', fontSize: 11 } },
  series: [{
    type: 'pie',
    radius: ['45%', '70%'],
    avoidLabelOverlap: false,
    label: { show: false },
    data: [
      { value: safeStats.value.sent, name: 'Sent', itemStyle: { color: '#10b981' } },
      { value: safeStats.value.failed, name: 'Failed', itemStyle: { color: '#ef4444' } },
      { value: safeStats.value.skipped, name: 'Skipped', itemStyle: { color: '#f59e0b' } },
      { value: safeStats.value.pending, name: 'Pending', itemStyle: { color: '#d1d5db' } },
    ],
  }],
}));

const timelineOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: '#fff', borderColor: '#e5e7eb', textStyle: { color: '#1a1a1a', fontSize: 12 } },
  legend: { data: ['Sent', 'Failed'], bottom: 0, textStyle: { color: '#6b7280', fontSize: 11 } },
  grid: { top: 10, right: 16, bottom: 36, left: 48 },
  xAxis: { type: 'category', data: safeStats.value.timeline.map((d) => d.time), axisLine: { lineStyle: { color: '#e5e7eb' } }, axisTick: { show: false }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  yAxis: { type: 'value', axisLine: { show: false }, axisTick: { show: false }, splitLine: { lineStyle: { color: '#f3f4f6' } }, axisLabel: { color: '#6b7280', fontSize: 11 } },
  series: [
    { name: 'Sent', type: 'line', data: safeStats.value.timeline.map((d) => d.sent), smooth: true, lineStyle: { color: '#10b981', width: 2 }, itemStyle: { color: '#10b981' }, areaStyle: { color: 'rgba(16,185,129,0.1)' } },
    { name: 'Failed', type: 'line', data: safeStats.value.timeline.map((d) => d.failed), smooth: true, lineStyle: { color: '#ef4444', width: 2 }, itemStyle: { color: '#ef4444' } },
  ],
}));

const statCards = computed(() => [
  { label: 'Total', value: safeStats.value.total, icon: Clock, color: 'text-primary', bg: 'bg-primary-light' },
  { label: 'Sent', value: safeStats.value.sent, icon: CheckCircle, color: 'text-success', bg: 'bg-success-light' },
  { label: 'Failed', value: safeStats.value.failed, icon: XCircle, color: 'text-danger', bg: 'bg-danger-light' },
  { label: 'Success Rate', value: safeStats.value.success_rate, icon: CheckCircle, color: 'text-success', bg: 'bg-success-light', format: 'percent' as const },
]);

async function resendFailedCampaign() {
  const confirmed = await confirm({
    title: 'Resend Failed',
    message: `Queue ${safeStats.value.failed} failed recipient${safeStats.value.failed === 1 ? '' : 's'} for resend?`,
    confirmLabel: 'Resend Failed',
  });

  if (!confirmed) return;

  isResendingFailed.value = true;
  router.post(route('campaigns.resend-failed', props.campaign.id), {}, {
    preserveScroll: true,
    only: ['campaign', 'stats'],
    onFinish: () => {
      isResendingFailed.value = false;
    },
  });
}
</script>

<template>
  <Head :title="`Report: ${campaign.name}`" />

  <AppLayout :breadcrumbs="[
    { label: 'Campaigns', href: route('campaigns.index') },
    { label: campaign.name, href: route('campaigns.show', campaign.id) },
    { label: 'Report' },
  ]">
    <PageHeader :title="`Report: ${campaign.name}`">
      <template #actions>
        <a :href="route('reports.campaign.export', campaign.id)" target="_blank" rel="noopener">
          <Button variant="outline">
            <Download class="h-4 w-4" />
            Export
          </Button>
        </a>
        <Button v-if="canResendFailed" variant="outline" :loading="isResendingFailed" @click="resendFailedCampaign">
          <RotateCcw class="h-4 w-4" />
          Resend Failed
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard v-for="stat in statCards" :key="stat.label" v-bind="stat" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <Card>
        <CardHeader><CardTitle>Status Breakdown</CardTitle></CardHeader>
        <CardContent>
          <VChart :option="pieOption" :autoresize="true" style="height: 300px" />
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>Send Timeline</CardTitle></CardHeader>
        <CardContent>
          <VChart :option="timelineOption" :autoresize="true" style="height: 300px" />
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
