<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import EmployeeMobileLayout from '@/layouts/EmployeeMobileLayout.vue';

type User = {
    name: string;
    email: string;
    position: string | null;
};

type Schedule = {
    name: string;
    start_time: string;
    end_time: string;
    late_tolerance_minutes: number;
};

type Attendance = {
    status: string;
    status_label: string;
    check_in_at: string | null;
    check_out_at: string | null;
    late_minutes: number;
    can_check_in: boolean;
    can_check_out: boolean;
    early_checkout_minutes: number;
};


defineProps<{
    user: User;
    schedule: Schedule | null;
    attendance: Attendance;
    today: string;
}>();

const checkInForm = useForm({});
const checkOutForm = useForm({});

const checkIn = () => {
    checkInForm.post('/employee/attendance/check-in', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Check-in berhasil.');
        },
        onError: () => {
            toast.error('Gagal check-in.');
        },
    });
};

const checkOut = () => {
    checkOutForm.patch('/employee/attendance/check-out', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Check-out berhasil.');
        },
        onError: () => {
            toast.error('Gagal check-out.');
        },
    });
};

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
    <EmployeeMobileLayout
        title="Attendly"
        subtitle="Absensi hari ini"
    >
        <div class="space-y-4">
            <div class="rounded-2xl border bg-background p-5">
                <p class="text-sm text-muted-foreground">Halo,</p>
                <h1 class="text-2xl font-bold">
                    {{ user.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ user.position ?? 'Karyawan' }}
                </p>
            </div>

            <div class="rounded-2xl border bg-background p-5">
                <p class="text-sm text-muted-foreground">Hari ini</p>
                <h2 class="mt-1 text-lg font-semibold">
                    {{ today }}
                </h2>
            </div>

            <div class="rounded-2xl border bg-background p-5">
                <p class="text-sm text-muted-foreground">Jadwal Kerja</p>

                <template v-if="schedule">
                    <h2 class="mt-1 text-lg font-semibold">
                        {{ schedule.name }}
                    </h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ schedule.start_time }} - {{ schedule.end_time }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Toleransi telat {{ schedule.late_tolerance_minutes }} menit
                    </p>
                </template>

                <p v-else class="mt-2 text-sm text-red-500">
                    Jadwal kerja belum diatur.
                </p>
            </div>

            <div class="rounded-2xl border bg-background p-5">
                <p class="text-sm text-muted-foreground">Status Absensi</p>

                <div class="mt-3 flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">
                            {{ attendance.status_label }}
                        </h2>

                        <p
                            v-if="attendance.late_minutes > 0"
                            class="mt-1 text-sm text-red-500"
                        >
                            Telat {{ formatLateDuration(attendance.late_minutes) }}
                        </p>

                        <p
                            v-if="attendance.early_checkout_minutes > 0"
                            class="mt-1 text-sm text-orange-500"
                        >
                            Pulang cepat {{ formatLateDuration(attendance.early_checkout_minutes) }}
                        </p>
                    </div>

                    <span
                        class="shrink-0 rounded-full px-3 py-1 text-xs font-medium"
                        :class="
                            attendance.status === 'late'
                                ? 'bg-red-100 text-red-700'
                                : ['leave', 'sick'].includes(attendance.status)
                                    ? 'bg-yellow-100 text-yellow-700'
                                    : ['wfh', 'wfc'].includes(attendance.status)
                                        ? 'bg-purple-100 text-purple-700'
                                        : attendance.status === 'absent'
                                            ? 'bg-red-100 text-red-700'
                                            : attendance.early_checkout_minutes > 0
                                                ? 'bg-orange-100 text-orange-700'
                                                : attendance.check_out_at
                                                    ? 'bg-blue-100 text-blue-700'
                                                    : attendance.check_in_at
                                                        ? 'bg-green-100 text-green-700'
                                                        : 'bg-muted text-muted-foreground'
                        "
                    >
                        {{ attendance.status_label }}
                    </span>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-muted p-3">
                        <p class="text-xs text-muted-foreground">Check-in</p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ attendance.check_in_at ?? '--:--' }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-muted p-3">
                        <p class="text-xs text-muted-foreground">Check-out</p>
                        <p class="mt-1 text-lg font-semibold">
                            {{ attendance.check_out_at ?? '--:--' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <button
                    type="button"
                    @click="checkIn"
                    :disabled="
                        checkInForm.processing ||
                        !schedule ||
                        !attendance.can_check_in
                    "
                    class="w-full rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        checkInForm.processing
                            ? 'Memproses...'
                            : attendance.can_check_in
                                ? 'Check-in Sekarang'
                                : 'Check-in Tidak Tersedia'
                    }}
                </button>

                <button
                    type="button"
                    @click="checkOut"
                    :disabled="
                        checkOutForm.processing ||
                        !attendance.can_check_out
                    "
                    class="w-full rounded-xl border px-4 py-3 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        checkOutForm.processing
                            ? 'Memproses...'
                            : attendance.check_out_at
                                ? 'Sudah Check-out'
                                : attendance.can_check_out
                                    ? 'Check-out Sekarang'
                                    : 'Check-out Tidak Tersedia'
                    }}
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <Link
                    href="/employee/history"
                    class="rounded-2xl border bg-background p-4 text-center text-sm font-semibold hover:bg-muted"
                >
                    Riwayat
                </Link>

                <Link
                    href="/employee/requests"
                    class="rounded-2xl border bg-background p-4 text-center text-sm font-semibold hover:bg-muted"
                >
                    Pengajuan
                </Link>
            </div>
        </div>
    </EmployeeMobileLayout>
</template>