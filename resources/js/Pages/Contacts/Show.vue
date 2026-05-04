<script setup lang="ts">
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import StatusBadge from '@/Components/common/StatusBadge.vue';
import TagPill from '@/Components/common/TagPill.vue';
import ListBadge from '@/Components/common/ListBadge.vue';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Separator from '@/Components/ui/Separator.vue';
import Avatar from '@/Components/ui/Avatar.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  Pencil,
  Trash2,
  Ban,
  UserX,
  Phone,
  Mail,
  Building2,
  MapPin,
  Briefcase,
  Calendar,
  MessageSquare,
  FileText,
} from 'lucide-vue-next';
import type { Contact } from '@/types/contact';
import type { Campaign } from '@/types/campaign';
import { formatDate, formatDateTime, formatRelativeTime } from '@/lib/utils';
import { useConfirm } from '@/composables/useConfirm';

const props = defineProps<{
  contact: Contact;
  campaigns: Campaign[];
  sms_history: Array<{
    id: number;
    message: string;
    status: string;
    campaign_name: string | null;
    sent_at: string | null;
    created_at: string;
  }>;
}>();

const { confirm } = useConfirm();

async function handleDelete() {
  const confirmed = await confirm({
    title: 'Delete Contact',
    message: `Are you sure you want to delete ${props.contact.full_name}? This action cannot be undone.`,
    confirmLabel: 'Delete',
    variant: 'destructive',
  });
  if (confirmed) {
    router.delete(route('contacts.destroy', props.contact.id));
  }
}

async function handleBlock() {
  const confirmed = await confirm({
    title: 'Block Contact',
    message: `Block ${props.contact.full_name}? They will no longer receive SMS messages.`,
    confirmLabel: 'Block',
    variant: 'destructive',
  });
  if (confirmed) {
    router.post(route('contacts.block', props.contact.id));
  }
}

const contactFields = [
  { icon: Phone, label: 'Phone', value: props.contact.phone },
  { icon: Mail, label: 'Email', value: props.contact.email },
  { icon: Building2, label: 'Company', value: props.contact.company },
  { icon: Briefcase, label: 'Job Title', value: props.contact.job_title },
  { icon: MapPin, label: 'City', value: props.contact.city },
  { icon: MapPin, label: 'District', value: props.contact.district },
  { icon: Calendar, label: 'Source', value: props.contact.source },
  { icon: Calendar, label: 'Created', value: formatDate(props.contact.created_at) },
];
</script>

<template>
  <Head :title="contact.full_name" />

  <AppLayout :breadcrumbs="[
    { label: 'Contacts', href: route('contacts.index') },
    { label: contact.full_name },
  ]">
    <PageHeader :title="contact.full_name" :subtitle="contact.phone">
      <template #actions>
        <Button variant="destructive" @click="handleBlock">
          <Ban class="h-4 w-4" />
          Block
        </Button>
        <Link :href="route('contacts.edit', contact.id)">
          <Button variant="outline">
            <Pencil class="h-4 w-4" />
            Edit
          </Button>
        </Link>
        <Button variant="ghost" @click="handleDelete">
          <Trash2 class="h-4 w-4 text-danger" />
        </Button>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Contact Profile Card -->
      <div class="lg:col-span-1 space-y-4">
        <Card>
          <CardContent class="pt-6">
            <div class="flex flex-col items-center text-center mb-6">
              <Avatar :alt="contact.full_name" size="xl" class="mb-3" />
              <h2 class="text-lg font-semibold text-foreground">{{ contact.full_name }}</h2>
              <StatusBadge :status="contact.status" class="mt-2" />
            </div>

            <Separator class="my-4" />

            <div class="space-y-3">
              <div
                v-for="field in contactFields"
                :key="field.label"
                class="flex items-center gap-3"
              >
                <component :is="field.icon" class="h-4 w-4 text-muted-foreground shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted">{{ field.label }}</p>
                  <p class="text-sm text-foreground truncate">{{ field.value ?? '—' }}</p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Tags -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Tags</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="contact.tags.length" class="flex flex-wrap gap-2">
              <TagPill v-for="tag in contact.tags" :key="tag.id" :name="tag.name" />
            </div>
            <p v-else class="text-sm text-muted">No tags assigned</p>
          </CardContent>
        </Card>

        <!-- Lists -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Lists</CardTitle>
          </CardHeader>
          <CardContent>
            <div v-if="contact.lists.length" class="flex flex-wrap gap-2">
              <ListBadge v-for="list in contact.lists" :key="list.id" :name="list.name" />
            </div>
            <p v-else class="text-sm text-muted">Not in any list</p>
          </CardContent>
        </Card>

        <!-- Notes -->
        <Card v-if="contact.notes">
          <CardHeader>
            <CardTitle class="text-base">Notes</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-sm text-muted whitespace-pre-wrap">{{ contact.notes }}</p>
          </CardContent>
        </Card>
      </div>

      <!-- History Tabs -->
      <div class="lg:col-span-2">
        <Card>
          <CardContent class="pt-6">
            <div class="space-y-6">
              <!-- Campaign History -->
              <div>
                <h3 class="text-sm font-semibold text-foreground mb-3 flex items-center gap-2">
                  <FileText class="h-4 w-4" />
                  Campaign History
                </h3>
                <div v-if="campaigns.length === 0" class="py-4 text-center text-sm text-muted">
                  Not included in any campaigns
                </div>
                <div v-else class="space-y-2">
                  <div
                    v-for="campaign in campaigns"
                    :key="campaign.id"
                    class="flex items-center justify-between rounded-lg border border-border p-3 hover:bg-gray-50 transition-colors"
                  >
                    <div>
                      <p class="text-sm font-medium text-foreground">{{ campaign.name }}</p>
                      <p class="text-xs text-muted">{{ formatDate(campaign.created_at) }}</p>
                    </div>
                    <StatusBadge :status="campaign.status" />
                  </div>
                </div>
              </div>

              <Separator />

              <!-- SMS History -->
              <div>
                <h3 class="text-sm font-semibold text-foreground mb-3 flex items-center gap-2">
                  <MessageSquare class="h-4 w-4" />
                  SMS History
                </h3>
                <div v-if="sms_history.length === 0" class="py-4 text-center text-sm text-muted">
                  No SMS messages sent to this contact
                </div>
                <div v-else class="space-y-2">
                  <div
                    v-for="sms in sms_history"
                    :key="sms.id"
                    class="rounded-lg border border-border p-3"
                  >
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-xs text-muted">
                        {{ sms.campaign_name ?? 'Direct SMS' }}
                      </span>
                      <StatusBadge :status="sms.status" />
                    </div>
                    <p class="text-sm text-foreground line-clamp-2">{{ sms.message }}</p>
                    <p class="text-xs text-muted-foreground mt-1">
                      {{ sms.sent_at ? formatDateTime(sms.sent_at) : formatRelativeTime(sms.created_at) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
