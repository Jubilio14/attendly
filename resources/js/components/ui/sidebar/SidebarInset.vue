<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <main
    data-slot="sidebar-inset"
    :class="cn(
      'relative flex min-h-screen min-w-0 w-full flex-1 flex-col bg-background overflow-x-hidden',
      props.class,
    )"
  >
    <slot />
  </main>
</template>