<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import EmployeeMobileLayout from '@/layouts/EmployeeMobileLayout.vue';

type RequestItem = {
    id: number;
    type: string;
    type_label: string;
    date_range: string;
    start_date: string;
    end_date: string;
    reason: string;
    status: string;
    status_label: string;
    rejection_reason: string | null;
    reviewed_at: string | null;
};

type Filters = {
    status: string;
    today: string;
};

const props = defineProps<{
    requests: RequestItem[];
    filters: Filters;
}>();

const selectedStatus = ref(props.filters.status);
const isRequestModalOpen = ref(false);

const requestForm = useForm({
    type: 'leave',
    start_date: props.filters.today,
    end_date: props.filters.today,
    reason: '',
});

const applyStatusFilter = (status: string) => {
    selectedStatus.value = status;

    router.get(
        '/employee/requests',
        {
            status,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const openRequestModal = (type: string) => {
    requestForm.type = type;
    requestForm.start_date = props.filters.today;
    requestForm.end_date = props.filters.today;
    requestForm.reason = '';
    requestForm.clearErrors();

    isRequestModalOpen.value = true;
};

const closeRequestModal = () => {
    isRequestModalOpen.value = false;
    requestForm.reset();
    requestForm.clearErrors();
};

const submitRequest = () => {
    if (!requestForm.start_date || !requestForm.end_date) {
        toast.error('Tanggal mulai dan tanggal selesai wajib diisi.');
        return;
    }

    if (requestForm.end_date < requestForm.start_date) {
        toast.error('Tanggal selesai tidak boleh sebelum tanggal mulai.');
        return;
    }

    if (!requestForm.reason.trim()) {
        toast.error('Alasan pengajuan wajib diisi.');
        return;
    }

    requestForm.post('/employee/requests', {
        preserveScroll: true,
        onSuccess: () => {
            closeRequestModal();
            toast.success('Pengajuan berhasil dikirim.');
        },
        onError: () => {
            toast.error('Periksa kembali data pengajuan.');
        },
    });
};

const requestTypeLabel = (type: string) => {
    if (type === 'leave') return 'Izin';
    if (type === 'sick') return 'Sakit';
    if (type === 'wfh') return 'WFH';
    if (type === 'wfc') return 'WFC';

    return '-';
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
    <EmployeeMobileLayout
        title="Pengajuan"
        subtitle="Izin, sakit, WFH, dan WFC."
    >
        <div class="space-y-4">
            <div class="rounded-2xl border bg-background p-4">
                <div class="mb-4">
                    <h2 class="font-semibold">Ajukan Absensi</h2>
                    <p class="text-sm text-muted-foreground">
                        Pilih jenis pengajuan yang kamu butuhkan.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        @click="openRequestModal('leave')"
                        class="rounded-xl border px-4 py-3 text-sm font-semibold hover:bg-muted"
                    >
                        Izin
                    </button>

                    <button
                        type="button"
                        @click="openRequestModal('sick')"
                        class="rounded-xl border px-4 py-3 text-sm font-semibold hover:bg-muted"
                    >
                        Sakit
                    </button>

                    <button
                        type="button"
                        @click="openRequestModal('wfh')"
                        class="rounded-xl border px-4 py-3 text-sm font-semibold hover:bg-muted"
                    >
                        WFH
                    </button>

                    <button
                        type="button"
                        @click="openRequestModal('wfc')"
                        class="rounded-xl border px-4 py-3 text-sm font-semibold hover:bg-muted"
                    >
                        WFC
                    </button>
                </div>
            </div>

            <div class="rounded-2xl border bg-background p-4">
                <h2 class="mb-3 font-semibold">Filter Status</h2>

                <div class="grid grid-cols-4 gap-2">
                    <button
                        type="button"
                        @click="applyStatusFilter('all')"
                        class="rounded-xl border px-2 py-2 text-xs font-semibold"
                        :class="selectedStatus === 'all' ? 'bg-primary text-primary-foreground' : ''"
                    >
                        Semua
                    </button>

                    <button
                        type="button"
                        @click="applyStatusFilter('pending')"
                        class="rounded-xl border px-2 py-2 text-xs font-semibold"
                        :class="selectedStatus === 'pending' ? 'bg-primary text-primary-foreground' : ''"
                    >
                        Pending
                    </button>

                    <button
                        type="button"
                        @click="applyStatusFilter('approved')"
                        class="rounded-xl border px-2 py-2 text-xs font-semibold"
                        :class="selectedStatus === 'approved' ? 'bg-primary text-primary-foreground' : ''"
                    >
                        Setuju
                    </button>

                    <button
                        type="button"
                        @click="applyStatusFilter('rejected')"
                        class="rounded-xl border px-2 py-2 text-xs font-semibold"
                        :class="selectedStatus === 'rejected' ? 'bg-primary text-primary-foreground' : ''"
                    >
                        Ditolak
                    </button>
                </div>
            </div>

            <div v-if="requests.length > 0" class="space-y-3">
                <div
                    v-for="request in requests"
                    :key="request.id"
                    class="rounded-2xl border bg-background p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">
                                {{ request.type_label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ request.date_range }}
                            </p>
                        </div>

                        <span
                            class="rounded-full px-2 py-1 text-xs font-medium"
                            :class="statusClass(request.status)"
                        >
                            {{ request.status_label }}
                        </span>
                    </div>

                    <p class="mt-3 text-sm text-muted-foreground">
                        {{ request.reason }}
                    </p>

                    <div
                        v-if="request.reviewed_at"
                        class="mt-3 rounded-xl bg-muted p-3 text-xs text-muted-foreground"
                    >
                        Direview pada {{ request.reviewed_at }}
                    </div>

                    <p
                        v-if="request.rejection_reason"
                        class="mt-3 rounded-xl bg-red-50 p-3 text-xs text-red-600"
                    >
                        Alasan ditolak: {{ request.rejection_reason }}
                    </p>
                </div>
            </div>

            <div
                v-else
                class="rounded-2xl border bg-background p-4 text-center text-sm text-muted-foreground"
            >
                Belum ada pengajuan.
            </div>
        </div>

        <div
            v-if="isRequestModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        >
            <div class="w-full max-w-md rounded-2xl border bg-background p-5 shadow-lg">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold">
                            Ajukan {{ requestTypeLabel(requestForm.type) }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Isi tanggal dan alasan pengajuan.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="closeRequestModal"
                        class="rounded-lg border px-3 py-1 text-sm"
                    >
                        Tutup
                    </button>
                </div>

                <form @submit.prevent="submitRequest" class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Jenis Pengajuan</label>

                        <select
                            v-model="requestForm.type"
                            class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                        >
                            <option value="leave">Izin</option>
                            <option value="sick">Sakit</option>
                            <option value="wfh">WFH</option>
                            <option value="wfc">WFC</option>
                        </select>

                        <p
                            v-if="requestForm.errors.type"
                            class="text-sm text-red-500"
                        >
                            {{ requestForm.errors.type }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Tanggal Mulai</label>

                            <input
                                v-model="requestForm.start_date"
                                type="date"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            />

                            <p
                                v-if="requestForm.errors.start_date"
                                class="text-sm text-red-500"
                            >
                                {{ requestForm.errors.start_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Tanggal Selesai</label>

                            <input
                                v-model="requestForm.end_date"
                                type="date"
                                :min="requestForm.start_date"
                                class="h-10 w-full rounded-lg border bg-background px-3 text-sm"
                            />

                            <p
                                v-if="requestForm.errors.end_date"
                                class="text-sm text-red-500"
                            >
                                {{ requestForm.errors.end_date }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium">Alasan</label>

                        <textarea
                            v-model="requestForm.reason"
                            rows="4"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm"
                            placeholder="Tulis alasan pengajuan..."
                        ></textarea>

                        <p
                            v-if="requestForm.errors.reason"
                            class="text-sm text-red-500"
                        >
                            {{ requestForm.errors.reason }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeRequestModal"
                            class="rounded-lg border px-4 py-2 text-sm font-medium"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            :disabled="requestForm.processing"
                            class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {{
                                requestForm.processing
                                    ? 'Mengirim...'
                                    : 'Kirim Pengajuan'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </EmployeeMobileLayout>
</template>