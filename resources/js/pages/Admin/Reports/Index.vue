<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    {
        title: 'Laporan',
        href: '/admin/reports',
    },
];

type Summary = {
    work_days: number;
    present: number;
    late: number;
    not_checked_in: number;
    absent: number;
    leave: number;
    sick: number;
    wfh: number;
    wfc: number;
};

type ReportRow = {
    id: number;
    name: string;
    email: string;
    position: string | null;
    department: string;
    schedule: string;
    is_active: boolean;
    summary: Summary;
};

type Totals = {
    employees: number;
    work_days: number;
    present: number;
    late: number;
    not_checked_in: number;
    absent: number;
    leave: number;
    sick: number;
    wfh: number;
    wfc: number;
};

type Filters = {
    month: string;
    search: string | null;
};

const props = defineProps<{
    reports: ReportRow[];
    totals: Totals;
    filters: Filters;
    periodLabel: string;
}>();

const filterSearch = ref(props.filters.search ?? '');
const filterMonth = ref(props.filters.month);

const exportUrl = computed(() => {
    const params = new URLSearchParams();

    if (filterSearch.value) {
        params.append('search', filterSearch.value);
    }

    if (filterMonth.value) {
        params.append('month', filterMonth.value);
    }

    return `/admin/reports/export?${params.toString()}`;
});

const applyFilters = () => {
    router.get(
        '/admin/reports',
        {
            search: filterSearch.value || undefined,
            month: filterMonth.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    const now = new Date();

    filterSearch.value = '';
    filterMonth.value = now.toISOString().slice(0, 7);

    router.get(
        '/admin/reports',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-bold">Laporan Bulanan</h1>
                <p class="text-muted-foreground">
                    Rekap kehadiran karyawan periode {{ periodLabel }}.
                </p>
            </div>

            <div class="rounded-xl border p-4">
                <div class="grid gap-3 md:grid-cols-12">
                    <div class="space-y-2 md:col-span-5">
                        <label class="text-sm font-medium">Cari Karyawan</label>
                        <input
                            v-model="filterSearch"
                            @keyup.enter="applyFilters"
                            type="text"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            placeholder="Cari nama, email, jabatan, atau departemen"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-3">
                        <label class="text-sm font-medium">Bulan</label>
                        <input
                            v-model="filterMonth"
                            type="month"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        />
                    </div>

                    <div class="flex items-end gap-2 md:col-span-4">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="h-10 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground"
                        >
                            Terapkan
                        </button>

                        <button
                            type="button"
                            @click="resetFilters"
                            class="h-10 rounded-lg border px-4 text-sm font-medium"
                        >
                            Reset
                        </button>

                        <a
                            :href="exportUrl"
                            class="h-10 rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted"
                        >
                            Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Karyawan</th>
                                <th class="px-4 py-3 font-medium">Departemen</th>
                                <th class="px-4 py-3 font-medium">Jadwal</th>
                                <th class="px-4 py-3 text-center font-medium">Hari Kerja</th>
                                <th class="px-4 py-3 text-center font-medium">Hadir</th>
                                <th class="px-4 py-3 text-center font-medium">Telat</th>
                                <th class="px-4 py-3 text-center font-medium">Belum Check-in</th>
                                <th class="px-4 py-3 text-center font-medium">Tidak Masuk</th>
                                <th class="px-4 py-3 text-center font-medium">Izin</th>
                                <th class="px-4 py-3 text-center font-medium">Sakit</th>
                                <th class="px-4 py-3 text-center font-medium">WFH</th>
                                <th class="px-4 py-3 text-center font-medium">WFC</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="report in reports"
                                :key="report.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ report.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ report.position ?? 'Karyawan' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ report.department }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ report.schedule }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.work_days }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.present }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.late }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.not_checked_in }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.absent }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.leave }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.sick }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.wfh }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ report.summary.wfc }}
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <a
                                        :href="`/admin/reports/${report.id}/detail?month=${filterMonth}`"
                                        class="rounded-lg border px-3 py-1 text-xs font-medium hover:bg-muted"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>

                            <tr v-if="reports.length === 0">
                                <td
                                    colspan="13"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada data laporan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>