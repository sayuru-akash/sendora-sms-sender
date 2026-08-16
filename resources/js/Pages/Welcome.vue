<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
  ArrowRight,
  CheckCircle2,
  Clock3,
  DatabaseZap,
  FileCheck2,
  MessageSquareText,
  Send,
  ShieldCheck,
  Tags,
  Users,
} from 'lucide-vue-next';
import PublicLayout from '@/Components/public/PublicLayout.vue';
import PublicSeoHead from '@/Components/public/PublicSeoHead.vue';
import type { SeoMeta } from '@/types/seo';

defineProps<{
  seo: SeoMeta;
}>();

const capabilities = [
  {
    title: 'Clean contact records',
    body: 'Profiles, normalized phone numbers, lists, tags, segments, notes, source details, and delivery status stay connected.',
    icon: Users,
  },
  {
    title: 'Review every import',
    body: 'Map CSV or XLSX columns, preview rows, handle duplicates, add lists and tags, then commit with confidence.',
    icon: FileCheck2,
  },
  {
    title: 'Controlled SMS sends',
    body: 'Use templates, variables, character counts, audience estimates, and scheduling before a campaign reaches the queue.',
    icon: MessageSquareText,
  },
  {
    title: 'Operational recovery',
    body: 'Watch live progress, pause or resume campaigns, retry failed recipients, and inspect recipient-level outcomes.',
    icon: Send,
  },
];

const workflow = [
  'Import and validate contacts',
  'Target the right audience',
  'Prepare the SMS',
  'Send, recover, and report',
];

const trustItems = [
  'Unsubscribed, blocked, and invalid contacts are excluded before sending.',
  'Campaigns preserve recipient and message outcomes for operational review.',
  'Variables and message length are visible before campaigns are queued.',
  'User actions, imports, campaigns, and retries are retained in activity logs.',
];
</script>

