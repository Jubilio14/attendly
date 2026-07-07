<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
];

type Stats = {
    total_employees: number;
    present_today: number;
    late_today: number;
    not_checked_in: number;
    checked_out: number;
    absent: number;
    leave: number;
    sick: number;
    wfh: number;
    wfc: number;
    pending_requests: number;
};

type EmployeeRow = {
    id: number;
    name: string;
    position: string | null;
    department: string;
    status: string;
    status_label: string;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number;
};

type PendingRequest = {
    id: number;
    employee_name: string;
    position: string | null;
    department: string;
    type_label: string;
    date_range: string;
};

defineProps<{
    stats: Stats;
    lateEmployees: EmployeeRow[];
    notCheckedInEmployees: EmployeeRow[];
    pendingRequests: PendingRequest[];
    today: string;
}>();

const formatLateDuration = (minutes: number) => {
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
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-bold">Dashboard</h1>
                <p class="text-muted-foreground">
                    Ringkasan kehadiran hari ini, {{ today }}.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Total Karyawan</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.total_employees }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Hadir</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.present_today }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Telat</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.late_today }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Belum Check-in</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.not_checked_in }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Pending Request</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.pending_requests }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Check-out</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.checked_out }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Tidak Masuk</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.absent }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Izin</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.leave }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Sakit</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.sick }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">WFH / WFC</p>
                    <h2 class="mt-1 text-2xl font-bold">
                        {{ stats.wfh + stats.wfc }}
                    </h2>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <div class="mb-4">
                        <h2 class="font-bold">Karyawan Telat</h2>
                        <p class="text-sm text-muted-foreground">
                            Maksimal 5 data hari ini.
                        </p>
                    </div>

                    <div v-if="lateEmployees.length > 0" class="space-y-3">
                        <div
                            v-for="employee in lateEmployees"
                            :key="employee.id"
                            class="rounded-lg bg-muted p-3"
                        >
                            <p class="font-medium">{{ employee.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ employee.position ?? 'Karyawan' }} · {{ employee.department }}
                            </p>
                            <p class="mt-1 text-xs text-red-500">
                                Telat {{ formatLateDuration(employee.late_minutes) }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        Tidak ada karyawan telat.
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="mb-4">
                        <h2 class="font-bold">Belum Check-in</h2>
                        <p class="text-sm text-muted-foreground">
                            Karyawan yang belum check-in.
                        </p>
                    </div>

                    <div v-if="notCheckedInEmployees.length > 0" class="space-y-3">
                        <div
                            v-for="employee in notCheckedInEmployees"
                            :key="employee.id"
                            class="rounded-lg bg-muted p-3"
                        >
                            <p class="font-medium">{{ employee.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ employee.position ?? 'Karyawan' }} · {{ employee.department }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        Semua karyawan sudah check-in atau memiliki status.
                    </p>
                </div>

                <div class="rounded-xl border p-4">
                    <div class="mb-4">
                        <h2 class="font-bold">Pending Requests</h2>
                        <p class="text-sm text-muted-foreground">
                            Pengajuan yang menunggu approval.
                        </p>
                    </div>

                    <div v-if="pendingRequests.length > 0" class="space-y-3">
                        <div
                            v-for="request in pendingRequests"
                            :key="request.id"
                            class="rounded-lg bg-muted p-3"
                        >
                            <p class="font-medium">{{ request.employee_name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ request.position ?? 'Karyawan' }} · {{ request.department }}
                            </p>
                            <p class="mt-1 text-xs">
                                {{ request.type_label }} · {{ request.date_range }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        Tidak ada request pending.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>