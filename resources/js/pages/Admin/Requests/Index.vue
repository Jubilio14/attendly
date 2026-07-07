<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import AppLayout from '@/layouts/AppLayout.vue';

const breadcrumbs = [
    {
        title: 'Requests',
        href: '/admin/requests',
    },
];

type AttendanceRequest = {
    id: number;
    employee_name: string;
    employee_email: string;
    position: string | null;
    department: string;
    type: string;
    type_label: string;
    start_date: string;
    end_date: string;
    reason: string;
    status: string;
    status_label: string;
    reviewer_name: string | null;
    reviewed_at: string | null;
    rejection_reason: string | null;
};

type Stats = {
    total: number;
    pending: number;
    approved: number;
    rejected: number;
};

type Filters = {
    status: string | null;
};

const props = defineProps<{
    requests: AttendanceRequest[];
    stats: Stats;
    filters: Filters;
}>();

const filterStatus = ref(props.filters.status ?? '');

const applyFilters = () => {
    router.get(
        '/admin/requests',
        {
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
    filterStatus.value = '';

    router.get(
        '/admin/requests',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const approveRequest = (request: AttendanceRequest) => {
    router.patch(
        `/admin/requests/${request.id}/approve`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Pengajuan berhasil disetujui.');
            },
            onError: () => {
                toast.error('Gagal menyetujui pengajuan.');
            },
        },
    );
};

const isRejectModalOpen = ref(false);
const selectedRejectRequest = ref<AttendanceRequest | null>(null);

const rejectForm = useForm({
    rejection_reason: '',
});

const openRejectModal = (request: AttendanceRequest) => {
    selectedRejectRequest.value = request;
    rejectForm.rejection_reason = '';
    rejectForm.clearErrors();
    isRejectModalOpen.value = true;
};

const closeRejectModal = () => {
    selectedRejectRequest.value = null;
    rejectForm.reset();
    rejectForm.clearErrors();
    isRejectModalOpen.value = false;
};

const submitReject = () => {
    if (!selectedRejectRequest.value) {
        return;
    }

    rejectForm.patch(`/admin/requests/${selectedRejectRequest.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            closeRejectModal();
            toast.success('Pengajuan berhasil ditolak.');
        },
        onError: () => {
            toast.error('Periksa alasan penolakan.');
        },
    });
};

const statusClass = (status: string) => {
    if (status === 'approved') {
        return 'bg-green-100 text-green-700';
    }

    if (status === 'rejected') {
        return 'bg-red-100 text-red-700';
    }

    return 'bg-yellow-100 text-yellow-700';
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <div>
                <h1 class="text-2xl font-bold">Requests</h1>
                <p class="text-muted-foreground">
                    Kelola pengajuan izin, sakit, WFH, dan WFC karyawan.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Total</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.total }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Menunggu</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.pending }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Disetujui</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.approved }}</h2>
                </div>

                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Ditolak</p>
                    <h2 class="mt-1 text-2xl font-bold">{{ stats.rejected }}</h2>
                </div>
            </div>

            <div class="rounded-xl border p-4">
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Status</label>
                        <select
                            v-model="filterStatus"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        >
                            <option value="">Semua Status</option>
                            <option value="pending">Menunggu</option>
                            <option value="approved">Disetujui</option>
                            <option value="rejected">Ditolak</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 md:col-span-3">
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
                                <th class="px-4 py-3 font-medium">Karyawan</th>
                                <th class="px-4 py-3 font-medium">Jenis</th>
                                <th class="px-4 py-3 font-medium">Tanggal</th>
                                <th class="px-4 py-3 font-medium">Alasan</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="request in requests"
                                :key="request.id"
                                class="border-b last:border-b-0"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ request.employee_name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ request.position ?? 'Karyawan' }} · {{ request.department }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ request.type_label }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ request.start_date }} - {{ request.end_date }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="max-w-xs">
                                        {{ request.reason }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-1 text-xs font-medium"
                                        :class="statusClass(request.status)"
                                    >
                                        {{ request.status_label }}
                                    </span>

                                    <p
                                        v-if="request.rejection_reason"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ request.rejection_reason }}
                                    </p>
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div
                                        v-if="request.status === 'pending'"
                                        class="flex justify-end gap-2"
                                    >
                                        <button
                                            type="button"
                                            @click="approveRequest(request)"
                                            class="rounded-lg border border-green-200 px-3 py-1 text-xs font-medium text-green-600 hover:bg-green-50"
                                        >
                                            Approve
                                        </button>

                                        <button
                                            type="button"
                                            @click="openRejectModal(request)"
                                            class="rounded-lg border border-red-200 px-3 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                                        >
                                            Reject
                                        </button>
                                    </div>

                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground"
                                    >
                                        Diproses
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="requests.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Belum ada pengajuan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="isRejectModalOpen && selectedRejectRequest"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            >
                <div class="w-full max-w-md rounded-xl border bg-background p-6 shadow-lg">
                    <div class="mb-5">
                        <h2 class="text-xl font-bold">Tolak Pengajuan</h2>
                        <p class="text-sm text-muted-foreground">
                            Berikan alasan penolakan untuk pengajuan
                            {{ selectedRejectRequest.employee_name }}.
                        </p>
                    </div>

                    <form @submit.prevent="submitReject" class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Alasan Penolakan</label>
                            <textarea
                                v-model="rejectForm.rejection_reason"
                                rows="4"
                                class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                                placeholder="Tulis alasan penolakan..."
                            ></textarea>

                            <p
                                v-if="rejectForm.errors.rejection_reason"
                                class="text-sm text-red-500"
                            >
                                {{ rejectForm.errors.rejection_reason }}
                            </p>
                        </div>

                        <div class="flex justify-end gap-3">
                            <button
                                type="button"
                                @click="closeRejectModal"
                                class="rounded-lg border px-4 py-2 text-sm font-medium"
                            >
                                Batal
                            </button>

                            <button
                                type="submit"
                                :disabled="rejectForm.processing"
                                class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
                            >
                                {{
                                    rejectForm.processing
                                        ? 'Memproses...'
                                        : 'Tolak Pengajuan'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>