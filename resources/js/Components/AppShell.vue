<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronUp } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);

function logout() {
    router.post('/keluar');
}
const showTop = ref(false);

function onScroll() {
    showTop.value = window.scrollY > 480;
}

onMounted(() => window.addEventListener('scroll', onScroll, { passive: true }));
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

function toTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function active(path) {
    if (path === '/') {
        return page.url === '/' || page.url.startsWith('/?');
    }

    if (path === '/buat') {
        return page.url === '/buat' || page.url.startsWith('/kelola');
    }

    return page.url.startsWith(path);
}
</script>

<template>
    <div class="min-h-screen">
        <header class="sticky top-0 z-20 border-b border-[#eceef3] bg-[#f4f5f8]/90 backdrop-blur">
            <div class="mx-auto flex min-h-16 max-w-[1200px] items-center justify-between gap-3 px-4 py-2 sm:px-6">
                <Link href="/" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 grid-cols-2 gap-0.5 rounded-xl bg-[#efe7ff] p-1.5 text-[#6d28d9]">
                        <span class="rounded-[3px] bg-current" />
                        <span class="rounded-[3px] bg-current opacity-70" />
                        <span class="rounded-[3px] bg-current opacity-70" />
                        <span class="rounded-[3px] bg-current" />
                    </span>
                    <span class="text-[15px] font-semibold tracking-tight">QR Generate</span>
                </Link>
                <nav v-if="user" class="flex flex-wrap items-center justify-end gap-1 text-sm">
                    <Link
                        href="/"
                        class="rounded-full px-3 py-1.5"
                        :class="active('/') ? 'bg-white font-medium text-[#6d28d9] shadow-sm' : 'text-[#5c5a66] hover:text-[#1c1b22]'"
                    >
                        Proyek
                    </Link>
                    <Link
                        href="/buat"
                        class="rounded-full px-3 py-1.5"
                        :class="active('/buat') ? 'bg-white font-medium text-[#6d28d9] shadow-sm' : 'text-[#5c5a66] hover:text-[#1c1b22]'"
                    >
                        Buat
                    </Link>
                    <Link
                        href="/pengaturan"
                        class="rounded-full px-3 py-1.5"
                        :class="active('/pengaturan') ? 'bg-white font-medium text-[#6d28d9] shadow-sm' : 'text-[#5c5a66] hover:text-[#1c1b22]'"
                    >
                        Pengaturan
                    </Link>
                    <button type="button" class="rounded-full px-3 py-1.5 text-[#5c5a66] hover:text-[#1c1b22]" @click="logout">
                        Keluar
                    </button>
                </nav>
            </div>
        </header>
        <main class="mx-auto max-w-[1200px] px-4 py-6 sm:px-6 sm:py-8">
            <slot />
        </main>
        <button
            v-if="showTop"
            type="button"
            class="fixed right-5 bottom-5 grid h-10 w-10 place-items-center rounded-full border border-[#e6e7ee] bg-white text-[#5c5a66] shadow-md"
            aria-label="Kembali ke atas"
            @click="toTop"
        >
            <ChevronUp :size="18" />
        </button>
    </div>
</template>
