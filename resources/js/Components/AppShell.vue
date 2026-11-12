<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const profileOpen = ref(false);
const profileMenu = ref(null);

function logout() {
    router.post('/keluar');
}
const showTop = ref(false);

function onScroll() {
    showTop.value = window.scrollY > 480;
}

function closeProfile(event) {
    if (profileMenu.value && !profileMenu.value.contains(event.target)) {
        profileOpen.value = false;
    }
}

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    document.addEventListener('pointerdown', closeProfile);
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    document.removeEventListener('pointerdown', closeProfile);
});

function toTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function active(path) {
    if (path === '/') {
        return page.url === '/' || page.url.startsWith('/?');
    }

    if (path === '/buat') {
        return page.url === '/buat' || page.url.includes('/ubah');
    }

    return page.url.startsWith(path);
}
</script>

<template>
    <div class="min-h-screen">
        <header class="sticky top-0 z-20 border-b border-[#eceef3] bg-[#f4f5f8]/90 backdrop-blur">
            <div class="mx-auto flex min-h-16 max-w-[1200px] items-center justify-between gap-3 px-4 py-2 sm:px-6">
                <Link href="/" class="flex items-center gap-2.5">
                    <span class="grid h-9 w-9 grid-cols-2 gap-0.5 rounded-xl bg-[#f8e8ea] p-1.5 text-[#7a2030]">
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
                        :class="active('/') ? 'bg-white font-medium text-[#7a2030] shadow-sm' : 'text-[#5c5a66] hover:text-[#1c1b22]'"
                    >
                        Projects
                    </Link>
                    <Link
                        href="/buat"
                        class="rounded-full px-3 py-1.5"
                        :class="active('/buat') ? 'bg-white font-medium text-[#7a2030] shadow-sm' : 'text-[#5c5a66] hover:text-[#1c1b22]'"
                    >
                        Create
                    </Link>
                    <Link
                        href="/tautan"
                        class="rounded-full px-3 py-1.5"
                        :class="active('/tautan') ? 'bg-white font-medium text-[#7a2030] shadow-sm' : 'text-[#5c5a66] hover:text-[#1c1b22]'"
                    >
                        Links
                    </Link>
                    <div ref="profileMenu" class="relative">
                        <button
                            type="button"
                            class="inline-flex max-w-44 cursor-pointer items-center gap-1 rounded-full bg-white px-3 py-1.5 font-medium shadow-sm"
                            :class="active('/pengaturan') ? 'text-[#7a2030]' : 'text-[#1c1b22]'"
                            :aria-expanded="profileOpen"
                            aria-haspopup="menu"
                            @click="profileOpen = !profileOpen"
                        >
                            <span class="truncate">{{ user.name }}</span>
                            <ChevronDown :size="16" class="shrink-0" />
                        </button>
                        <div v-if="profileOpen" class="absolute right-0 z-30 mt-2 w-52 overflow-hidden rounded-2xl border border-[#e6e7ee] bg-white p-1 shadow-lg" role="menu">
                            <div class="px-3 py-2">
                                <div class="truncate text-sm font-medium text-[#1c1b22]">{{ user.name }}</div>
                                <div class="truncate text-xs text-[#6d6a7a]">{{ user.email }}</div>
                            </div>
                            <Link
                                href="/pengaturan"
                                class="block rounded-xl px-3 py-2 hover:bg-[#f8e8ea]"
                                :class="active('/pengaturan') ? 'font-medium text-[#7a2030]' : 'text-[#1c1b22]'"
                                role="menuitem"
                                @click="profileOpen = false"
                            >
                                Settings
                            </Link>
                            <button type="button" class="block w-full cursor-pointer rounded-xl px-3 py-2 text-left text-[#9f1239] hover:bg-[#fff1f2]" role="menuitem" @click="logout">
                                Log out
                            </button>
                        </div>
                    </div>
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
            aria-label="Back to top"
            @click="toTop"
        >
            <ChevronUp :size="18" />
        </button>
    </div>
</template>
