<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff } from '@lucide/vue';
import { computed, ref } from 'vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});
const page = usePage();
const showPassword = ref(false);
const notice = computed(() => page.props.flash?.error ?? '');

function submit() {
    form.post('/masuk', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Sign in" />
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
            <h1 class="text-2xl font-semibold tracking-tight">Sign in</h1>
            <p class="mt-1 text-sm text-[#6d6a7a]">Sign in to create and manage QR codes.</p>
            <label class="mt-6 grid gap-1.5 text-sm">
                <span class="font-medium">Email</span>
                <input
                    v-model="form.email"
                    type="email"
                    autocomplete="username"
                    required
                    class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]"
                    placeholder="name@example.com"
                />
            </label>
            <div class="mt-4 grid gap-1.5 text-sm">
                <label for="password" class="font-medium">Password</label>
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
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                        @click="showPassword = !showPassword"
                    >
                        <EyeOff v-if="showPassword" :size="16" />
                        <Eye v-else :size="16" />
                    </button>
                </div>
            </div>
            <label class="mt-4 flex items-center gap-2 text-sm text-[#3f3d48]">
                <input v-model="form.remember" type="checkbox" class="size-4 accent-[#9b2335]" />
                Remember me
            </label>
            <p v-if="notice" class="mt-4 text-sm text-[#9f1239]">{{ notice }}</p>
            <p v-if="form.errors.email || form.errors.password" class="mt-4 text-sm text-[#9f1239]">
                {{ form.errors.email || form.errors.password }}
            </p>
            <button type="submit" class="mt-6 w-full rounded-full bg-[#7a2030] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" :disabled="form.processing">
                {{ form.processing ? 'Checking…' : 'Sign in' }}
            </button>
            <div class="my-5 flex items-center gap-3 text-xs text-[#8a8794]">
                <span class="h-px flex-1 bg-[#e6e7ee]" />
                or
                <span class="h-px flex-1 bg-[#e6e7ee]" />
            </div>
            <a href="/masuk/google" class="flex w-full items-center justify-center gap-2 rounded-full border border-[#e6e7ee] px-4 py-2.5 text-sm font-medium">
                <svg viewBox="0 0 24 24" class="size-4" aria-hidden="true">
                    <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.4h6.5a5.6 5.6 0 0 1-2.4 3.7v3h3.9c2.3-2.1 3.5-5.2 3.5-8.8z" />
                    <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24z" />
                    <path fill="#FBBC05" d="M5.4 14.4A7.2 7.2 0 0 1 5 12c0-.8.1-1.6.4-2.4V6.5H1.4A12 12 0 0 0 0 12c0 1.9.5 3.8 1.4 5.5l4-3.1z" />
                    <path fill="#EA4335" d="M12 4.8c1.7 0 3.3.6 4.5 1.8l3.4-3.4A12 12 0 0 0 1.4 6.5l4 3.1C6.3 6.9 8.9 4.8 12 4.8z" />
                </svg>
                Continue with Google
            </a>
        </form>
    </div>
</template>
