<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    {
        title: 'Departemen',
        href: '/admin/departments',
    },
];

type Department = {
    id: number;
    name: string;
    is_active: boolean;
    employees_count: number;
};

type Filters = {
    search: string | null;
    status: string | null;
};

const props = defineProps<{
    departments: Department[];
    filters: Filters;
}>();

const filterSearch = ref(props.filters.search ?? '');
const filterStatus = ref(props.filters.status ?? '');

const applyFilters = () => {
    router.get(
        '/admin/departments',
        {
            search: filterSearch.value || undefined,
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
    filterStatus.value = '';

    router.get(
        '/admin/departments',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedDepartment = ref<Department | null>(null);

const createForm = useForm({
    name: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    is_active: true,
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.is_active = true;

    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post('/admin/departments', {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateModal();
            toast.success('Departemen berhasil ditambahkan.');
        },
        onError: () => {
            toast.error('Periksa kembali data departemen.');
        },
    });
};

const openEditModal = (department: Department) => {
    selectedDepartment.value = department;

    editForm.name = department.name;
    editForm.is_active = department.is_active;
    editForm.clearErrors();

    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    selectedDepartment.value = null;
    isEditModalOpen.value = false;
    editForm.reset();
    editForm.clearErrors();
};

const submitEdit = () => {
    if (!selectedDepartment.value) {
        return;
    }

    editForm.put(`/admin/departments/${selectedDepartment.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            toast.success('Departemen berhasil diperbarui.');
        },
        onError: (errors) => {
            toast.error(errors.department ?? 'Periksa kembali data departemen.');
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Departemen</h1>
                    <p class="text-muted-foreground">
                        Kelola departemen karyawan Attendly.
                    </p>
                </div>

                <button
                    type="button"
                    @click="openCreateModal"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                >
                    Tambah Departemen
                </button>
            </div>

            <div class="rounded-xl border p-4">
                <div class="grid gap-3 md:grid-cols-12">
                    <div class="space-y-2 md:col-span-5">
                        <label class="text-sm font-medium">Cari Departemen</label>
                        <input
                            v-model="filterSearch"
                            @keyup.enter="applyFilters"
                            type="text"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            placeholder="Cari nama departemen"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-3">
                        <label class="text-sm font-medium">Status</label>
                        <select
                            v-model="filterStatus"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
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
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 font-medium">Nama Departemen</th>
                                <th class="px-4 py-3 font-medium">Jumlah Karyawan</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="department in departments"
                                :key="department.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ department.name }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ department.employees_count }} karyawan
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="
                                            department.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ department.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <button
                                        type="button"
                                        @click="openEditModal(department)"
                                        class="rounded-lg border px-3 py-1 text-xs font-medium hover:bg-muted"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="departments.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada data departemen.
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
                <div class="w-full max-w-md rounded-xl border bg-background p-6 shadow-lg">
                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">Tambah Departemen</h2>
                            <p class="text-sm text-muted-foreground">
                                Buat departemen baru.
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
                            <label class="text-sm font-medium">Nama Departemen</label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                                placeholder="Contoh: IT"
                            />
                            <p v-if="createForm.errors.name" class="text-sm text-red-500">
                                {{ createForm.errors.name }}
                            </p>
                        </div>

                        <label class="flex items-center gap-2 rounded-lg border p-3 text-sm">
                            <input
                                v-model="createForm.is_active"
                                type="checkbox"
                            />
                            Departemen aktif
                        </label>

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
                                {{ createForm.processing ? 'Menyimpan...' : 'Simpan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Edit -->
            <div
                v-if="isEditModalOpen && selectedDepartment"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-md rounded-xl border bg-background p-6 shadow-lg">
                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-xl font-bold">Edit Departemen</h2>
                            <p class="text-sm text-muted-foreground">
                                Perbarui data departemen.
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
                            <label class="text-sm font-medium">Nama Departemen</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            />
                            <p v-if="editForm.errors.name" class="text-sm text-red-500">
                                {{ editForm.errors.name }}
                            </p>
                        </div>

                        <label class="flex items-center gap-2 rounded-lg border p-3 text-sm">
                            <input
                                v-model="editForm.is_active"
                                type="checkbox"
                            />
                            Departemen aktif
                        </label>

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