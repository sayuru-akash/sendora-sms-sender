<script setup lang="ts">
import PublicLayout from '@/Components/public/PublicLayout.vue';
import PublicSeoHead from '@/Components/public/PublicSeoHead.vue';
import type { SeoMeta } from '@/types/seo';

defineProps<{
  seo: SeoMeta;
}>();

const sections = [
  {
    title: 'Data Sendora processes',
    body: 'Sendora may store contact names, phone numbers, email addresses, company details, location fields, source details, notes, list and tag membership, imported metadata, status, opt-out status, blocked status, and campaign recipient history.',
  },
  {
    title: 'How the data is used',
    body: 'This information is used to manage contacts, build audiences, personalize message content, send SMS through configured providers, exclude unsuitable recipients, report campaign performance, and maintain an operational audit trail.',
  },
  {
    title: 'Imports',
    body: 'Uploaded CSV and XLSX files are used to preview, validate, map, and process contacts. Extra columns may be retained as contact metadata. Failed rows may be retained so operators can review processing results.',
  },
  {
    title: 'Campaigns and provider responses',
    body: 'Sendora stores campaign, recipient, and message records, including provider message identifiers, send outcomes, failures, and provider responses where available. Delivery depends on the configured SMS provider.',
  },
  {
    title: 'Activity logs',
    body: 'Operational activity can include sign-ins, imports, contact changes, campaign actions, retry actions, and administrative updates. Logs may include actor details, route, event category, timestamp, and redacted operational metadata.',
  },
  {
    title: 'Sensitive configuration',
    body: 'SMS provider credentials are read from environment configuration. Sendora is designed not to store provider credentials in application records. Passwords, session cookies, provider credentials, and other secrets must not be included in notes or activity properties.',
  },
  {
    title: 'Suppression and contact status',
    body: 'Contacts can be marked unsubscribed, blocked, or invalid. Sendora excludes these statuses from campaign targeting so operators can avoid sending to recipients who should not receive messages.',
  },
  {
    title: 'Retention',
    body: "Contact records, campaigns, imports, message outcomes, suppressions, and activity logs are retained according to the workspace operator's retention policy, legal requirements, and operational needs.",
  },
];
</script>

<template>
  <PublicSeoHead :seo="seo" type="article" />

  <PublicLayout contained>
    <article>
      <p class="text-sm font-medium text-primary">Privacy Policy</p>
      <h1 class="mt-3 text-4xl font-semibold tracking-tight">How Sendora handles campaign data.</h1>
      <p class="mt-5 text-base leading-7 text-muted">
        Sendora is an SMS campaign and contact management system. This policy explains how contact records, campaign activity, provider responses, imports, user accounts, and operational logs are handled inside the platform.
      </p>
      <p class="mt-4 text-sm text-muted">Last updated: August 17, 2026</p>

      <div class="mt-10 space-y-8">
        <section v-for="section in sections" :key="section.title" class="border-t border-border pt-8">
          <h2 class="text-xl font-semibold tracking-tight">{{ section.title }}</h2>
          <p class="mt-3 text-sm leading-7 text-muted">{{ section.body }}</p>
        </section>
      </div>
    </article>
  </PublicLayout>
</template>
