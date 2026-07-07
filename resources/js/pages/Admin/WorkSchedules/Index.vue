<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    {
        title: 'Jadwal Kerja',
        href: '/admin/work-schedules',
    },
];

type WorkSchedule = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    late_tolerance_minutes: number;
    work_days: string[];
    work_days_label: string;
    is_default: boolean;
    is_active: boolean;
};

defineProps<{
    workSchedules: WorkSchedule[];
}>();

const dayOptions = [
    { value: 'monday', label: 'Senin' },
    { value: 'tuesday', label: 'Selasa' },
    { value: 'wednesday', label: 'Rabu' },
    { value: 'thursday', label: 'Kamis' },
    { value: 'friday', label: 'Jumat' },
    { value: 'saturday', label: 'Sabtu' },
    { value: 'sunday', label: 'Minggu' },
];

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedSchedule = ref<WorkSchedule | null>(null);

const createForm = useForm({
    name: '',
    start_time: '08:00',
    end_time: '17:00',
    late_tolerance_minutes: 15,
    work_days: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    is_default: false,
    is_active: true,
});

const editForm = useForm({
    name: '',
    start_time: '',
    end_time: '',
    late_tolerance_minutes: 0,
    work_days: [] as string[],
    is_default: false,
    is_active: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();

    createForm.start_time = '08:00';
    createForm.end_time = '17:00';
    createForm.late_tolerance_minutes = 15;
    createForm.work_days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    createForm.is_default = false;
    createForm.is_active = true;

    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post('/admin/work-schedules', {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.success('Jadwal kerja berhasil ditambahkan.');
        },
        onError: () => {
            toast.error('Periksa kembali data jadwal kerja.');
        },
    });
};

