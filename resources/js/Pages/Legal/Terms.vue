<script setup lang="ts">
import PublicLayout from '@/Components/public/PublicLayout.vue';
import PublicSeoHead from '@/Components/public/PublicSeoHead.vue';
import type { SeoMeta } from '@/types/seo';

defineProps<{
  seo: SeoMeta;
}>();

const sections = [
  {
    title: 'Authorized use',
    body: "Sendora is intended for authorized teams managing their own SMS contacts and campaigns. Users must only upload, store, segment, and send to contacts they are permitted to process under applicable law and their organization's policies.",
  },
  {
    title: 'SMS compliance',
    body: 'Users are responsible for lawful consent, accurate sender identification, compliant message content, recipient rights, and applicable opt-out obligations. Sendora provides status, targeting, and audit features, but the workspace operator remains responsible for campaign use.',
  },
  {
    title: 'Prohibited use',
    body: 'Sendora may not be used to send spam, deceptive messages, phishing content, malware, unlawful promotions, or campaigns to purchased or unauthorized lists. Users may not bypass blocked, unsubscribed, invalid, or other recipient-protection controls.',
  },
  {
    title: 'Provider responsibility',
    body: 'SMS delivery depends on configured third-party providers. Users are responsible for valid provider accounts, sender identities, credentials, rate limits, destination rules, and compliance with provider terms.',
  },
  {
    title: 'Imports and contact data',
    body: 'Users are responsible for the accuracy and legality of uploaded contact files. Sendora provides validation previews, duplicate handling, mapping, and row-level results, but users decide what data is imported and how it is used.',
  },
  {
    title: 'Account access',
    body: 'Accounts are created by an owner or administrator. Users are responsible for protecting login credentials, using assigned roles appropriately, and reporting unauthorized access or suspicious activity.',
  },
  {
    title: 'Operational availability',
    body: 'Campaign sending, scheduled sends, imports, recovery tasks, and reporting depend on queues, scheduler processes, database availability, provider availability, and network connectivity. Operators are responsible for running and monitoring the required production infrastructure.',
  },
  {
    title: 'Limitations',
    body: 'Sendora does not guarantee carrier acceptance, delivery, read confirmation, or uninterrupted third-party service. Reports reflect the data available from the application and configured providers.',
  },
];
</script>

<template>
  <PublicSeoHead :seo="seo" type="article" />

  <PublicLayout contained>
    <article>
      <p class="text-sm font-medium text-primary">Terms of Use</p>
      <h1 class="mt-3 text-4xl font-semibold tracking-tight">Responsible use of Sendora.</h1>
      <p class="mt-5 text-base leading-7 text-muted">
        These terms govern access to and use of Sendora, an SMS campaign, contact management, import, reporting, and sending operations platform.
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
