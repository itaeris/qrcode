<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { onMounted, ref } from 'vue';
import AppShell from '../Components/AppShell.vue';
import { api } from '../lib/api';
import { forget, loadMine } from '../lib/storage';

const props = defineProps({
    projects: { type: Array, required: true },
});

const items = ref(props.projects);
const pendingDelete = ref(null);
const deleting = ref(false);
const error = ref('');

onMounted(() => {
    const local = loadMine();
    const codes = new Set(items.value.map((item) => item.code));

    local.filter((item) => !codes.has(item.code)).forEach((item) => forget(item.code));
});

function askRemove(item) {
    pendingDelete.value = item;
}

async function confirmRemove() {
    const item = pendingDelete.value;

    if (!item || deleting.value) {
        return;
    }

    deleting.value = true;
    error.value = '';

    try {
        await api(`/qr/${item.code}/hapus`, { body: {} });
        forget(item.code);
        items.value = items.value.filter((row) => row.code !== item.code);
        pendingDelete.value = null;
    } catch (reason) {
        error.value = reason.message;
        pendingDelete.value = null;
    } finally {
        deleting.value = false;
    }
}

function formatTime(value) {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value));
}
</script>

<template>
    <Head title="Proyek" />
    <AppShell>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Proyek</h1>
                <p class="mt-1 text-sm text-[#6d6a7a]">QR yang sudah disimpan.</p>
            </div>
            <Link href="/buat" class="rounded-full bg-[#6d28d9] px-4 py-2 text-sm font-medium text-white">QR baru</Link>
        </div>
        <p v-if="error" class="mb-4 text-sm text-[#9f1239]">{{ error }}</p>
        <div v-if="items.length === 0" class="rounded-3xl border border-dashed border-[#d9dbe3] bg-white px-6 py-16 text-center">
            <p class="font-medium">Belum ada proyek</p>
            <p class="mt-1 text-sm text-[#6d6a7a]">Buat QR pertama, lalu simpan sebagai QR cerdas.</p>
            <Link href="/buat" class="mt-4 inline-flex rounded-full bg-[#efe7ff] px-4 py-2 text-sm font-medium text-[#6d28d9]">Buat QR</Link>
        </div>
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="item in items" :key="item.code" class="relative flex flex-col rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)]">
                <button
                    type="button"
                    class="absolute top-4 right-4 grid size-9 cursor-pointer place-items-center rounded-full border border-[#fecdd3] bg-[#fff1f2] text-[#9f1239] shadow-sm transition hover:bg-[#ffe4e6] active:scale-95"
                    aria-label="Hapus"
                    @click="askRemove(item)"
                >
                    <Trash2 :size="16" />
                </button>
                <div class="pr-8">
                    <div class="text-xs font-medium text-[#6d28d9]">{{ item.type_label }}</div>
                    <h2 class="mt-2 text-lg font-semibold tracking-tight">{{ item.title }}</h2>
                    <p class="mt-1 text-sm text-[#6d6a7a]">{{ item.scans }} pindaian · {{ formatTime(item.created_at) }}</p>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <Link :href="`/kelola/${item.code}`" class="rounded-full bg-[#efe7ff] px-3 py-1.5 text-sm font-medium text-[#6d28d9]">Kelola</Link>
                    <a :href="item.url" target="_blank" rel="noreferrer" class="rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm">Buka</a>
                </div>
            </article>
        </div>
        <div v-if="pendingDelete" class="fixed inset-0 z-30 grid place-items-center bg-[#1c1b22]/40 px-4" @click.self="pendingDelete = null">
            <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl" role="alertdialog" aria-modal="true" aria-labelledby="delete-title">
                <h2 id="delete-title" class="text-lg font-semibold">Hapus proyek?</h2>
                <p class="mt-2 text-sm leading-6 text-[#6d6a7a]">
                    <span class="font-medium text-[#1c1b22]">{{ pendingDelete.title }}</span>
                    akan dihapus. Tautan QR-nya tidak bisa dipakai lagi.
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="cursor-pointer rounded-full border border-[#e6e7ee] px-4 py-2 text-sm font-medium" :disabled="deleting" @click="pendingDelete = null">
                        Batal
                    </button>
                    <button type="button" class="cursor-pointer rounded-full bg-[#9f1239] px-4 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="deleting" @click="confirmRemove">
                        {{ deleting ? 'Menghapus…' : 'Hapus' }}
                    </button>
                </div>
            </div>
        </div>
    </AppShell>
</template>