<template>
  <PublicSeoHead :seo="seo" />

  <PublicLayout>
    <section class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-14 sm:py-20 lg:grid-cols-[minmax(0,1fr)_520px] lg:px-8 lg:py-24">
      <div class="flex min-w-0 flex-col justify-center">
        <h1 class="max-w-3xl text-4xl font-semibold leading-tight text-balance sm:text-5xl lg:text-6xl">
          Run SMS campaigns with cleaner data and safer delivery.
        </h1>
        <p class="mt-6 max-w-2xl text-base leading-7 text-muted sm:text-lg">
          Sendora brings contacts, imports, targeting, templates, message controls, campaign queues, delivery outcomes, and audit logs into one operations workspace.
        </p>
        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
          <Link
            class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
            :href="route('login')"
          >
            Log in
            <ArrowRight class="size-4" aria-hidden="true" focusable="false" />
          </Link>
          <a
            class="inline-flex h-11 items-center justify-center rounded-md border border-border bg-white px-5 text-sm font-medium transition-colors hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30"
            href="#workflow"
          >
            View platform overview
          </a>
        </div>
        <p class="mt-8 max-w-xl text-sm leading-6 text-muted">
          Built for teams that need reliable contact operations and measured SMS campaigns, not a one-click broadcast tool.
        </p>
      </div>

      <div class="relative min-h-[480px] overflow-hidden rounded-lg border border-border bg-white p-4 shadow-sm" aria-label="Sendora SMS campaign workspace preview">
        <div class="absolute inset-x-0 top-0 h-20 bg-primary/10"></div>
        <div class="relative rounded-lg border border-border bg-background">
          <div class="flex items-center justify-between border-b border-border px-4 py-3">
            <div>
              <div class="text-sm font-medium">Course reminder</div>
              <div class="text-xs text-muted">Audience ready for review</div>
            </div>
            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">draft</span>
          </div>
          <div class="space-y-4 p-4">
            <div class="grid gap-2 sm:grid-cols-3 sm:gap-3">
              <div class="rounded-lg border border-border p-3">
                <div class="text-xs text-muted">Recipients</div>
                <div class="mt-1 text-2xl font-semibold">Ready</div>
              </div>
              <div class="rounded-lg border border-border p-3">
                <div class="text-xs text-muted">Message</div>
                <div class="mt-1 text-2xl font-semibold">Checked</div>
              </div>
              <div class="rounded-lg border border-border p-3">
                <div class="text-xs text-muted">Queue</div>
                <div class="mt-1 text-2xl font-semibold">Idle</div>
              </div>
            </div>

            <div class="rounded-lg border border-border p-4">
              <div class="mb-3 flex items-center justify-between text-sm">
                <span class="font-medium">Campaign readiness</span>
                <span class="text-muted">Pre-send checks</span>
              </div>
              <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full w-3/4 rounded-full bg-primary"></div>
              </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
              <div class="rounded-lg border border-border p-4">
                <div class="mb-3 flex items-center gap-2 text-sm font-medium">
                  <Tags class="size-4 text-primary" aria-hidden="true" focusable="false" />
                  Audience
                </div>
                <div class="space-y-2 text-xs">
                  <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
                    <span>Lists and tags</span>
                    <CheckCircle2 class="size-3.5 text-emerald-600" aria-hidden="true" focusable="false" />
                  </div>
                  <div class="flex items-center justify-between rounded-md bg-gray-50 px-3 py-2">
                    <span>Status exclusions</span>
                    <CheckCircle2 class="size-3.5 text-emerald-600" aria-hidden="true" focusable="false" />
                  </div>
                </div>
              </div>
              <div class="rounded-lg border border-border p-4">
                <div class="mb-3 flex items-center gap-2 text-sm font-medium">
                  <Clock3 class="size-4 text-primary" aria-hidden="true" focusable="false" />
                  Delivery control
                </div>
                <div class="space-y-2 text-xs">
                  <div class="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2">
                    <CheckCircle2 class="size-3.5 text-emerald-600" aria-hidden="true" focusable="false" />
                    Queue monitored
                  </div>
                  <div class="flex items-center gap-2 rounded-md bg-gray-50 px-3 py-2">
                    <Clock3 class="size-3.5 text-primary" aria-hidden="true" focusable="false" />
                    Schedule supported
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="workflow" class="border-y border-border bg-gray-50">
      <div class="mx-auto grid w-full max-w-7xl gap-4 px-4 py-10 sm:grid-cols-4 lg:px-8">
        <div v-for="(item, index) in workflow" :key="item" class="rounded-lg border border-border bg-white p-4">
          <div class="text-xs text-muted">0{{ index + 1 }}</div>
          <div class="mt-3 text-sm font-medium">{{ item }}</div>
        </div>
      </div>
    </section>

    <section id="control" class="mx-auto w-full max-w-7xl px-4 py-16 lg:px-8">
      <div class="max-w-2xl">
        <h2 class="text-3xl font-semibold tracking-tight">Everything needed before and after send.</h2>
        <p class="mt-4 text-muted">Keep contact management, audience choices, sending controls, and results close to the operational data that decides whether a campaign is ready.</p>
      </div>

      <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <article v-for="item in capabilities" :key="item.title" class="rounded-lg border border-border bg-white p-5 transition-colors hover:bg-gray-50">
          <component :is="item.icon" class="size-5 text-primary" aria-hidden="true" focusable="false" />
          <h3 class="mt-5 text-base font-semibold">{{ item.title }}</h3>
          <p class="mt-3 text-sm leading-6 text-muted">{{ item.body }}</p>
        </article>
      </div>
    </section>

    <section id="trust" class="border-t border-border bg-gray-50">
      <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 lg:grid-cols-[0.8fr_1fr] lg:px-8">
        <div>
          <ShieldCheck class="size-8 text-primary" aria-hidden="true" focusable="false" />
          <h2 class="mt-5 text-3xl font-semibold tracking-tight">Contact protection and recovery stay in the workflow.</h2>
          <p class="mt-4 text-muted">Sendora treats every campaign as an operational process that must remain understandable before, during, and after delivery.</p>
        </div>
        <div class="grid gap-3">
          <div v-for="item in trustItems" :key="item" class="flex gap-3 rounded-lg border border-border bg-white p-4 text-sm leading-6">
            <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-emerald-600" aria-hidden="true" focusable="false" />
            <span>{{ item }}</span>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-16 lg:px-8">
      <div class="rounded-lg border border-border bg-white p-6 sm:p-8 lg:flex lg:items-center lg:justify-between">
        <div class="max-w-2xl">
          <DatabaseZap class="size-6 text-primary" aria-hidden="true" focusable="false" />
          <h2 class="mt-5 text-3xl font-semibold tracking-tight">Bring SMS campaign work back under control.</h2>
          <p class="mt-3 text-muted">Manage the contact data, prepare the audience, send the campaign, and verify the result from one Sendora workspace.</p>
        </div>
        <Link class="mt-6 inline-flex h-11 items-center justify-center gap-2 rounded-md bg-primary px-5 text-sm font-medium text-white transition-colors hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 lg:mt-0" :href="route('login')">
          Log in to Sendora
          <ArrowRight class="size-4" aria-hidden="true" focusable="false" />
        </Link>
      </div>
    </section>
  </PublicLayout>
</template>