const openEditModal = (schedule: WorkSchedule) => {
    selectedSchedule.value = schedule;

    editForm.name = schedule.name;
    editForm.start_time = schedule.start_time.slice(0, 5);
    editForm.end_time = schedule.end_time.slice(0, 5);
    editForm.late_tolerance_minutes = schedule.late_tolerance_minutes;
    editForm.work_days = [...schedule.work_days];
    editForm.is_default = schedule.is_default;
    editForm.is_active = schedule.is_active;
    editForm.clearErrors();

    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    selectedSchedule.value = null;
    isEditModalOpen.value = false;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!selectedSchedule.value) {
        return;
    }

    editForm.put(`/admin/work-schedules/${selectedSchedule.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.success('Jadwal kerja berhasil diperbarui.');
        },
        onError: (errors) => {
            toast.error(errors.work_schedule ?? 'Periksa kembali data jadwal kerja.');
        },
    });
};

const toggleCreateDay = (day: string) => {
    if (createForm.work_days.includes(day)) {
        createForm.work_days = createForm.work_days.filter((item) => item !== day);
        return;
    }

    createForm.work_days = [...createForm.work_days, day];
};

const toggleEditDay = (day: string) => {
    if (editForm.work_days.includes(day)) {
        editForm.work_days = editForm.work_days.filter((item) => item !== day);
        return;
    }

    editForm.work_days = [...editForm.work_days, day];
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Jadwal Kerja</h1>
                    <p class="text-muted-foreground">
                        Kelola jadwal kerja dan toleransi keterlambatan karyawan.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                >
                    Tambah Jadwal
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nama Jadwal</th>
                                <th class="px-4 py-3 font-medium">Jam Kerja</th>
                                <th class="px-4 py-3 font-medium">Toleransi</th>
                                <th class="px-4 py-3 font-medium">Hari Kerja</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="schedule in workSchedules"
                                :key="schedule.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ schedule.name }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ schedule.start_time }} - {{ schedule.end_time }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ schedule.late_tolerance_minutes }} menit
                                </td>

                                <td class="px-4 py-3">
                                    {{ schedule.work_days_label }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            class="rounded-full px-2 py-1 text-xs font-medium"
                                            :class="
                                                schedule.is_active
                                                    ? 'bg-green-100 text-green-700'
                                                    : 'bg-red-100 text-red-700'
                                            "
                                        >
                                            {{ schedule.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>

                                        <span
                                            v-if="schedule.is_default"
                                            class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700"
                                        >
                                            Default
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        @click="openEditModal(schedule)"
                                        class="rounded-lg border px-3 py-1 text-xs font-medium hover:bg-muted"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="workSchedules.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada jadwal kerja.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Create -->
            <div
                v-if="isCreateModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-2xl rounded-xl border bg-background p-6 shadow-lg">
                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">Tambah Jadwal Kerja</h2>
                            <p class="text-sm text-muted-foreground">
                                Buat jadwal kerja baru untuk karyawan.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="rounded-lg border px-3 py-1 text-sm"
                        >
                            Tutup
                        </button>
                    </div>

                    <form @submit.prevent="submitCreate" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Jadwal</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                placeholder="Contoh: Regular Office"
                            />
                            <p v-if="createForm.errors.name" class="text-sm text-red-500">
                                {{ createForm.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Jam Masuk</label>
                                <input
                                    v-model="createForm.start_time"
                                    type="time"
                                    class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                />
                                <p v-if="createForm.errors.start_time" class="text-sm text-red-500">
                                    {{ createForm.errors.start_time }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Jam Pulang</label>
                                <input
                                    v-model="createForm.end_time"
                                    type="time"
                                    class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                />
                                <p v-if="createForm.errors.end_time" class="text-sm text-red-500">
                                    {{ createForm.errors.end_time }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Toleransi Telat</label>
                                <input
                                    v-model="createForm.late_tolerance_minutes"
                                    type="number"
                                    min="0"
                                    class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                />
                                <p
                                    v-if="createForm.errors.late_tolerance_minutes"
                                    class="text-sm text-red-500"
                                >
                                    {{ createForm.errors.late_tolerance_minutes }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Hari Kerja</label>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                <label
                                    v-for="day in dayOptions"
                                    :key="day.value"
                                    class="flex items-center gap-2 rounded-lg border p-3 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="createForm.work_days.includes(day.value)"
                                        @change="toggleCreateDay(day.value)"
                                    />
                                    {{ day.label }}
                                </label>
                            </div>
                            <p v-if="createForm.errors.work_days" class="text-sm text-red-500">
                                {{ createForm.errors.work_days }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 rounded-lg border p-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="createForm.is_default"
                                    type="checkbox"
                                />
                                Jadikan jadwal default
                            </label>

                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="createForm.is_active"
                                    type="checkbox"
                                />
                                Jadwal aktif
                            </label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="closeCreateModal"
                                class="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Batal
                            </button>

                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                            >
                                {{ createForm.processing ? 'Menyimpan...' : 'Simpan Jadwal' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Edit -->
            <div
                v-if="isEditModalOpen && selectedSchedule"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-2xl rounded-xl border bg-background p-6 shadow-lg">
                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">Edit Jadwal Kerja</h2>
                            <p class="text-sm text-muted-foreground">
                                Perbarui jadwal kerja.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closeEditModal"
                            class="rounded-lg border px-3 py-1 text-sm"
                        >
                            Tutup
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Nama Jadwal</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            />
                            <p v-if="editForm.errors.name" class="text-sm text-red-500">
                                {{ editForm.errors.name }}
                            </p>
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">Jam Masuk</label>
                                <input
                                    v-model="editForm.start_time"
                                    type="time"
                                    class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Jam Pulang</label>
                                <input
                                    v-model="editForm.end_time"
                                    type="time"
                                    class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium">Toleransi Telat</label>
                                <input
                                    v-model="editForm.late_tolerance_minutes"
                                    type="number"
                                    min="0"
                                    class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Hari Kerja</label>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                <label
                                    v-for="day in dayOptions"
                                    :key="day.value"
                                    class="flex items-center gap-2 rounded-lg border p-3 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="editForm.work_days.includes(day.value)"
                                        @change="toggleEditDay(day.value)"
                                    />
                                    {{ day.label }}
                                </label>
                            </div>
                            <p v-if="editForm.errors.work_days" class="text-sm text-red-500">
                                {{ editForm.errors.work_days }}
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 rounded-lg border p-3">
                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="editForm.is_default"
                                    type="checkbox"
                                />
                                Jadikan jadwal default
                            </label>

                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="editForm.is_active"
                                    type="checkbox"
                                />
                                Jadwal aktif
                            </label>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="closeEditModal"
                                class="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Batal
                            </button>

                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                            >
                                {{ editForm.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>