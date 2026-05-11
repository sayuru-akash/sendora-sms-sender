<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import SearchInput from '@/Components/common/SearchInput.vue';
import Pagination from '@/Components/common/Pagination.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Select from '@/Components/ui/Select.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Send, Plus, MoreHorizontal, Eye, Copy, Pause, Play, XCircle, Trash2, BarChart3 } from 'lucide-vue-next';
import type { Campaign } from '@/types/campaign';
import type { Pagination as PaginationType } from '@/types';
import { formatDate, formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  campaigns: { data: Campaign[]; meta: PaginationType };
  summary: {
    campaigns_count: number;
    total_recipients_sum: number;
    sent_count_sum: number;
    failed_count_sum: number;
    pending_count_sum: number;
    queued_count_sum: number;
    status_counts: Record<string, number>;
  };
  filters: {
    search?: string;
    status?: string;
  };
}>();

const { confirm } = useConfirm();
const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const processingCampaignId = ref<number | null>(null);
let refreshTimer: number | undefined;

const statusOptions = [
  { label: 'All statuses', value: '' },
  { label: 'Draft', value: 'draft' },
  { label: 'Scheduled', value: 'scheduled' },
  { label: 'Queued', value: 'queued' },
  { label: 'Sending', value: 'sending' },
  { label: 'Paused', value: 'paused' },
  { label: 'Completed', value: 'completed' },
  { label: 'Failed', value: 'failed' },
  { label: 'Cancelled', value: 'cancelled' },
];

const activeWorkCount = computed(() => props.summary.pending_count_sum + props.summary.queued_count_sum);

function successRateFor(campaign: Campaign) {
  return Number.isFinite(Number(campaign.success_rate)) ? Number(campaign.success_rate) : 0;
}

function applyFilters() {
  router.get(
    route('campaigns.index'),
    {
      search: search.value || undefined,
      status: selectedStatus.value || undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
    },
  );
}

function handleSearch(value: string) {
  search.value = value;
  applyFilters();
}

function handleStatusFilter(value: string | number) {
  selectedStatus.value = String(value);
  applyFilters();
}

