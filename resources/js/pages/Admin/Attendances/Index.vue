<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    {
        title: 'Absensi',
        href: '/admin/attendances',
    },
];

type AttendanceRow = {
    employee_id: number;
    name: string;
    email: string;
    position: string | null;
    department: string;
    schedule: string;
    status: string;
    status_label: string;
    check_in_at: string;
    check_out_at: string;
    raw_check_in_at: string | null;
    raw_check_out_at: string | null;
    late_minutes: number;
    has_checked_out: boolean;
    early_checkout_minutes: number;
    notes: string | null;
};

type Stats = {
    total: number;
    present: number;
    late: number;
    not_checked_in: number;
    checked_out: number;
    absent: number;
    leave: number;
    sick: number;
    wfh: number;
    wfc: number;
};

type Filters = {
    date: string;
    status: string | null;
};

const props = defineProps<{
    rows: AttendanceRow[];
    stats: Stats;
    filters: Filters;
}>();

const filterDate = ref(props.filters.date);
const filterStatus = ref(props.filters.status ?? '');

const applyFilters = () => {
    router.get(
        '/admin/attendances',
        {
            date: filterDate.value,
            status: filterStatus.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterDate.value = new Date().toISOString().slice(0, 10);
    filterStatus.value = '';

    router.get(
        '/admin/attendances',
        {},
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

const formatDuration = (minutes: number) => {
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
    if (status === 'late') {
        return 'bg-red-100 text-red-700';
    }

    if (status === 'present') {
        return 'bg-green-100 text-green-700';
    }

    if (status === 'checked_out') {
        return 'bg-blue-100 text-blue-700';
    }

    return 'bg-muted text-muted-foreground';
};

const correctionStatuses = [
    { value: 'present', label: 'Hadir' },
    { value: 'absent', label: 'Tidak Masuk' },
    { value: 'leave', label: 'Izin' },
    { value: 'sick', label: 'Sakit' },
    { value: 'wfh', label: 'WFH' },
    { value: 'wfc', label: 'WFC' },
];

const presenceStatuses = ['present', 'late'];

const isCorrectionModalOpen = ref(false);
const selectedAttendance = ref<AttendanceRow | null>(null);

const correctionForm = useForm({
    attendance_date: '',
    status: 'present',
    check_in_at: '',
    check_out_at: '',
    notes: '',
});

const openCorrectionModal = (attendance: AttendanceRow) => {
    selectedAttendance.value = attendance;

    correctionForm.clearErrors();

    correctionForm.attendance_date = filterDate.value;

    correctionForm.status = ['not_checked_in', 'late'].includes(attendance.status)
        ? 'present'
        : attendance.status;

    correctionForm.check_in_at = attendance.raw_check_in_at ?? '';
    correctionForm.check_out_at = attendance.raw_check_out_at ?? '';
    correctionForm.notes = attendance.notes ?? '';

    isCorrectionModalOpen.value = true;
};

const closeCorrectionModal = () => {
    selectedAttendance.value = null;
    isCorrectionModalOpen.value = false;

    correctionForm.reset();
    correctionForm.clearErrors();
};

const submitCorrection = () => {
    if (!selectedAttendance.value) {
        return;
    }

    correctionForm.patch(
        `/admin/attendances/${selectedAttendance.value.employee_id}/correction`,
        {
            preserveScroll: true,
            onSuccess: () => {
                closeCorrectionModal();
                toast.success('Absensi berhasil dikoreksi.');
            },
            onError: () => {
                toast.error('Periksa kembali data koreksi absensi.');
            },
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full w-full min-w-0 flex-1 flex-col gap-4 p-6">
            <div>
                <h1 class="text-2xl font-bold">Absensi</h1>
                <p class="text-muted-foreground">
                    Pantau kehadiran karyawan berdasarkan tanggal.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-5">
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Total</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.total }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Hadir</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.present }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Telat</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.late }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Belum Check-in</p>
                    <h2 class="mt-1 text-2xl font-bold">
                        {{ stats.not_checked_in }}
                    </h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Check-out</p>
                    <h2 class="mt-1 text-2xl font-bold">
                        {{ stats.checked_out }}
                    </h2>
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
                    <p class="text-sm text-muted-foreground">WFH</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.wfh }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">WFC</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.wfc }}</h2>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Tanggal</label>
                        <input
                            v-model="filterDate"
                            type="date"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Status</label>
                        <select
                            v-model="filterStatus"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        >
                            <option value="">Semua Status</option>
                            <option value="present">Hadir</option>
                            <option value="late">Telat</option>
                            <option value="checked_out">Sudah Check-out</option>
                            <option value="not_checked_in">Belum Check-in</option>
                            <option value="absent">Tidak Masuk</option>
                            <option value="leave">Izin</option>
                            <option value="sick">Sakit</option>
                            <option value="wfh">WFH</option>
                            <option value="wfc">WFC</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-2">
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
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nama</th>
                                <th class="px-4 py-3 font-medium">Departemen</th>
                                <th class="px-4 py-3 font-medium">Jadwal</th>
                                <th class="px-4 py-3 font-medium">Check-in</th>
                                <th class="px-4 py-3 font-medium">Check-out</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Telat</th>
                                <th class="px-4 py-3 font-medium">Pulang Cepat</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="row in rows"
                                :key="row.employee_id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ row.name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ row.position ?? 'Karyawan' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ row.department }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ row.schedule }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ row.check_in_at ?? '--:--' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ row.check_out_at ?? '--:--' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="statusClass(row.status)"
                                    >
                                        {{ row.status_label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    {{ formatLateDuration(row.late_minutes) }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        v-if="row.early_checkout_minutes > 0"
                                        class="font-medium text-orange-600"
                                    >
                                        {{ formatDuration(row.early_checkout_minutes) }}
                                    </span>

                                    <span v-else>
                                        -
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        @click="openCorrectionModal(row)"
                                        class="rounded-lg border px-3 py-1 text-xs font-medium hover:bg-muted"
                                    >
                                        Koreksi
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="rows.length === 0">
                                <td
                                    colspan="9"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada data absensi.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL -->
         <div
            v-if="isCorrectionModalOpen && selectedAttendance"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-lg rounded-xl border bg-background p-6 shadow-lg">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Koreksi Absensi</h2>
                        <p class="text-sm text-muted-foreground">
                            {{ selectedAttendance.name }} - {{ correctionForm.attendance_date }}
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="closeCorrectionModal"
                        class="rounded-lg border px-3 py-1 text-sm"
                    >
                        Tutup
                    </button>
                </div>

                <form @submit.prevent="submitCorrection" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Tanggal</label>
                        <input
                            v-model="correctionForm.attendance_date"
                            type="date"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        />

                        <p
                            v-if="correctionForm.errors.attendance_date"
                            class="text-sm text-red-500"
                        >
                            {{ correctionForm.errors.attendance_date }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Status</label>
                        <select
                            v-model="correctionForm.status"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        >
                            <option
                                v-for="status in correctionStatuses"
                                :key="status.value"
                                :value="status.value"
                            >
                                {{ status.label }}
                            </option>
                        </select>

                        <p
                            v-if="correctionForm.errors.status"
                            class="text-sm text-red-500"
                        >
                            {{ correctionForm.errors.status }}
                        </p>
                    </div>

                    <div
                        v-if="presenceStatuses.includes(correctionForm.status)"
                        class="grid gap-3 md:grid-cols-2"
                    >
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Check-in</label>
                            <input
                                v-model="correctionForm.check_in_at"
                                type="time"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            />

                            <p
                                v-if="correctionForm.errors.check_in_at"
                                class="text-sm text-red-500"
                            >
                                {{ correctionForm.errors.check_in_at }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Check-out</label>
                            <input
                                v-model="correctionForm.check_out_at"
                                type="time"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            />

                            <p
                                v-if="correctionForm.errors.check_out_at"
                                class="text-sm text-red-500"
                            >
                                {{ correctionForm.errors.check_out_at }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-lg border bg-muted/40 p-3 text-sm text-muted-foreground"
                    >
                        Untuk status ini, jam check-in dan check-out akan dikosongkan.
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Catatan</label>
                        <textarea
                            v-model="correctionForm.notes"
                            rows="3"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Contoh: Koreksi manual oleh admin karena karyawan lupa check-in."
                        />

                        <p
                            v-if="correctionForm.errors.notes"
                            class="text-sm text-red-500"
                        >
                            {{ correctionForm.errors.notes }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closeCorrectionModal"
                            class="rounded-lg border px-4 py-2 text-sm font-medium"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="correctionForm.processing"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {{ correctionForm.processing ? 'Menyimpan...' : 'Simpan Koreksi' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>