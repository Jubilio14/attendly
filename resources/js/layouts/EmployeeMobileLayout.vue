<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Home,
    ClipboardList,
    FileText,
    User,
} from '@lucide/vue';

type Props = {
    title: string;
    subtitle?: string;
};

defineProps<Props>();

const page = usePage();

const navItems = [
    {
        title: 'Home',
        href: '/employee/home',
        icon: Home,
    },
    {
        title: 'Riwayat',
        href: '/employee/history',
        icon: ClipboardList,
    },
    {
        title: 'Pengajuan',
        href: '/employee/requests',
        icon: FileText,
    },
    {
        title: 'Profil',
        href: '/employee/profile',
        icon: User,
    },
];

const isActive = (href: string) => {
    return page.url.startsWith(href);
};
</script>

<template>
    <div class="min-h-screen bg-muted/30">
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col bg-background">
            <header class="sticky top-0 z-20 border-b bg-background/95 px-4 py-3 backdrop-blur">
                <div>
                    <h1 class="text-xl font-bold">
                        {{ title }}
                    </h1>

                    <p
                        v-if="subtitle"
                        class="text-sm text-muted-foreground"
                    >
                        {{ subtitle }}
                    </p>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto px-4 py-4 pb-28">
                <slot />
            </main>

            <nav class="fixed bottom-0 left-1/2 z-30 w-full max-w-md -translate-x-1/2 border-t bg-background/95 px-4 pb-4 pt-2 backdrop-blur">
                <div class="grid grid-cols-4 gap-2 rounded-2xl border bg-background p-2 shadow-lg">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="flex flex-col items-center justify-center gap-1 rounded-xl px-2 py-2 text-xs font-medium transition"
                        :class="
                            isActive(item.href)
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                        "
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5"
                        />

                        <span>
                            {{ item.title }}
                        </span>
                    </Link>
                </div>
            </nav>
        </div>
    </div>
</template>