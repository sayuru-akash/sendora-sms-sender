<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import CampaignProgress from '@/Components/common/CampaignProgress.vue';
import CharacterCounter from '@/Components/common/CharacterCounter.vue';
import StatCard from '@/Components/common/StatCard.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Send, CheckCircle, XCircle, SkipForward, Clock, Pause, Play, XCircleIcon, BarChart3, RotateCcw } from 'lucide-vue-next';
import type { Campaign, CampaignRecipient } from '@/types/campaign';
import type { Pagination } from '@/types';
import { formatDate, formatDateTime, formatNumber } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  campaign: Campaign;
  recipients: { data: CampaignRecipient[]; meta: Pagination };
}>();

const { confirm } = useConfirm();
const isResendingFailed = ref(false);
const retryingRecipientId = ref<number | null>(null);
let refreshTimer: number | undefined;

const canResendFailed = computed(() => ['completed', 'failed'].includes(props.campaign.status) && props.campaign.failed_count > 0);

function pauseCampaign() {
  router.post(route('campaigns.pause', props.campaign.id));
}
function resumeCampaign() {
  router.post(route('campaigns.resume', props.campaign.id));
}
async function sendCampaign() {
  const confirmed = await confirm({
    title: 'Send Campaign',
    message: 'Start sending this campaign now?',
    confirmLabel: 'Send Campaign',
  });
  if (confirmed) router.post(route('campaigns.send', props.campaign.id), { confirmed: true });
}
async function resendFailedCampaign() {
  const confirmed = await confirm({
    title: 'Resend Failed',
    message: `Queue ${props.campaign.failed_count} failed recipient${props.campaign.failed_count === 1 ? '' : 's'} for resend?`,
    confirmLabel: 'Resend Failed',
  });

  if (!confirmed) return;

  isResendingFailed.value = true;
  router.post(route('campaigns.resend-failed', props.campaign.id), {}, {
    preserveScroll: true,
    only: ['campaign', 'recipients'],
    onFinish: () => {
      isResendingFailed.value = false;
    },
  });
}
async function resendRecipient(recipient: CampaignRecipient) {
  const confirmed = await confirm({
    title: 'Resend Recipient',
    message: `Queue another SMS to ${recipient.contact_name}?`,
    confirmLabel: 'Resend',
  });

  if (!confirmed) return;

  retryingRecipientId.value = recipient.id;
  router.post(route('campaigns.recipients.resend', [props.campaign.id, recipient.id]), {}, {
    preserveScroll: true,
    only: ['campaign', 'recipients'],
    onFinish: () => {
      retryingRecipientId.value = null;
    },
  });
}
async function cancelCampaign() {
  const confirmed = await confirm({
    title: 'Cancel Campaign',
    message: 'Are you sure you want to cancel this campaign?',
    confirmLabel: 'Cancel Campaign',
    variant: 'destructive',
  });
  if (confirmed) router.post(route('campaigns.cancel', props.campaign.id));
}

const stats = computed(() => [
  { label: 'Total Recipients', value: props.campaign.total_recipients, icon: Send, color: 'text-primary', bg: 'bg-primary-light' },
  { label: 'Sent', value: props.campaign.sent_count, icon: CheckCircle, color: 'text-success', bg: 'bg-success-light' },
  { label: 'Failed', value: props.campaign.failed_count, icon: XCircle, color: 'text-danger', bg: 'bg-danger-light' },
  { label: 'Pending', value: props.campaign.pending_count, icon: Clock, color: 'text-warning', bg: 'bg-warning-light' },
]);

function refreshCampaignState() {
  if (document.visibilityState !== 'visible') return;

  router.reload({
    only: ['campaign', 'recipients'],
  });
}

onMounted(() => {
  refreshTimer = window.setInterval(refreshCampaignState, 3000);
});

onBeforeUnmount(() => {
  if (refreshTimer) window.clearInterval(refreshTimer);
});
</script>