async function deleteCampaign(campaign: Campaign) {
  const confirmed = await confirm({
    title: 'Delete Campaign',
    message: `Delete campaign "${campaign.name}"?`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) router.delete(route('campaigns.destroy', campaign.id));
}

function postCampaignAction(campaign: Campaign, routeName: string) {
  processingCampaignId.value = campaign.id;
  router.post(route(routeName, campaign.id), {}, {
    preserveScroll: true,
    onFinish: () => {
      processingCampaignId.value = null;
    },
  });
}

function pauseCampaign(campaign: Campaign) {
  postCampaignAction(campaign, 'campaigns.pause');
}

function resumeCampaign(campaign: Campaign) {
  postCampaignAction(campaign, 'campaigns.resume');
}

async function cancelCampaign(campaign: Campaign) {
  const confirmed = await confirm({
    title: 'Cancel Campaign',
    message: `Cancel "${campaign.name}" and skip all unsent recipients?`,
    confirmLabel: 'Cancel Campaign',
    variant: 'destructive',
  });

  if (confirmed) {
    postCampaignAction(campaign, 'campaigns.cancel');
  }
}

async function duplicateCampaign(campaign: Campaign) {
  const confirmed = await confirm({
    title: 'Duplicate Campaign',
    message: `Create a draft copy of "${campaign.name}"?`,
    confirmLabel: 'Duplicate',
  });

  if (confirmed) {
    postCampaignAction(campaign, 'campaigns.duplicate');
  }
}

function refreshCampaigns() {
  if (document.visibilityState !== 'visible') return;

  router.reload({
    only: ['campaigns', 'summary'],
  });
}

onMounted(() => {
  refreshTimer = window.setInterval(refreshCampaigns, 5000);
});

onBeforeUnmount(() => {
  if (refreshTimer) window.clearInterval(refreshTimer);
});
</script>

<template>
  <Head title="Campaigns" />

  <AppLayout :breadcrumbs="[{ label: 'Campaigns' }]">
    <PageHeader title="Campaigns" :subtitle="`${formatNumber(campaigns.meta.total)} campaigns in view`">
      <template #actions>
        <Link :href="route('campaigns.builder')">
          <Button>
            <Plus class="h-4 w-4" />
            New Campaign
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-lg border border-border bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">Campaigns</p>
        <p class="mt-1 text-2xl font-semibold text-foreground">{{ formatNumber(summary.campaigns_count) }}</p>
      </div>
      <div class="rounded-lg border border-border bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">Recipients</p>
        <p class="mt-1 text-2xl font-semibold text-foreground">{{ formatNumber(summary.total_recipients_sum) }}</p>
      </div>
      <div class="rounded-lg border border-border bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">Sent</p>
        <p class="mt-1 text-2xl font-semibold text-success">{{ formatNumber(summary.sent_count_sum) }}</p>
      </div>
      <div class="rounded-lg border border-border bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-muted">Needs Attention</p>
        <p class="mt-1 text-2xl font-semibold text-danger">{{ formatNumber(summary.failed_count_sum) }}</p>
        <p class="mt-1 text-xs text-muted">{{ formatNumber(activeWorkCount) }} queued or pending</p>
      </div>
    </div>

    <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <SearchInput
          :model-value="search"
          placeholder="Search campaigns..."
          class="w-full sm:w-80"
          @update:model-value="handleSearch"
        />
        <Select
          :model-value="selectedStatus"
          :options="statusOptions"
          placeholder="Status"
          class="w-full sm:w-44"
          @update:model-value="handleStatusFilter"
        />
      </div>
      <div class="flex flex-wrap gap-2">
        <Badge
          v-for="[status, count] in Object.entries(summary.status_counts)"
          :key="status"
          variant="outline"
          class="capitalize"
        >
          {{ status }} · {{ formatNumber(count) }}
        </Badge>
      </div>
    </div>

    <div v-if="campaigns.data.length === 0" class="py-8">
      <EmptyState
        :icon="Send"
        :title="search || selectedStatus ? 'No campaigns found' : 'No campaigns yet'"
        :description="search || selectedStatus ? 'Change the search or status filter to see more campaigns.' : 'Create your first SMS campaign to start reaching your contacts.'"
      >
        <template #action>
          <Link :href="route('campaigns.builder')">
            <Button size="sm">
              <Plus class="h-4 w-4" />
              New Campaign
            </Button>
          </Link>
        </template>
      </EmptyState>
    </div>

    <div v-else class="rounded-xl border border-border bg-white overflow-hidden">
      <div class="overflow-x-auto">
      <table class="w-full min-w-[980px] text-sm">
        <thead>
          <tr class="border-b border-border">
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Campaign</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Status</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Recipients</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Success Rate</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Created</th>
            <th class="h-10 px-4 w-10"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="campaign in campaigns.data"
            :key="campaign.id"
            class="border-b border-border hover:bg-gray-50/50 transition-colors"
          >
            <td class="px-4 py-3">
              <Link :href="route('campaigns.show', campaign.id)" class="text-sm font-medium text-foreground hover:text-primary transition-colors">
                {{ campaign.name }}
              </Link>
              <p class="text-xs text-muted mt-0.5 capitalize">{{ campaign.target_type }} target</p>
            </td>
            <td class="px-4 py-3">
              <StatusBadge :status="campaign.status" />
            </td>
            <td class="px-4 py-3 text-sm text-foreground">
              <div class="font-medium">{{ formatNumber(campaign.total_recipients) }}</div>
              <div class="mt-1 flex flex-wrap gap-1.5 text-[11px]">
                <span class="rounded-full bg-success/10 px-2 py-0.5 text-success">{{ formatNumber(campaign.sent_count) }} sent</span>
                <span v-if="campaign.failed_count" class="rounded-full bg-danger-light px-2 py-0.5 text-danger">{{ formatNumber(campaign.failed_count) }} failed</span>
                <span v-if="campaign.pending_count" class="rounded-full bg-gray-100 px-2 py-0.5 text-muted">{{ formatNumber(campaign.pending_count) }} pending</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-100">
                  <div class="h-full rounded-full bg-success" :style="{ width: `${Math.min(successRateFor(campaign), 100)}%` }" />
                </div>
                <span class="text-sm font-medium text-foreground">{{ successRateFor(campaign) }}%</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <p class="text-sm text-muted">{{ formatDate(campaign.created_at) }}</p>
              <p class="text-xs text-muted-foreground">{{ campaign.created_by_name }}</p>
            </td>
            <td class="px-4 py-3">
              <DropdownMenu>
                <template #trigger>
                  <Button variant="ghost" size="icon-sm">
                    <MoreHorizontal class="h-4 w-4" :class="processingCampaignId === campaign.id && 'animate-pulse'" />
                  </Button>
                </template>
                <DropdownMenuItem @select="router.get(route('campaigns.show', campaign.id))">
                  <Eye class="h-4 w-4" /> View
                </DropdownMenuItem>
                <DropdownMenuItem @select="router.get(route('campaigns.report', campaign.id))">
                  <BarChart3 class="h-4 w-4" /> Report
                </DropdownMenuItem>
                <DropdownMenuItem @select="duplicateCampaign(campaign)">
                  <Copy class="h-4 w-4" /> Duplicate
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem v-if="campaign.status === 'sending'" @select="pauseCampaign(campaign)">
                  <Pause class="h-4 w-4" /> Pause
                </DropdownMenuItem>
                <DropdownMenuItem v-if="campaign.status === 'paused'" @select="resumeCampaign(campaign)">
                  <Play class="h-4 w-4" /> Resume
                </DropdownMenuItem>
                <DropdownMenuItem
                  v-if="['draft', 'scheduled', 'sending', 'paused'].includes(campaign.status)"
                  @select="cancelCampaign(campaign)"
                >
                  <XCircle class="h-4 w-4" /> Cancel
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem destructive @select="deleteCampaign(campaign)">
                  <Trash2 class="h-4 w-4" /> Delete
                </DropdownMenuItem>
              </DropdownMenu>
            </td>
          </tr>
        </tbody>
      </table>
      </div>
    </div>

    <Pagination :meta="campaigns.meta" />
  </AppLayout>
</template>
