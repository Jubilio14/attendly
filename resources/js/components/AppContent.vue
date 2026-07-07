<script setup lang="ts">
import { computed } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});

const className = computed(() => props.class);
</script>

<template>
    <SidebarInset
        v-if="props.variant === 'sidebar'"
        :class="['min-w-0 w-full flex-1', className]"
    >
        <slot />
    </SidebarInset>

    <main
        v-else
        :class="['flex h-full w-full flex-1 flex-col', className]"
    >
        <slot />
    </main>
</template>