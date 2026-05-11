<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import EmptyState from '@/Components/common/EmptyState.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import DropdownMenu from '@/Components/ui/DropdownMenu.vue';
import DropdownMenuItem from '@/Components/ui/DropdownMenuItem.vue';
import DropdownMenuSeparator from '@/Components/ui/DropdownMenuSeparator.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Send, Plus, MoreHorizontal, Eye, Copy, Pause, Play, XCircle, Trash2, BarChart3 } from 'lucide-vue-next';
import type { Campaign } from '@/types/campaign';
import type { Pagination } from '@/types';
import { formatDate, formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  campaigns: { data: Campaign[]; meta: Pagination };
}>();

const { confirm } = useConfirm();
let refreshTimer: number | undefined;

async function deleteCampaign(campaign: Campaign) {
  const confirmed = await confirm({
    title: 'Delete Campaign',
    message: `Delete campaign "${campaign.name}"?`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) router.delete(route('campaigns.destroy', campaign.id));
}

function pauseCampaign(campaign: Campaign) {
  router.post(route('campaigns.pause', campaign.id));
}

function resumeCampaign(campaign: Campaign) {
  router.post(route('campaigns.resume', campaign.id));
}

function cancelCampaign(campaign: Campaign) {
  router.post(route('campaigns.cancel', campaign.id));
}

function duplicateCampaign(campaign: Campaign) {
  router.post(route('campaigns.duplicate', campaign.id));
}

function refreshCampaigns() {
  if (document.visibilityState !== 'visible') return;

  router.reload({
    only: ['campaigns'],
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
    <PageHeader title="Campaigns" :subtitle="`${formatNumber(campaigns.meta.total)} campaigns`">
      <template #actions>
        <Link :href="route('campaigns.builder')">
          <Button>
            <Plus class="h-4 w-4" />
            New Campaign
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div v-if="campaigns.data.length === 0" class="py-8">
      <EmptyState
        :icon="Send"
        title="No campaigns yet"
        description="Create your first SMS campaign to start reaching your contacts."
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
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-border">
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Campaign</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Status</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Recipients</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Sent</th>
            <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase tracking-wider">Failed</th>
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
              {{ formatNumber(campaign.total_recipients) }}
            </td>
            <td class="px-4 py-3">
              <span class="text-sm text-success font-medium">{{ formatNumber(campaign.sent_count) }}</span>
            </td>
            <td class="px-4 py-3">
              <span class="text-sm text-danger font-medium">{{ formatNumber(campaign.failed_count) }}</span>
            </td>
            <td class="px-4 py-3 text-sm text-foreground">
              {{ campaign.success_rate }}%
            </td>
            <td class="px-4 py-3">
              <p class="text-sm text-muted">{{ formatDate(campaign.created_at) }}</p>
              <p class="text-xs text-muted-foreground">{{ campaign.created_by_name }}</p>
            </td>
            <td class="px-4 py-3">
              <DropdownMenu>
                <template #trigger>
                  <Button variant="ghost" size="icon-sm">
                    <MoreHorizontal class="h-4 w-4" />
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
  </AppLayout>
</template>
