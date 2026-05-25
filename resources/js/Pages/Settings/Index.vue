<script setup lang="ts">
import { computed } from 'vue';
import AppLayout from '@/Components/layout/AppLayout.vue';
import PageHeader from '@/Components/common/PageHeader.vue';
import Card from '@/Components/ui/Card.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import Button from '@/Components/ui/Button.vue';
import Input from '@/Components/ui/Input.vue';
import Label from '@/Components/ui/Label.vue';
import Select from '@/Components/ui/Select.vue';
import Separator from '@/Components/ui/Separator.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import TabsList from '@/Components/ui/TabsList.vue';
import TabsTrigger from '@/Components/ui/TabsTrigger.vue';
import TabsContent from '@/Components/ui/TabsContent.vue';
import Alert from '@/Components/ui/Alert.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Save, Send, CheckCircle } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

interface SettingsPayload {
  company_name: string;
  timezone: string;
  date_format: string;
  default_country_code: string;
  sender_id: string;
  sms_provider: string;
  max_import_file_size: number;
  default_duplicate_handling: string;
}

const props = defineProps<{
  settings?: SettingsPayload;
}>();

const defaultSettings: SettingsPayload = {
  company_name: 'Sendora',
  timezone: 'Asia/Colombo',
  date_format: 'd/m/Y',
  default_country_code: '+94',
  sender_id: 'SITC Campus',
  sms_provider: 'notifylk',
  max_import_file_size: 10,
  default_duplicate_handling: 'skip',
};

const safeSettings = computed(() => props.settings ?? defaultSettings);

const generalForm = useForm({
  company_name: safeSettings.value.company_name,
  timezone: safeSettings.value.timezone,
  date_format: safeSettings.value.date_format,
  default_country_code: safeSettings.value.default_country_code,
});

const smsTestForm = useForm({
  phone: '',
  message: 'This is a test message from Sendora.',
});

const timezoneOptions = [
  { label: 'Asia/Colombo', value: 'Asia/Colombo' },
  { label: 'UTC', value: 'UTC' },
  { label: 'America/New_York', value: 'America/New_York' },
  { label: 'Europe/London', value: 'Europe/London' },
  { label: 'Asia/Kolkata', value: 'Asia/Kolkata' },
];

const dateFormatOptions = [
  { label: 'DD/MM/YYYY', value: 'd/m/Y' },
  { label: 'MM/DD/YYYY', value: 'm/d/Y' },
  { label: 'YYYY-MM-DD', value: 'Y-m-d' },
];

function saveGeneral() {
  generalForm.put(route('settings.update'), {
    onSuccess: () => toast.success('Settings saved'),
  });
}

function sendTestSms() {
  smsTestForm.post(route('settings.test-sms'), {
    onSuccess: () => toast.success('Test SMS sent successfully'),
    onError: () => toast.error('Failed to send test SMS'),
  });
}
</script>

