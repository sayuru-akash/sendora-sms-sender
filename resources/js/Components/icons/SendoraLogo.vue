<script setup lang="ts">
// No props needed - class is passed via $attrs
</script>

<template>
  <svg
    :class="($attrs.class as string)"
    viewBox="0 0 40 40"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
  >
    <rect width="40" height="40" rx="10" fill="#4f46e5" />
    <path
      d="M12 20L18 14V17H28V23H18V26L12 20Z"
      fill="white"
      transform="rotate(-180 20 20)"
    />
    <path
      d="M14 16L26 16"
      stroke="white"
      stroke-width="2"
      stroke-linecap="round"
      opacity="0.5"
    />
    <path
      d="M14 20L24 20"
      stroke="white"
      stroke-width="2"
      stroke-linecap="round"
      opacity="0.3"
    />
  </svg>
</template>
