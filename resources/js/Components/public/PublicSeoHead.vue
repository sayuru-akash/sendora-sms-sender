<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { SeoMeta } from '@/types/seo';

withDefaults(defineProps<{
  seo: SeoMeta;
  type?: 'article' | 'website';
}>(), {
  type: 'website',
});
</script>

<template>
  <Head :title="seo.title">
    <meta head-key="description" name="description" :content="seo.description" />
    <meta head-key="robots" name="robots" :content="seo.robots" />
    <link head-key="canonical" rel="canonical" :href="seo.canonical" />
    <meta head-key="og:type" property="og:type" :content="type" />
    <meta head-key="og:site_name" property="og:site_name" :content="seo.siteName" />
    <meta head-key="og:locale" property="og:locale" :content="seo.locale" />
    <meta head-key="og:title" property="og:title" :content="seo.fullTitle" />
    <meta head-key="og:description" property="og:description" :content="seo.description" />
    <meta head-key="og:url" property="og:url" :content="seo.canonical" />
    <meta head-key="og:image" property="og:image" :content="seo.image" />
    <meta head-key="og:image:alt" property="og:image:alt" :content="seo.imageAlt" />
    <meta head-key="og:image:width" property="og:image:width" :content="String(seo.imageWidth)" />
    <meta head-key="og:image:height" property="og:image:height" :content="String(seo.imageHeight)" />
    <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
    <meta head-key="twitter:title" name="twitter:title" :content="seo.fullTitle" />
    <meta head-key="twitter:description" name="twitter:description" :content="seo.description" />
    <meta head-key="twitter:image" name="twitter:image" :content="seo.image" />
    <meta head-key="twitter:image:alt" name="twitter:image:alt" :content="seo.imageAlt" />
  </Head>
</template>
