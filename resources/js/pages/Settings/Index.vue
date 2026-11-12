<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../Components/AppShell.vue';
import PasswordField from '../../Components/PasswordField.vue';

defineProps({
    users: { type: Array, required: true },
});

const page = usePage();
const success = computed(() => page.props.flash?.success ?? '');

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const userForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function updatePassword() {
    passwordForm.put('/pengaturan/sandi', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

function addUser() {
    userForm.post('/pengaturan/pengguna', {
        preserveScroll: true,
        onSuccess: () => userForm.reset(),
    });
}
</script>

<template>
    <Head title="Pengaturan" />
    <AppShell>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">Pengaturan</h1>
            <p class="mt-1 text-sm text-[#6d6a7a]">Ganti kata sandi dan tambah pengguna.</p>
        </div>
        <p v-if="success" class="mb-4 rounded-2xl bg-[#ecfdf3] px-4 py-3 text-sm text-[#166534]">{{ success }}</p>
        <div class="grid gap-4 lg:grid-cols-2">
            <form class="rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)] sm:p-6" @submit.prevent="updatePassword">
                <h2 class="text-lg font-semibold tracking-tight">Ganti kata sandi</h2>
                <p class="mt-1 text-sm text-[#6d6a7a]">Berlaku untuk akun yang sedang masuk.</p>
                <div class="mt-5 grid gap-4">
                    <PasswordField
                        id="current-password"
                        v-model="passwordForm.current_password"
                        label="Kata sandi saat ini"
                        autocomplete="current-password"
                        :error="passwordForm.errors.current_password"
                    />
                    <PasswordField
                        id="new-password"
                        v-model="passwordForm.password"
                        label="Kata sandi baru"
                        autocomplete="new-password"
                        :error="passwordForm.errors.password"
                    />
                    <PasswordField
                        id="confirm-password"
                        v-model="passwordForm.password_confirmation"
                        label="Ulangi kata sandi baru"
                        autocomplete="new-password"
                    />
                </div>
                <button type="submit" class="mt-6 rounded-full bg-[#7a2030] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" :disabled="passwordForm.processing">
                    {{ passwordForm.processing ? 'Menyimpan…' : 'Simpan kata sandi' }}
                </button>
            </form>
            <form class="rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)] sm:p-6" @submit.prevent="addUser">
                <h2 class="text-lg font-semibold tracking-tight">Tambah pengguna</h2>
                <p class="mt-1 text-sm text-[#6d6a7a]">Pengguna baru bisa langsung masuk.</p>
                <div class="mt-5 grid gap-4">
                    <label class="grid gap-1.5 text-sm">
                        <span class="font-medium">Nama</span>
                        <input
                            v-model="userForm.name"
                            type="text"
                            autocomplete="name"
                            required
                            class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]"
                        />
                        <span v-if="userForm.errors.name" class="text-[#9f1239]">{{ userForm.errors.name }}</span>
                    </label>
                    <label class="grid gap-1.5 text-sm">
                        <span class="font-medium">Email</span>
                        <input
                            v-model="userForm.email"
                            type="email"
                            autocomplete="off"
                            required
                            class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]"
                            placeholder="nama@contoh.com"
                        />
                        <span v-if="userForm.errors.email" class="text-[#9f1239]">{{ userForm.errors.email }}</span>
                    </label>
                    <PasswordField
                        id="user-password"
                        v-model="userForm.password"
                        label="Kata sandi"
                        autocomplete="new-password"
                        :error="userForm.errors.password"
                    />
                    <PasswordField
                        id="user-password-confirmation"
                        v-model="userForm.password_confirmation"
                        label="Ulangi kata sandi"
                        autocomplete="new-password"
                    />
                </div>
                <button type="submit" class="mt-6 rounded-full bg-[#7a2030] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" :disabled="userForm.processing">
                    {{ userForm.processing ? 'Menambahkan…' : 'Tambah pengguna' }}
                </button>
            </form>
        </div>
        <section class="mt-4 rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)] sm:p-6">
            <h2 class="text-lg font-semibold tracking-tight">Pengguna</h2>
            <ul class="mt-4 divide-y divide-[#eceef3]">
                <li v-for="user in users" :key="user.email" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm">
                    <span class="font-medium">{{ user.name }}</span>
                    <span class="text-[#6d6a7a]">{{ user.email }}</span>
                </li>
            </ul>
        </section>
    </AppShell>
</template>
