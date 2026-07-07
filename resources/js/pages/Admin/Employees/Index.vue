<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const breadcrumbs = [
    {
        title: 'Karyawan',
        href: '/admin/employees',
    },
];

type Employee = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    position: string | null;
    department_id: number | null;
    work_schedule_id: number | null;
    department: string;
    work_schedule: string;
    is_active: boolean;
};

type Department = {
    id: number;
    name: string;
};

type WorkSchedule = {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
};

type Filters = {
    search: string | null;
    department_id: string | null;
    status: string | null;
};

const props = defineProps<{
    employees: Employee[];
    departments: Department[];
    workSchedules: WorkSchedule[];
    filters: Filters;
}>();

// MODAL TAMBAH
const isCreateModalOpen = ref(false);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    position: '',
    department_id: '',
    work_schedule_id: '',
});

const openCreateModal = () => {
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post('/admin/employees', {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.success('Karyawan berhasil ditambahkan.');
        },
        onError: () => {
            toast.error('Periksa kembali data karyawan.');
        },
    });
};

// MODAL EDIT
const isEditModalOpen = ref(false);
const selectedEmployee = ref<Employee | null>(null);

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    position: '',
    department_id: '',
    work_schedule_id: '',
});

const openEditModal = (employee: Employee) => {
    selectedEmployee.value = employee;

    editForm.name = employee.name;
    editForm.email = employee.email;
    editForm.phone = employee.phone ?? '';
    editForm.position = employee.position ?? '';
    editForm.department_id = employee.department_id ? String(employee.department_id) : '';
    editForm.work_schedule_id = employee.work_schedule_id ? String(employee.work_schedule_id) : '';

    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedEmployee.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!selectedEmployee.value) {
        return;
    }

    editForm.put(`/admin/employees/${selectedEmployee.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.success('Data karyawan berhasil diperbarui.');
        },
        onError: () => {
            toast.error('Periksa kembali data karyawan.');
        },
    });
};

// UBAH STATUS KARYAWAN
const isToggleModalOpen = ref(false);
const selectedToggleEmployee = ref<Employee | null>(null);

const openToggleModal = (employee: Employee) => {
    selectedToggleEmployee.value = employee;
    isToggleModalOpen.value = true;
};

const closeToggleModal = () => {
    selectedToggleEmployee.value = null;
    isToggleModalOpen.value = false;
};

const submitToggleStatus = () => {
    if (!selectedToggleEmployee.value) {
        return;
    }

    const employee = selectedToggleEmployee.value;

    router.patch(
        `/admin/employees/${employee.id}/toggle-status`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                closeToggleModal();

                toast.success(
                    employee.is_active
                        ? 'Karyawan berhasil dinonaktifkan.'
                        : 'Karyawan berhasil diaktifkan.',
                );
            },
            onError: () => {
                toast.error('Gagal mengubah status karyawan.');
            },
        },
    );
};

// RESET PASSWORD
const isResetPasswordModalOpen = ref(false);
const selectedResetPasswordEmployee = ref<Employee | null>(null);

const resetPasswordForm = useForm({
    password: '',
    password_confirmation: '',
});

const openResetPasswordModal = (employee: Employee) => {
    selectedResetPasswordEmployee.value = employee;
    isResetPasswordModalOpen.value = true;
};

const closeResetPasswordModal = () => {
    selectedResetPasswordEmployee.value = null;
    isResetPasswordModalOpen.value = false;
    resetPasswordForm.reset();
    resetPasswordForm.clearErrors();
};

const submitResetPassword = () => {
    if (!selectedResetPasswordEmployee.value) {
        return;
    }

    resetPasswordForm.patch(
        `/admin/employees/${selectedResetPasswordEmployee.value.id}/reset-password`,
        {
            preserveScroll: true,
            onSuccess: () => {
                closeResetPasswordModal();
                toast.success('Password karyawan berhasil direset.');
            },
            onError: () => {
                toast.error('Periksa kembali password baru.');
            },
        },
    );
};

// SEARCH DAN FILTER
const filterSearch = ref(props.filters.search ?? '');
const filterDepartmentId = ref(props.filters.department_id ?? '');
const filterStatus = ref(props.filters.status ?? '');

const applyFilters = () => {
    router.get(
        '/admin/employees',
        {
            search: filterSearch.value || undefined,
            department_id: filterDepartmentId.value || undefined,
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
    filterSearch.value = '';
    filterDepartmentId.value = '';
    filterStatus.value = '';

    router.get(
        '/admin/employees',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const hasActiveFilters = computed(() => {
    return (
        filterSearch.value !== '' ||
        filterDepartmentId.value !== '' ||
        filterStatus.value !== ''
    );
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Karyawan</h1>
                    <p class="text-muted-foreground">
                        Kelola Data Karyawan
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground cursor-pointer"
                >
                    Tambah Karyawan
                </button>
            </div>

            <div class="rounded-xl border bg-card p-5">
                <div class="mb-4 flex flex-col gap-1">
                    <h2 class="text-base font-semibold">Filter Karyawan</h2>
                    <p class="text-sm text-muted-foreground">
                        Cari dan filter data karyawan berdasarkan kebutuhan.
                    </p>
                </div>

                <div class="grid gap-4 lg:grid-cols-12">
                    <div class="space-y-2 lg:col-span-5">
                        <label class="text-sm font-medium">Cari Karyawan</label>
                        <input
                            v-model="filterSearch"
                            @keyup.enter="applyFilters"
                            type="text"
                            class="h-11 w-full rounded-lg border bg-background px-3 text-sm outline-none transition focus:border-primary"
                            placeholder="Cari nama, email, no HP, atau jabatan"
                        />
                    </div>

                    <div class="space-y-2 lg:col-span-3">
                        <label class="text-sm font-medium">Departemen</label>
                        <select
                            v-model="filterDepartmentId"
                            class="h-11 w-full rounded-lg border bg-background px-3 text-sm outline-none transition focus:border-primary"
                        >
                            <option value="">Semua Departemen</option>
                            <option
                                v-for="department in departments"
                                :key="department.id"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2 lg:col-span-2">
                        <label class="text-sm font-medium">Status</label>
                        <select
                            v-model="filterStatus"
                            class="h-11 w-full rounded-lg border bg-background px-3 text-sm outline-none transition focus:border-primary"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 lg:col-span-2">
                        <button
                            type="button"
                            @click="applyFilters"
                            class="h-11 flex-1 rounded-lg bg-primary px-4 text-sm font-medium text-primary-foreground transition hover:opacity-90"
                        >
                            Terapkan
                        </button>

                        <button
                            type="button"
                            @click="resetFilters"
                            class="h-11 rounded-lg border px-4 text-sm font-medium transition hover:bg-muted"
                            :disabled="!hasActiveFilters"
                            :class="!hasActiveFilters ? 'cursor-not-allowed opacity-50' : ''"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <div
                    v-if="hasActiveFilters"
                    class="mt-4 flex flex-wrap items-center gap-2 border-t pt-4"
                >
                    <span class="text-sm text-muted-foreground">Filter aktif:</span>

                    <span
                        v-if="filterSearch"
                        class="rounded-full bg-muted px-3 py-1 text-xs font-medium"
                    >
                        Search: {{ filterSearch }}
                    </span>

                    <span
                        v-if="filterDepartmentId"
                        class="rounded-full bg-muted px-3 py-1 text-xs font-medium"
                    >
                        Departemen dipilih
                    </span>

                    <span
                        v-if="filterStatus"
                        class="rounded-full bg-muted px-3 py-1 text-xs font-medium"
                    >
                        Status: {{ filterStatus === 'active' ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <p class="text-sm text-muted-foreground">
                    Menampilkan
                    <span class="font-medium text-foreground">
                        {{ employees.length }}
                    </span>
                    data karyawan.
                </p>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nama</th>
                                <th class="px-4 py-3 font-medium">Email</th>
                                <th class="px-4 py-3 font-medium">No HP</th>
                                <th class="px-4 py-3 font-medium">Jabatan</th>
                                <th class="px-4 py-3 font-medium">Departemen</th>
                                <th class="px-4 py-3 font-medium">Jadwal</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="employee in employees"
                                :key="employee.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ employee.name }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ employee.email }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ employee.phone ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ employee.position ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ employee.department }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ employee.work_schedule }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="employee.is_active"
                                        class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700"
                                    >
                                        Aktif
                                    </span>
                                    <span
                                        v-else
                                        class="rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700"
                                    >
                                        Nonaktif
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <button
                                                type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border text-lg font-bold hover:bg-muted"
                                            >
                                                ...
                                            </button>
                                        </DropdownMenuTrigger>

                                        <DropdownMenuContent align="end" class="w-44">
                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                @click="openEditModal(employee)"
                                            >
                                                Edit Karyawan
                                            </DropdownMenuItem>

                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                :class="
                                                    employee.is_active
                                                        ? 'text-red-600 focus:text-red-600'
                                                        : 'text-green-600 focus:text-green-600'
                                                "
                                                @click="openToggleModal(employee)"
                                            >
                                                {{ employee.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </DropdownMenuItem>

                                            <DropdownMenuSeparator />

                                            <DropdownMenuItem
                                                class="cursor-pointer"
                                                @click="openResetPasswordModal(employee)"
                                            >
                                                Reset Password
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </td>
                            </tr>

                            <tr v-if="employees.length === 0">
                                <td
                                    colspan="8"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada data karyawan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODAL TAMBAH KARYAWAN -->
        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-3xl rounded-xl border bg-background p-6 shadow-lg">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Tambah Karyawan</h2>
                        <p class="text-sm text-muted-foreground">
                            Buat akun karyawan baru untuk absensi.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="closeCreateModal"
                        class="rounded-lg border px-3 py-1 text-sm cursor-pointer"
                    >
                        Tutup
                    </button>
                </div>

                <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Nama</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Nama karyawan"
                        />
                        <p v-if="form.errors.name" class="text-sm text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="employee@attendly.test"
                        />
                        <p v-if="form.errors.email" class="text-sm text-red-500">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Minimal 8 karakter"
                        />
                        <p v-if="form.errors.password" class="text-sm text-red-500">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Konfirmasi Password</label>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Ulangi password"
                        />
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">No HP</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="08123456789"
                        />
                        <p v-if="form.errors.phone" class="text-sm text-red-500">
                            {{ form.errors.phone }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Jabatan</label>
                        <input
                            v-model="form.position"
                            type="text"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Staff IT"
                        />
                        <p v-if="form.errors.position" class="text-sm text-red-500">
                            {{ form.errors.position }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Departemen</label>
                        <select
                            v-model="form.department_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Pilih departemen</option>
                            <option
                                v-for="department in departments"
                                :key="department.id"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.department_id" class="text-sm text-red-500">
                            {{ form.errors.department_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Jadwal Kerja</label>
                        <select
                            v-model="form.work_schedule_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Pilih jadwal kerja</option>
                            <option
                                v-for="schedule in workSchedules"
                                :key="schedule.id"
                                :value="schedule.id"
                            >
                                {{ schedule.name }} ({{ schedule.start_time }} - {{ schedule.end_time }})
                            </option>
                        </select>
                        <p v-if="form.errors.work_schedule_id" class="text-sm text-red-500">
                            {{ form.errors.work_schedule_id }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 md:col-span-2">
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="rounded-lg border px-4 py-2 text-sm font-medium cursor-pointer"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50 cursor-pointer"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan Karyawan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT -->
         <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-3xl rounded-xl border bg-background p-6 shadow-lg">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">Edit Karyawan</h2>
                        <p class="text-sm text-muted-foreground">
                            Perbarui data karyawan Attendly.
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

                <form @submit.prevent="submitEdit" class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Nama</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Nama karyawan"
                        />
                        <p v-if="editForm.errors.name" class="text-sm text-red-500">
                            {{ editForm.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Email</label>
                        <input
                            v-model="editForm.email"
                            type="email"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="employee@attendly.test"
                        />
                        <p v-if="editForm.errors.email" class="text-sm text-red-500">
                            {{ editForm.errors.email }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">No HP</label>
                        <input
                            v-model="editForm.phone"
                            type="text"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="08123456789"
                        />
                        <p v-if="editForm.errors.phone" class="text-sm text-red-500">
                            {{ editForm.errors.phone }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Jabatan</label>
                        <input
                            v-model="editForm.position"
                            type="text"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Staff IT"
                        />
                        <p v-if="editForm.errors.position" class="text-sm text-red-500">
                            {{ editForm.errors.position }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Departemen</label>
                        <select
                            v-model="editForm.department_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Pilih departemen</option>
                            <option
                                v-for="department in departments"
                                :key="department.id"
                                :value="department.id"
                            >
                                {{ department.name }}
                            </option>
                        </select>
                        <p v-if="editForm.errors.department_id" class="text-sm text-red-500">
                            {{ editForm.errors.department_id }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Jadwal Kerja</label>
                        <select
                            v-model="editForm.work_schedule_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Pilih jadwal kerja</option>
                            <option
                                v-for="schedule in workSchedules"
                                :key="schedule.id"
                                :value="schedule.id"
                            >
                                {{ schedule.name }} ({{ schedule.start_time }} - {{ schedule.end_time }})
                            </option>
                        </select>
                        <p v-if="editForm.errors.work_schedule_id" class="text-sm text-red-500">
                            {{ editForm.errors.work_schedule_id }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 md:col-span-2">
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

        <!-- MODAL KONFIRMASI STATUS -->
         <div
            v-if="isToggleModalOpen && selectedToggleEmployee"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-xl border bg-background p-6 shadow-lg">
                <div class="space-y-3">
                    <h2 class="text-xl font-bold">
                        {{ selectedToggleEmployee.is_active ? 'Nonaktifkan Karyawan?' : 'Aktifkan Karyawan?' }}
                    </h2>

                    <p class="text-sm text-muted-foreground">
                        Apakah kamu yakin ingin
                        <span class="font-semibold">
                            {{ selectedToggleEmployee.is_active ? 'menonaktifkan' : 'mengaktifkan' }}
                        </span>
                        karyawan
                        <span class="font-semibold text-foreground">
                            {{ selectedToggleEmployee.name }}
                        </span>
                        ?
                    </p>

                    <p
                        v-if="selectedToggleEmployee.is_active"
                        class="rounded-lg bg-red-50 p-3 text-sm text-red-700"
                    >
                        Karyawan yang dinonaktifkan tidak akan dianggap sebagai karyawan aktif di sistem.
                    </p>

                    <p
                        v-else
                        class="rounded-lg bg-green-50 p-3 text-sm text-green-700"
                    >
                        Karyawan akan diaktifkan kembali dan bisa digunakan lagi di sistem.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeToggleModal"
                        class="rounded-lg border px-4 py-2 text-sm font-medium"
                    >
                        Batal
                    </button>

                    <button
                        type="button"
                        @click="submitToggleStatus"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-white"
                        :class="
                            selectedToggleEmployee.is_active
                                ? 'bg-red-600 hover:bg-red-700'
                                : 'bg-green-600 hover:bg-green-700'
                        "
                    >
                        {{ selectedToggleEmployee.is_active ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL RESET PASSWORD -->
         <div
            v-if="isResetPasswordModalOpen && selectedResetPasswordEmployee"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-xl border bg-background p-6 shadow-lg">
                <div class="mb-6 space-y-2">
                    <h2 class="text-xl font-bold">Reset Password</h2>

                    <p class="text-sm text-muted-foreground">
                        Reset password untuk karyawan
                        <span class="font-semibold text-foreground">
                            {{ selectedResetPasswordEmployee.name }}
                        </span>
                        .
                    </p>
                </div>

                <form @submit.prevent="submitResetPassword" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Password Baru</label>
                        <input
                            v-model="resetPasswordForm.password"
                            type="password"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Minimal 8 karakter"
                        />
                        <p
                            v-if="resetPasswordForm.errors.password"
                            class="text-sm text-red-500"
                        >
                            {{ resetPasswordForm.errors.password }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Konfirmasi Password Baru</label>
                        <input
                            v-model="resetPasswordForm.password_confirmation"
                            type="password"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Ulangi password baru"
                        />
                    </div>

                    <div class="rounded-lg bg-muted p-3 text-sm text-muted-foreground">
                        Setelah password direset, karyawan bisa login menggunakan password baru.
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeResetPasswordModal"
                            class="rounded-lg border px-4 py-2 text-sm font-medium"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="resetPasswordForm.processing"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {{
                                resetPasswordForm.processing
                                    ? 'Menyimpan...'
                                    : 'Reset Password'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>