<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

type Employee = {
    id: number;
    name: string;
    email: string;
    position: string | null;
    department: string;
    schedule: string;
    is_active: boolean;
};

type DailyReport = {
    date: string;
    day: string;
    status: string;
    status_label: string;
    check_in_at: string;
    check_out_at: string;
    late_minutes: number;
    early_checkout_minutes: number;
    notes: string;
};

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
    off: number;
    total_late_minutes: number;
    early_checkout_count: number;
    total_early_checkout_minutes: number;
};

type Filters = {
    month: string;
};

const props = defineProps<{
    employee: Employee;
    dailyReports: DailyReport[];
    summary: Summary;
    filters: Filters;
    periodLabel: string;
}>();

const breadcrumbs = [
    {
        title: 'Laporan',
        href: '/admin/reports',
    },
    {
        title: 'Detail',
        href: `/admin/reports/${props.employee.id}/detail`,
    },
];

const filterMonth = ref(props.filters.month);

const applyFilters = () => {
    router.get(
        `/admin/reports/${props.employee.id}/detail`,
        {
            month: filterMonth.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const backUrl = computed(() => {
    return `/admin/reports?month=${filterMonth.value}`;
});

const exportUrl = computed(() => {
    return `/admin/reports/${props.employee.id}/detail/export?month=${filterMonth.value}`;
});

const formatLateDuration = (minutes: number) => {
    if (!minutes) {
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
    switch (status) {
        case 'present':
            return 'bg-green-100 text-green-700';
        case 'late':
            return 'bg-yellow-100 text-yellow-700';
        case 'not_checked_in':
            return 'bg-gray-100 text-gray-700';
        case 'absent':
            return 'bg-red-100 text-red-700';
        case 'leave':
            return 'bg-blue-100 text-blue-700';
        case 'sick':
            return 'bg-purple-100 text-purple-700';
        case 'wfh':
            return 'bg-indigo-100 text-indigo-700';
        case 'wfc':
            return 'bg-cyan-100 text-cyan-700';
        case 'off':
            return 'bg-muted text-muted-foreground';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Detail Laporan Karyawan</h1>
                    <p class="text-muted-foreground">
                        Detail kehadiran {{ employee.name }} periode {{ periodLabel }}.
                    </p>
                </div>

                <div class="flex gap-2">
                    <a
                        :href="exportUrl"
                        class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Export Excel
                    </a>

                    <a
                        :href="backUrl"
                        class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted"
                    >
                        Kembali
                    </a>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <p class="text-sm text-muted-foreground">Nama</p>
                        <p class="font-medium">{{ employee.name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-muted-foreground">Email</p>
                        <p class="font-medium">{{ employee.email }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-muted-foreground">Jabatan</p>
                        <p class="font-medium">{{ employee.position ?? '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-muted-foreground">Departemen</p>
                        <p class="font-medium">{{ employee.department }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-muted-foreground">Jadwal</p>
                        <p class="font-medium">{{ employee.schedule }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-muted-foreground">Status Karyawan</p>
                        <span
                            class="mt-1 inline-flex rounded-full px-2 py-1 text-xs font-medium"
                            :class="
                                employee.is_active
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-red-100 text-red-700'
                            "
                        >
                            {{ employee.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <div class="grid gap-3 md:grid-cols-12">
                    <div class="space-y-2 md:col-span-3">
                        <label class="text-sm font-medium">Bulan</label>
                        <input
                            v-model="filterMonth"
                            type="month"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        />
                    </div>

                    <div class="flex items-end gap-2 md:col-span-9">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="h-10 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground"
                        >
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                    <span>
                        Hari Kerja:
                        <strong class="text-foreground">{{ summary.work_days }}</strong>
                    </span>

                    <span>
                        Hadir:
                        <strong class="text-foreground">{{ summary.present }}</strong>
                    </span>

                    <span>
                        Telat:
                        <strong class="text-foreground">{{ summary.late }}</strong>
                    </span>

                    <span>
                        Belum Check-in:
                        <strong class="text-foreground">{{ summary.not_checked_in }}</strong>
                    </span>

                    <span>
                        Tidak Masuk:
                        <strong class="text-foreground">{{ summary.absent }}</strong>
                    </span>

                    <span>
                        Izin:
                        <strong class="text-foreground">{{ summary.leave }}</strong>
                    </span>

                    <span>
                        Sakit:
                        <strong class="text-foreground">{{ summary.sick }}</strong>
                    </span>

                    <span>
                        WFH:
                        <strong class="text-foreground">{{ summary.wfh }}</strong>
                    </span>

                    <span>
                        WFC:
                        <strong class="text-foreground">{{ summary.wfc }}</strong>
                    </span>

                    <span>
                        Libur:
                        <strong class="text-foreground">{{ summary.off }}</strong>
                    </span>

                    <span>
                        Total Telat:
                        <strong class="text-foreground">
                            {{ formatLateDuration(summary.total_late_minutes) }}
                        </strong>
                    </span>

                    <span>
                        Pulang Cepat:
                        <strong class="text-foreground">
                            {{ summary.early_checkout_count }}x
                        </strong>
                    </span>

                    <span>
                        Total Pulang Cepat:
                        <strong class="text-orange-600">
                            {{ formatLateDuration(summary.total_early_checkout_minutes) }}
                        </strong>
                    </span>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Tanggal</th>
                                <th class="px-4 py-3 font-medium">Hari</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Check-in</th>
                                <th class="px-4 py-3 font-medium">Check-out</th>
                                <th class="px-4 py-3 font-medium">Telat</th>
                                <th class="px-4 py-3 font-medium">Pulang Cepat</th>
                                <th class="px-4 py-3 font-medium">Catatan</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="dailyReport in dailyReports"
                                :key="dailyReport.date"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3">
                                    {{ dailyReport.date }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ dailyReport.day }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="statusClass(dailyReport.status)"
                                    >
                                        {{ dailyReport.status_label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    {{ dailyReport.check_in_at }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ dailyReport.check_out_at }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ formatLateDuration(dailyReport.late_minutes) }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        :class="dailyReport.early_checkout_minutes > 0 ? 'font-medium text-orange-600' : ''"
                                    >
                                        {{ formatLateDuration(dailyReport.early_checkout_minutes) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    {{ dailyReport.notes }}
                                </td>
                            </tr>

                            <tr v-if="dailyReports.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada data detail laporan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>