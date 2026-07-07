<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import EmployeeMobileLayout from '@/layouts/EmployeeMobileLayout.vue';

type HistoryItem = {
    date: string;
    day: string;
    status: string;
    status_label: string;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number;
    early_checkout_minutes: number;
};

type Filters = {
    start_date: string;
    end_date: string;
    today: string;
};

const props = defineProps<{
    items: HistoryItem[];
    filters: Filters;
}>();

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

const applyFilter = () => {
    if (!startDate.value || !endDate.value) {
        toast.error('Tanggal awal dan tanggal akhir wajib diisi.');
        return;
    }

    if (startDate.value > props.filters.today) {
        toast.error('Tanggal awal tidak boleh melebihi hari ini.');
        return;
    }

    if (endDate.value > props.filters.today) {
        toast.error('Tanggal akhir tidak boleh melebihi hari ini.');
        return;
    }

    if (endDate.value < startDate.value) {
        toast.error('Tanggal akhir tidak boleh sebelum tanggal awal.');
        return;
    }

    router.get(
        '/employee/history',
        {
            start_date: startDate.value,
            end_date: endDate.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const formatLateDuration = (minutes: number) => {
    if (minutes <= 0) {
        return '-';
    }

    if (minutes < 60) {
        return `${minutes} menit`;
    }

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    if (remainingMinutes === 0) {
        return `${hours} jam`;
    }

    return `${hours} jam ${remainingMinutes} menit`;
};

const statusClass = (status: string) => {
    if (status === 'late' || status === 'absent') {
        return 'bg-red-100 text-red-700';
    }

    if (status === 'present') {
        return 'bg-green-100 text-green-700';
    }

    if (['leave', 'sick'].includes(status)) {
        return 'bg-yellow-100 text-yellow-700';
    }

    if (['wfh', 'wfc'].includes(status)) {
        return 'bg-purple-100 text-purple-700';
    }

    if (status === 'off') {
        return 'bg-muted text-muted-foreground';
    }

    return 'bg-muted text-muted-foreground';
};
</script>

<template>
    <EmployeeMobileLayout
        title="Riwayat"
        subtitle="Riwayat absensi kamu."
    >
        <div class="space-y-4">
            <div class="rounded-2xl border bg-background p-4">
                <div class="mb-3">
                    <h2 class="font-semibold">Filter Tanggal</h2>
                    <p class="text-sm text-muted-foreground">
                        Pilih tanggal atau range tanggal.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">
                            Dari
                        </label>
                        <input
                            v-model="startDate"
                            type="date"
                            :max="props.filters.today"
                            class="h-10 w-full rounded-xl border bg-background px-3 text-sm"
                        />
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-medium text-muted-foreground">
                            Sampai
                        </label>
                        <input
                            v-model="endDate"
                            type="date"
                            :min="startDate"
                            :max="props.filters.today"
                            class="h-10 w-full rounded-xl border bg-background px-3 text-sm"
                        />
                    </div>
                </div>

                <button
                    type="button"
                    @click="applyFilter"
                    class="mt-3 h-10 w-full rounded-xl bg-primary text-sm font-semibold text-primary-foreground"
                >
                    Terapkan Filter
                </button>
            </div>

            <div v-if="items.length > 0" class="space-y-3">
                <div
                    v-for="item in items"
                    :key="item.date"
                    class="rounded-2xl border bg-background p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">
                                {{ item.day }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ item.date }}
                            </p>
                        </div>

                        <span
                            class="rounded-full px-2 py-1 text-xs font-medium"
                            :class="statusClass(item.status)"
                        >
                            {{ item.status_label }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                        <div class="rounded-xl bg-muted p-3">
                            <p class="text-xs text-muted-foreground">Masuk</p>
                            <p class="mt-1 font-semibold">
                                {{ item.check_in_at ?? '--:--' }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-muted p-3">
                            <p class="text-xs text-muted-foreground">Pulang</p>
                            <p class="mt-1 font-semibold">
                                {{ item.check_out_at ?? '--:--' }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-muted p-3">
                            <p class="text-xs text-muted-foreground">Telat</p>
                            <p class="mt-1 font-semibold">
                                {{ formatLateDuration(item.late_minutes) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-muted p-3">
                            <p class="text-xs text-muted-foreground">Pulang Cepat</p>
                            <p
                                class="mt-1 font-semibold"
                                :class="item.early_checkout_minutes > 0 ? 'text-orange-600' : ''"
                            >
                                {{ formatLateDuration(item.early_checkout_minutes) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-background p-4 text-center text-sm text-muted-foreground"
            >
                Belum ada riwayat absensi.
            </div>
        </div>
    </EmployeeMobileLayout>
</template>