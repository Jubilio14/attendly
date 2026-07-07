<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import EmployeeMobileLayout from '@/layouts/EmployeeMobileLayout.vue';

type Schedule = {
    name: string;
    start_time: string;
    end_time: string;
    late_tolerance_minutes: number;
    work_days: string[];
};

type Employee = {
    name: string;
    email: string;
    phone: string | null;
    position: string | null;
    department: string;
    schedule: Schedule | null;
};

const props = defineProps<{
    employee: Employee;
}>();

const phoneForm = useForm({
    phone: props.employee.phone ?? '',
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const workDayLabels: Record<string, string> = {
    monday: 'Senin',
    tuesday: 'Selasa',
    wednesday: 'Rabu',
    thursday: 'Kamis',
    friday: 'Jumat',
    saturday: 'Sabtu',
    sunday: 'Minggu',
};

const formatWorkDays = (days: string[]) => {
    if (!days.length) {
        return '-';
    }

    return days.map((day) => workDayLabels[day] ?? day).join(', ');
};

const submitPhone = () => {
    phoneForm.patch('/employee/profile/phone', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Nomor HP berhasil diperbarui.');
        },
        onError: () => {
            toast.error('Periksa kembali nomor HP.');
        },
    });
};

const submitPassword = () => {
    passwordForm.patch('/employee/profile/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            toast.success('Password berhasil diperbarui.');
        },
        onError: () => {
            toast.error('Periksa kembali password kamu.');
        },
    });
};
</script>


<template>
    <EmployeeMobileLayout
        title="Profil Saya"
        subtitle="Data akun dan informasi kerja."
    >
        <div class="space-y-4">
            <section class="rounded-2xl border bg-background p-4">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-lg font-bold text-primary-foreground"
                        >
                            {{ employee.name.charAt(0).toUpperCase() }}
                        </div>

                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-bold">
                                {{ employee.name }}
                            </h2>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ employee.email }}
                            </p>
                            <p class="mt-1 text-sm">
                                {{ employee.position ?? 'Karyawan' }}
                            </p>
                        </div>
                    </div>
            </section> 
            
            <section class="rounded-2xl border bg-background p-4">
                    <h2 class="mb-3 font-semibold">Informasi Karyawan</h2>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Departemen</span>
                            <span class="text-right font-medium">
                                {{ employee.department }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Jabatan</span>
                            <span class="text-right font-medium">
                                {{ employee.position ?? '-' }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Email</span>
                            <span class="text-right font-medium">
                                {{ employee.email }}
                            </span>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border bg-background p-4">
                    <h2 class="mb-3 font-semibold">Jadwal Kerja</h2>

                    <div v-if="employee.schedule" class="space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Nama Jadwal</span>
                            <span class="text-right font-medium">
                                {{ employee.schedule.name }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Jam Kerja</span>
                            <span class="text-right font-medium">
                                {{ employee.schedule.start_time }} -
                                {{ employee.schedule.end_time }}
                            </span>
                        </div>

                        <div class="flex justify-between gap-4">
                            <span class="text-muted-foreground">Toleransi Telat</span>
                            <span class="text-right font-medium">
                                {{ employee.schedule.late_tolerance_minutes }} menit
                            </span>
                        </div>

                        <div class="space-y-1">
                            <span class="text-muted-foreground">Hari Kerja</span>
                            <p class="font-medium">
                                {{ formatWorkDays(employee.schedule.work_days) }}
                            </p>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">
                        Jadwal kerja belum diatur.
                    </p>
                </section>

                <section class="rounded-2xl border bg-background p-4">
                    <h2 class="mb-3 font-semibold">Nomor HP</h2>

                    <form @submit.prevent="submitPhone" class="space-y-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">No HP</label>
                            <input
                                v-model="phoneForm.phone"
                                type="text"
                                class="h-11 w-full rounded-xl border bg-background px-3 text-sm"
                                placeholder="Contoh: 081234567890"
                            />

                            <p v-if="phoneForm.errors.phone" class="text-sm text-red-500">
                                {{ phoneForm.errors.phone }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="phoneForm.processing"
                            class="h-11 w-full rounded-xl bg-primary text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {{ phoneForm.processing ? 'Menyimpan...' : 'Simpan No HP' }}
                        </button>
                    </form>
                </section>

                <section class="rounded-2xl border bg-background p-4">
                    <h2 class="mb-3 font-semibold">Ubah Password</h2>

                    <form @submit.prevent="submitPassword" class="space-y-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Password Lama</label>
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                class="h-11 w-full rounded-xl border bg-background px-3 text-sm"
                                placeholder="Masukkan password lama"
                            />

                            <p
                                v-if="passwordForm.errors.current_password"
                                class="text-sm text-red-500"
                            >
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Password Baru</label>
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                class="h-11 w-full rounded-xl border bg-background px-3 text-sm"
                                placeholder="Minimal 8 karakter"
                            />

                            <p
                                v-if="passwordForm.errors.password"
                                class="text-sm text-red-500"
                            >
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium">Konfirmasi Password Baru</label>
                            <input
                                v-model="passwordForm.password_confirmation"
                                type="password"
                                class="h-11 w-full rounded-xl border bg-background px-3 text-sm"
                                placeholder="Ulangi password baru"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="h-11 w-full rounded-xl bg-primary text-sm font-medium text-primary-foreground disabled:opacity-50"
                        >
                            {{ passwordForm.processing ? 'Menyimpan...' : 'Ubah Password' }}
                        </button>
                    </form>
                </section>

                <section class="rounded-2xl border bg-background p-4">
                    <h2 class="mb-3 font-semibold">Akun</h2>

                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="h-11 w-full rounded-xl border border-red-200 text-sm font-semibold text-red-600 hover:bg-red-50"
                    >
                        Logout
                    </Link>
                </section>
        </div>  
    </EmployeeMobileLayout>
</template>

<!-- <template>
    <div class="min-h-screen bg-muted/30">
        <div class="mx-auto flex min-h-screen w-full max-w-md flex-col bg-background">
            <header class="sticky top-0 z-10 border-b bg-background/95 p-4 backdrop-blur">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h1 class="text-xl font-bold">Profil Saya</h1>
                        <p class="text-sm text-muted-foreground">
                            Data akun dan informasi kerja.
                        </p>
                    </div>

                    <Link
                        href="/employee/home"
                        class="rounded-xl border px-3 py-2 text-xs font-medium hover:bg-muted"
                    >
                        Home
                    </Link>
                </div>
            </header>

            <main class="flex-1 space-y-4 p-4">
                
            </main>
        </div>
    </div>
</template> -->