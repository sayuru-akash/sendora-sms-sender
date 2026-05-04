<script setup lang="ts">
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
import Alert from '@/Components/ui/Alert.vue';
import Separator from '@/Components/ui/Separator.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Save, Lock, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { useConfirm } from '@/composables/useConfirm';

defineProps<{
  mustVerifyEmail?: boolean;
  status?: string;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);
const { confirm } = useConfirm();

const profileForm = useForm({
  name: user.value.name,
  email: user.value.email,
});

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

function updateProfile() {
  profileForm.patch(route('profile.update'));
}

function updatePassword() {
  passwordForm.put(route('password.update'), {
    onSuccess: () => passwordForm.reset(),
  });
}

async function handleDeleteAccount() {
  const confirmed = await confirm({
    title: 'Delete Account',
    message: 'Are you sure you want to delete your account? This action is permanent and cannot be undone.',
    confirmLabel: 'Delete Account',
    variant: 'destructive',
  });
  if (confirmed) {
    useForm({}).delete(route('profile.destroy'));
  }
}
</script>

<template>
  <Head title="Profile" />

  <AppLayout :breadcrumbs="[{ label: 'Profile' }]">
    <PageHeader title="Profile" subtitle="Manage your account settings." />

    <div class="max-w-2xl space-y-6">
      <Alert v-if="status === 'verification-link-sent'" variant="success">
        A verification link has been sent to your email.
      </Alert>

      <!-- Profile Information -->
      <Card>
        <CardHeader>
          <CardTitle>Profile Information</CardTitle>
          <CardDescription>Update your account's name and email address.</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="updateProfile" class="space-y-4">
            <div class="space-y-1.5">
              <Label for="profile_name" required>Name</Label>
              <Input id="profile_name" v-model="profileForm.name" :error="profileForm.errors.name" />
              <p v-if="profileForm.errors.name" class="text-xs text-danger">{{ profileForm.errors.name }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="profile_email" required>Email</Label>
              <Input id="profile_email" v-model="profileForm.email" type="email" :error="profileForm.errors.email" />
              <p v-if="profileForm.errors.email" class="text-xs text-danger">{{ profileForm.errors.email }}</p>
            </div>
            <div v-if="mustVerifyEmail && !user.email_verified_at" class="text-sm text-muted">
              Your email is unverified.
              <Link :href="route('verification.send')" method="post" as="button" class="text-primary hover:text-primary-hover underline">
                Resend verification email
              </Link>
            </div>
            <Button type="submit" :loading="profileForm.processing">
              <Save class="h-4 w-4" />
              Save
            </Button>
          </form>
        </CardContent>
      </Card>

      <!-- Update Password -->
      <Card>
        <CardHeader>
          <CardTitle>Update Password</CardTitle>
          <CardDescription>Ensure your account uses a strong password.</CardDescription>
        </CardHeader>
        <CardContent>
          <form @submit.prevent="updatePassword" class="space-y-4">
            <div class="space-y-1.5">
              <Label for="current_password" required>Current Password</Label>
              <Input id="current_password" v-model="passwordForm.current_password" type="password" :error="passwordForm.errors.current_password" />
              <p v-if="passwordForm.errors.current_password" class="text-xs text-danger">{{ passwordForm.errors.current_password }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="new_password" required>New Password</Label>
              <Input id="new_password" v-model="passwordForm.password" type="password" :error="passwordForm.errors.password" />
              <p v-if="passwordForm.errors.password" class="text-xs text-danger">{{ passwordForm.errors.password }}</p>
            </div>
            <div class="space-y-1.5">
              <Label for="confirm_password" required>Confirm Password</Label>
              <Input id="confirm_password" v-model="passwordForm.password_confirmation" type="password" />
            </div>
            <Button type="submit" :loading="passwordForm.processing">
              <Lock class="h-4 w-4" />
              Update Password
            </Button>
          </form>
        </CardContent>
      </Card>

      <!-- Delete Account -->
      <Card class="border-danger/30">
        <CardHeader>
          <CardTitle class="text-danger">Delete Account</CardTitle>
          <CardDescription>Permanently delete your account and all associated data.</CardDescription>
        </CardHeader>
        <CardContent>
          <Button variant="destructive" @click="handleDeleteAccount">
            <Trash2 class="h-4 w-4" />
            Delete Account
          </Button>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
