<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const showPassword = ref(false);

function submit() {
    form.post('/masuk', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Masuk" />
    <div class="grid min-h-screen place-items-center bg-[#f4f5f8] px-4 py-10">
        <form class="w-full max-w-sm rounded-[28px] border border-[#e6e7ee] bg-white p-6 shadow-[0_1px_2px_rgba(20,18,31,0.04)] sm:p-8" @submit.prevent="submit">
            <div class="mb-6 flex items-center gap-2.5">
                <span class="grid h-9 w-9 grid-cols-2 gap-0.5 rounded-xl bg-[#f8e8ea] p-1.5 text-[#7a2030]">
                    <span class="rounded-[3px] bg-current" />
                    <span class="rounded-[3px] bg-current opacity-70" />
                    <span class="rounded-[3px] bg-current opacity-70" />
                    <span class="rounded-[3px] bg-current" />
                </span>
                <span class="text-[15px] font-semibold tracking-tight">QR Generate</span>
            </div>
            <h1 class="text-2xl font-semibold tracking-tight">Masuk</h1>
            <p class="mt-1 text-sm text-[#6d6a7a]">Masuk untuk membuat dan mengelola QR.</p>
            <label class="mt-6 grid gap-1.5 text-sm">
                <span class="font-medium">Email</span>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                    class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]"
                    placeholder="nama@contoh.com"
                />
            </label>
            <div class="mt-4 grid gap-1.5 text-sm">
                <label for="password" class="font-medium">Kata sandi</label>
                <div class="relative">
                    <input
                        id="password"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-xl border border-[#e6e7ee] px-3 py-2.5 pr-11 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]"
                    />
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 grid w-11 place-items-center text-[#6d6a7a]"
                        :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" :size="16" />
                        <Eye v-else :size="16" />
                    </button>
                </div>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-[#3f3d48]">
                <input v-model="form.remember" type="checkbox" class="size-4 accent-[#9b2335]" />
                Ingat saya
            </label>
            <p v-if="form.errors.email || form.errors.password" class="mt-4 text-sm text-[#9f1239]">
                {{ form.errors.email || form.errors.password }}
            </p>
            <button type="submit" class="mt-6 w-full rounded-full bg-[#7a2030] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" :disabled="form.processing">
                {{ form.processing ? 'Memeriksa…' : 'Masuk' }}
            </button>
        </form>
    </div>
</template>