<template>
  <Head title="Settings" />

  <AppLayout :breadcrumbs="[{ label: 'Settings' }]">
    <PageHeader title="Settings" subtitle="Manage your application settings." />

    <Tabs default-value="general">
      <TabsList class="mb-6">
        <TabsTrigger value="general">General</TabsTrigger>
        <TabsTrigger value="sms">SMS</TabsTrigger>
        <TabsTrigger value="imports">Imports</TabsTrigger>
        <TabsTrigger value="access">Users & Access</TabsTrigger>
      </TabsList>

      <!-- General -->
      <TabsContent value="general">
        <Card>
          <CardHeader>
            <CardTitle>General Settings</CardTitle>
            <CardDescription>Configure your company and display preferences.</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="saveGeneral" class="space-y-4 max-w-lg">
              <div class="space-y-1.5">
                <Label for="company_name">Company Name</Label>
                <Input id="company_name" v-model="generalForm.company_name" />
              </div>
              <div class="space-y-1.5">
                <Label>Timezone</Label>
                <Select v-model="generalForm.timezone" :options="timezoneOptions" />
              </div>
              <div class="space-y-1.5">
                <Label>Date Format</Label>
                <Select v-model="generalForm.date_format" :options="dateFormatOptions" />
              </div>
              <div class="space-y-1.5">
                <Label for="country_code">Default Country Code</Label>
                <Input id="country_code" v-model="generalForm.default_country_code" placeholder="+94" />
              </div>
              <Button type="submit" :loading="generalForm.processing">
                <Save class="h-4 w-4" />
                Save Changes
              </Button>
            </form>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- SMS -->
      <TabsContent value="sms">
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>SMS Configuration</CardTitle>
              <CardDescription>Your SMS provider is configured via environment variables.</CardDescription>
            </CardHeader>
            <CardContent>
              <dl class="space-y-3 text-sm max-w-md">
                <div class="flex justify-between">
                  <dt class="text-muted">Provider</dt>
                  <dd class="font-medium text-foreground capitalize">{{ safeSettings.sms_provider }}</dd>
                </div>
                <div class="flex justify-between">
                  <dt class="text-muted">Sender ID</dt>
                  <dd class="font-medium text-foreground">{{ safeSettings.sender_id }}</dd>
                </div>
              </dl>
              <Alert variant="info" class="mt-4">
                SMS credentials are stored securely in your environment configuration (.env) and are not displayed here.
              </Alert>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Test SMS</CardTitle>
              <CardDescription>Send a test message to verify your SMS configuration.</CardDescription>
            </CardHeader>
            <CardContent>
              <form @submit.prevent="sendTestSms" class="space-y-4 max-w-md">
                <div class="space-y-1.5">
                  <Label for="test_phone" required>Phone Number</Label>
                  <Input id="test_phone" v-model="smsTestForm.phone" placeholder="+94XXXXXXXXX" :error="smsTestForm.errors.phone" />
                  <p v-if="smsTestForm.errors.phone" class="text-xs text-danger">{{ smsTestForm.errors.phone }}</p>
                </div>
                <div class="space-y-1.5">
                  <Label for="test_message">Message</Label>
                  <Input id="test_message" v-model="smsTestForm.message" />
                </div>
                <Button type="submit" :loading="smsTestForm.processing">
                  <Send class="h-4 w-4" />
                  Send Test SMS
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </TabsContent>

      <!-- Imports -->
      <TabsContent value="imports">
        <Card>
          <CardHeader>
            <CardTitle>Import Settings</CardTitle>
            <CardDescription>Configure default settings for contact imports.</CardDescription>
          </CardHeader>
          <CardContent>
            <dl class="space-y-3 text-sm max-w-md">
              <div class="flex justify-between">
                <dt class="text-muted">Max File Size</dt>
                <dd class="font-medium text-foreground">{{ safeSettings.max_import_file_size }}MB</dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-muted">Default Duplicate Handling</dt>
                <dd class="font-medium text-foreground capitalize">{{ safeSettings.default_duplicate_handling }}</dd>
              </div>
            </dl>
          </CardContent>
        </Card>
      </TabsContent>

      <!-- Users & Access -->
      <TabsContent value="access">
        <Card>
          <CardHeader>
            <CardTitle>Roles & Permissions</CardTitle>
            <CardDescription>Overview of user roles and their access levels.</CardDescription>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div v-for="role in ['Owner', 'Admin', 'Manager', 'Staff', 'Viewer']" :key="role" class="flex items-start gap-3 p-3 rounded-lg border border-border">
                <div class="flex-1">
                  <p class="text-sm font-medium text-foreground">{{ role }}</p>
                  <p class="text-xs text-muted mt-0.5">
                    {{ {
                      'Owner': 'Full access to all features, billing, and user management.',
                      'Admin': 'Full access except billing and owner management.',
                      'Manager': 'Manage contacts, campaigns, templates, and reports.',
                      'Staff': 'View and manage assigned contacts only.',
                      'Viewer': 'Read-only access to dashboards and reports.',
                    }[role] }}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </TabsContent>
    </Tabs>
  </AppLayout>
</template>
