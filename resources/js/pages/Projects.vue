<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ChevronDown, Download, LayoutGrid, List, Share2, Trash2 } from '@lucide/vue';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppShell from '../Components/AppShell.vue';
import ShareDialog from '../Components/ShareDialog.vue';
import { api } from '../lib/api';
import { downloadPng } from '../lib/download';
import { forget, loadMine } from '../lib/storage';

const props = defineProps({
    projects: { type: Array, required: true },
});

const items = ref(props.projects);
const view = ref('cards');
const pendingDelete = ref(null);
const deleting = ref(false);
const error = ref('');
const createOpen = ref(false);
const createMenu = ref(null);
const sharing = ref(null);

function isOwner(item) {
    return item.access === 'owner';
}

function canEdit(item) {
    return item.access === 'owner' || item.access === 'edit';
}

function setShares(shares) {
    if (sharing.value) {
        sharing.value.shares = shares;
    }
}

onMounted(() => {
    const local = loadMine();
    const codes = new Set(items.value.map((item) => item.code));

    local.filter((item) => !codes.has(item.code)).forEach((item) => forget(item.code));
    document.addEventListener('pointerdown', closeCreateMenu);
});

onBeforeUnmount(() => document.removeEventListener('pointerdown', closeCreateMenu));

function closeCreateMenu(event) {
    if (createMenu.value && !createMenu.value.contains(event.target)) {
        createOpen.value = false;
    }
}

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
        await api(`/qr/${item.code}/delete`, { body: {} });
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
    return new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value));
}

function qrSvg(svg) {
    return typeof svg === 'string' && svg.startsWith('<svg') && !/<script|javascript:|foreignObject|\son[a-z]+\s*=/i.test(svg) ? svg : '';
}

async function download(item) {
    const svg = qrSvg(item.svg);

    if (!svg) {
        error.value = 'This QR cannot be downloaded yet.';

        return;
    }

    error.value = '';

    try {
        await downloadPng(svg, 512, `qr-${item.code}.png`, item.background || '#ffffff');
    } catch {
        error.value = 'This browser could not create the PNG.';
    }
}
</script>

