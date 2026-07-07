<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Eye, EyeOff, LockKeyhole } from '@lucide/vue';

defineProps<{
    status?: string;
    canResetPassword?: boolean;
}>();

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Login" />

    <div class="min-h-screen bg-slate-950 text-white">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl items-center justify-center px-5 py-8">
            <div class="grid w-full overflow-hidden rounded-3xl border border-white/10 bg-white/5 shadow-2xl backdrop-blur lg:grid-cols-2">
                <section class="hidden bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 p-10 lg:flex lg:flex-col lg:justify-between">
                    <div>
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-950">
                                <LockKeyhole class="h-6 w-6" />
                            </div>

                            <div>
                                <h1 class="text-2xl font-bold">Attendly</h1>
                                <p class="text-sm text-slate-400">
                                    Employee Attendance System
                                </p>
                            </div>
                        </div>

                        <div class="mt-20">
                            <h2 class="text-4xl font-bold leading-tight">
                                Absensi karyawan jadi lebih cepat dan rapi.
                            </h2>

                            <p class="mt-5 max-w-md text-base leading-relaxed text-slate-400">
                                Kelola check-in, check-out, pengajuan izin,
                                WFH, WFC, dan riwayat absensi dalam satu sistem.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-2xl font-bold">PWA</p>
                            <p class="mt-1 text-xs text-slate-400">
                                Installable
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-2xl font-bold">Role</p>
                            <p class="mt-1 text-xs text-slate-400">
                                Admin & Employee
                            </p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4">
                            <p class="text-2xl font-bold">Live</p>
                            <p class="mt-1 text-xs text-slate-400">
                                Attendance
                            </p>
                        </div>
                    </div>
                </section>

                <section class="flex min-h-[680px] items-center justify-center bg-background px-5 py-10 text-foreground sm:px-10">
                    <div class="w-full max-w-md">
                        <div class="mb-8 text-center lg:hidden">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                                <LockKeyhole class="h-7 w-7" />
                            </div>

                            <h1 class="mt-4 text-3xl font-bold">Attendly</h1>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Employee Attendance System
                            </p>
                        </div>

                        <div class="mb-8">
                            <h2 class="text-3xl font-bold">Masuk</h2>
                            <p class="mt-2 text-sm text-muted-foreground">
                                Gunakan akun karyawan atau admin untuk masuk.
                            </p>
                        </div>

                        <div
                            v-if="status"
                            class="mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
                        >
                            {{ status }}
                        </div>

                        <form @submit.prevent="submit" class="space-y-5">
                            <div class="space-y-2">
                                <label class="text-sm font-medium">
                                    Email
                                </label>

                                <input
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    autofocus
                                    placeholder="nama@email.com"
                                    class="h-12 w-full rounded-xl border bg-background px-4 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                />

                                <p
                                    v-if="form.errors.email"
                                    class="text-sm text-red-500"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <div class="flex items-center justify-between gap-3">
                                    <label class="text-sm font-medium">
                                        Password
                                    </label>

                                    <Link
                                        v-if="canResetPassword"
                                        href="/forgot-password"
                                        class="text-sm font-medium text-primary hover:underline"
                                    >
                                        Lupa password?
                                    </Link>
                                </div>

                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        autocomplete="current-password"
                                        placeholder="Masukkan password"
                                        class="h-12 w-full rounded-xl border bg-background px-4 pr-12 text-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                                    />

                                    <button
                                        type="button"
                                        @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-muted-foreground hover:bg-muted"
                                    >
                                        <EyeOff
                                            v-if="showPassword"
                                            class="h-5 w-5"
                                        />
                                        <Eye
                                            v-else
                                            class="h-5 w-5"
                                        />
                                    </button>
                                </div>

                                <p
                                    v-if="form.errors.password"
                                    class="text-sm text-red-500"
                                >
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <label class="flex items-center gap-3 text-sm">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border"
                                />

                                <span class="text-muted-foreground">
                                    Ingat saya
                                </span>
                            </label>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="h-12 w-full rounded-xl bg-primary text-sm font-semibold text-primary-foreground transition hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {{ form.processing ? 'Memproses...' : 'Masuk ke Attendly' }}
                            </button>
                        </form>

                        <p class="mt-8 text-center text-xs text-muted-foreground">
                            Attendly Internal Attendance System
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>