<template>
  <Head :title="campaign.name" />

  <AppLayout :breadcrumbs="[
    { label: 'Campaigns', href: route('campaigns.index') },
    { label: campaign.name },
  ]">
    <PageHeader :title="campaign.name">
      <template #actions>
        <StatusBadge :status="campaign.status" />
        <Link :href="route('campaigns.report', campaign.id)">
          <Button variant="outline">
            <BarChart3 class="h-4 w-4" />
            Report
          </Button>
        </Link>
        <Button v-if="campaign.status === 'draft'" @click="sendCampaign">
          <Send class="h-4 w-4" />
          Send
        </Button>
        <Button v-if="canResendFailed" variant="outline" :loading="isResendingFailed" @click="resendFailedCampaign">
          <RotateCcw class="h-4 w-4" />
          Resend Failed
        </Button>
        <Button v-if="campaign.status === 'sending'" variant="outline" @click="pauseCampaign">
          <Pause class="h-4 w-4" />
          Pause
        </Button>
        <Button v-if="campaign.status === 'paused'" variant="outline" @click="resumeCampaign">
          <Play class="h-4 w-4" />
          Resume
        </Button>
        <Button v-if="['draft','scheduled','sending','paused'].includes(campaign.status)" variant="ghost" @click="cancelCampaign">
          <XCircleIcon class="h-4 w-4 text-danger" />
        </Button>
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <StatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <!-- Progress -->
    <Card class="mb-6">
      <CardContent class="pt-6">
        <CampaignProgress
          :sent="campaign.sent_count"
          :failed="campaign.failed_count"
          :pending="campaign.pending_count"
          :total="campaign.total_recipients"
        />
      </CardContent>
    </Card>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Campaign Info -->
      <Card>
        <CardHeader>
          <CardTitle>Campaign Details</CardTitle>
        </CardHeader>
        <CardContent>
          <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
              <dt class="text-muted">Target Type</dt>
              <dd class="font-medium text-foreground capitalize">{{ campaign.target_type }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Sender ID</dt>
              <dd class="font-medium text-foreground">{{ campaign.sender_id }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Created By</dt>
              <dd class="font-medium text-foreground">{{ campaign.created_by_name }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-muted">Created At</dt>
              <dd class="font-medium text-foreground">{{ formatDateTime(campaign.created_at) }}</dd>
            </div>
            <div v-if="campaign.started_at" class="flex justify-between">
              <dt class="text-muted">Started At</dt>
              <dd class="font-medium text-foreground">{{ formatDateTime(campaign.started_at) }}</dd>
            </div>
            <div v-if="campaign.completed_at" class="flex justify-between">
              <dt class="text-muted">Completed At</dt>
              <dd class="font-medium text-foreground">{{ formatDateTime(campaign.completed_at) }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>

      <!-- Message -->
      <Card class="lg:col-span-2">
        <CardHeader>
          <CardTitle>Message</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="rounded-lg border border-border bg-gray-50 p-4 mb-3">
            <p class="text-sm text-foreground whitespace-pre-wrap">{{ campaign.message_body }}</p>
          </div>
          <CharacterCounter :text="campaign.message_body" />
        </CardContent>
      </Card>
    </div>

    <!-- Recipients Table -->
    <Card class="mt-6">
      <CardHeader>
        <CardTitle>Recipients</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-border">
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase">Name</th>
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase">Phone</th>
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase">Status</th>
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase">Error</th>
                <th class="h-10 px-4 text-left font-medium text-muted text-xs uppercase">Sent At</th>
                <th class="h-10 px-4 text-right font-medium text-muted text-xs uppercase">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in recipients.data" :key="r.id" class="border-b border-border hover:bg-gray-50/50">
                <td class="px-4 py-3 text-sm text-foreground">{{ r.contact_name }}</td>
                <td class="px-4 py-3 text-sm text-foreground">{{ r.contact_phone }}</td>
                <td class="px-4 py-3"><StatusBadge :status="r.status" /></td>
                <td class="px-4 py-3 text-xs text-danger">
                  <span class="line-clamp-2">{{ r.error_message ?? '—' }}</span>
                </td>
                <td class="px-4 py-3 text-xs text-muted">{{ r.sent_at ? formatDateTime(r.sent_at) : '—' }}</td>
                <td class="px-4 py-3 text-right">
                  <Button
                    v-if="r.status === 'failed' && canResendFailed"
                    size="sm"
                    variant="outline"
                    :loading="retryingRecipientId === r.id"
                    @click="resendRecipient(r)"
                  >
                    <RotateCcw class="h-3.5 w-3.5" />
                    Resend
                  </Button>
                  <span v-else class="text-xs text-muted">—</span>
                </td>
              </tr>
              <tr v-if="recipients.data.length === 0">
                <td colspan="6" class="px-4 py-8 text-center text-sm text-muted">
                  No recipients prepared yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  </AppLayout>
</template>