<template>
    <Head title="Projects" />
    <AppShell>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
                <p class="mt-1 text-sm text-[#6d6a7a]">Saved QR codes.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex rounded-full border border-[#e6e7ee] bg-white p-1" role="group" aria-label="Project view">
                    <button
                        type="button"
                        class="grid size-9 cursor-pointer place-items-center rounded-full"
                        :class="view === 'list' ? 'bg-[#f8e8ea] text-[#7a2030]' : 'text-[#5c5a66]'"
                        :aria-pressed="view === 'list'"
                        aria-label="List"
                        @click="view = 'list'"
                    >
                        <List :size="16" />
                    </button>
                    <button
                        type="button"
                        class="grid size-9 cursor-pointer place-items-center rounded-full"
                        :class="view === 'cards' ? 'bg-[#f8e8ea] text-[#7a2030]' : 'text-[#5c5a66]'"
                        :aria-pressed="view === 'cards'"
                        aria-label="Cards"
                        @click="view = 'cards'"
                    >
                        <LayoutGrid :size="16" />
                    </button>
                </div>
                <div ref="createMenu" class="relative">
                    <button
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-[#7a2030] px-4 py-2 text-sm font-medium text-white"
                        :aria-expanded="createOpen"
                        aria-haspopup="menu"
                        @click="createOpen = !createOpen"
                    >
                        New
                        <ChevronDown :size="16" />
                    </button>
                    <div v-if="createOpen" class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-2xl border border-[#e6e7ee] bg-white p-1 shadow-lg" role="menu">
                        <Link href="/create" class="block rounded-xl px-3 py-2 text-sm hover:bg-[#f8e8ea]" role="menuitem" @click="createOpen = false">New QR</Link>
                        <Link href="/links" class="block rounded-xl px-3 py-2 text-sm hover:bg-[#f8e8ea]" role="menuitem" @click="createOpen = false">Short link</Link>
                    </div>
                </div>
            </div>
        </div>
        <p v-if="error" class="mb-4 text-sm text-[#9f1239]">{{ error }}</p>
        <div v-if="items.length === 0" class="rounded-3xl border border-dashed border-[#d9dbe3] bg-white px-6 py-16 text-center">
            <p class="font-medium">No projects yet</p>
            <p class="mt-1 text-sm text-[#6d6a7a]">Create your first QR, then save it.</p>
            <Link href="/create" class="mt-4 inline-flex rounded-full bg-[#f8e8ea] px-4 py-2 text-sm font-medium text-[#7a2030]">Create QR</Link>
        </div>
        <div v-else-if="view === 'list'" class="overflow-hidden rounded-3xl border border-[#e6e7ee] bg-white shadow-[0_1px_2px_rgba(20,18,31,0.04)]">
            <div v-for="item in items" :key="item.code" class="flex flex-wrap items-center gap-3 border-b border-[#f0f1f5] px-4 py-3 last:border-b-0 sm:px-5">
                <div class="min-w-0 flex-1">
                    <div class="text-xs font-medium text-[#7a2030]">{{ item.type_label }}</div>
                    <h2 class="truncate text-sm font-semibold">{{ item.title }}</h2>
                    <p class="text-xs text-[#6d6a7a]">{{ item.scans }} scans · {{ formatTime(item.created_at) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="!isOwner(item)" class="rounded-full bg-[#f8e8ea] px-2.5 py-1 text-xs font-medium text-[#7a2030]">{{ item.access === 'edit' ? 'Can edit' : 'View only' }}</span>
                    <Link :href="`/manage/${item.code}`" class="rounded-full bg-[#f8e8ea] px-3 py-1.5 text-sm font-medium text-[#7a2030]">Manage</Link>
                    <Link v-if="canEdit(item)" :href="`/manage/${item.code}/edit`" class="rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm">Edit</Link>
                    <a :href="item.url" target="_blank" rel="noreferrer" class="rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm">Open</a>
                    <button v-if="isOwner(item)" type="button" class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm" @click="sharing = item">
                        <Share2 :size="15" />
                        Share
                    </button>
                    <button
                        v-if="isOwner(item)"
                        type="button"
                        class="grid size-9 cursor-pointer place-items-center rounded-full border border-[#fecdd3] bg-[#fff1f2] text-[#9f1239] shadow-sm transition hover:bg-[#ffe4e6] active:scale-95"
                        aria-label="Delete"
                        @click="askRemove(item)"
                    >
                        <Trash2 :size="16" />
                    </button>
                </div>
            </div>
        </div>
        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="item in items" :key="item.code" class="relative flex flex-col rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)]">
                <button
                    v-if="isOwner(item)"
                    type="button"
                    class="absolute top-4 right-4 grid size-9 cursor-pointer place-items-center rounded-full border border-[#fecdd3] bg-[#fff1f2] text-[#9f1239] shadow-sm transition hover:bg-[#ffe4e6] active:scale-95"
                    aria-label="Delete"
                    @click="askRemove(item)"
                >
                    <Trash2 :size="16" />
                </button>
                <div class="qr-preview aspect-square max-w-40 overflow-hidden rounded-2xl border border-[#f0f1f5] bg-[#fafafb] p-2" :class="isOwner(item) ? 'mr-10' : ''" v-html="qrSvg(item.svg)" />
                <div class="mt-4 pr-2">
                    <div class="flex items-center gap-2">
                        <div class="text-xs font-medium text-[#7a2030]">{{ item.type_label }}</div>
                        <span v-if="!isOwner(item)" class="rounded-full bg-[#f8e8ea] px-2 py-0.5 text-xs font-medium text-[#7a2030]">{{ item.access === 'edit' ? 'Can edit' : 'View only' }}</span>
                    </div>
                    <h2 class="mt-2 text-lg font-semibold tracking-tight">{{ item.title }}</h2>
                    <p class="mt-1 text-sm text-[#6d6a7a]">{{ item.scans }} scans · {{ formatTime(item.created_at) }}</p>
                </div>
                <div class="mt-5 flex flex-wrap gap-2">
                    <Link :href="`/manage/${item.code}`" class="rounded-full bg-[#f8e8ea] px-3 py-1.5 text-sm font-medium text-[#7a2030]">Manage</Link>
                    <Link v-if="canEdit(item)" :href="`/manage/${item.code}/edit`" class="rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm">Edit</Link>
                    <a :href="item.url" target="_blank" rel="noreferrer" class="rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm">Open</a>
                    <button v-if="isOwner(item)" type="button" class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm" @click="sharing = item">
                        <Share2 :size="15" />
                        Share
                    </button>
                    <button type="button" class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm" @click="download(item)">
                        <Download :size="15" />
                        Download
                    </button>
                </div>
            </article>
        </div>
        <ShareDialog v-if="sharing" :project="sharing" @close="sharing = null" @shares="setShares" />
        <div v-if="pendingDelete" class="fixed inset-0 z-30 grid place-items-center bg-[#1c1b22]/40 px-4" @click.self="pendingDelete = null">
            <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl" role="alertdialog" aria-modal="true" aria-labelledby="delete-title">
                <h2 id="delete-title" class="text-lg font-semibold">Delete project?</h2>
                <p class="mt-2 text-sm leading-6 text-[#6d6a7a]">
                    <span class="font-medium text-[#1c1b22]">{{ pendingDelete.title }}</span>
                    will be deleted. Its QR link will stop working.
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="cursor-pointer rounded-full border border-[#e6e7ee] px-4 py-2 text-sm font-medium" :disabled="deleting" @click="pendingDelete = null">
                        Cancel
                    </button>
                    <button type="button" class="cursor-pointer rounded-full bg-[#9f1239] px-4 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="deleting" @click="confirmRemove">
                        {{ deleting ? 'Deleting…' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </AppShell>
</template>
