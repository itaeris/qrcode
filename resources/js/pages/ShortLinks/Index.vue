<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { Check, Copy, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppShell from '../../Components/AppShell.vue';

defineProps({
    links: { type: Array, required: true },
});

const page = usePage();
const success = computed(() => page.props.flash?.success ?? '');
const form = useForm({
    url: '',
    title: '',
    code: '',
});
const copied = ref('');
const pending = ref(null);
const deleting = ref(false);

function submit() {
    form.post('/links', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

async function copy(link) {
    await navigator.clipboard.writeText(link.short_url);
    copied.value = link.code;
    setTimeout(() => {
        if (copied.value === link.code) {
            copied.value = '';
        }
    }, 1600);
}

function remove() {
    if (!pending.value || deleting.value) {
        return;
    }

    deleting.value = true;
    router.post(`/links/${pending.value.code}/delete`, {}, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            pending.value = null;
        },
    });
}
</script>

<template>
    <Head title="Short links" />
    <AppShell>
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">Short links</h1>
            <p class="mt-1 text-sm text-[#6d6a7a]">Shorten a link, then change its destination any time.</p>
        </div>
        <p v-if="success" class="mb-4 rounded-2xl bg-[#ecfdf3] px-4 py-3 text-sm text-[#166534]">{{ success }}</p>
        <form class="mb-4 grid gap-4 rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)] sm:grid-cols-2" @submit.prevent="submit">
            <label class="grid gap-1.5 text-sm sm:col-span-2">
                <span class="font-medium">Destination</span>
                <input v-model="form.url" type="text" required placeholder="https://example.com/page" class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]" />
                <span v-if="form.errors.url" class="text-[#9f1239]">{{ form.errors.url }}</span>
            </label>
            <label class="grid gap-1.5 text-sm">
                <span class="font-medium">Name</span>
                <input v-model="form.title" type="text" maxlength="140" placeholder="Optional" class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]" />
            </label>
            <label class="grid gap-1.5 text-sm">
                <span class="font-medium">Custom code</span>
                <input v-model="form.code" type="text" maxlength="16" placeholder="Leave empty for a random code" class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]" />
                <span v-if="form.errors.code" class="text-[#9f1239]">{{ form.errors.code }}</span>
            </label>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-full bg-[#7a2030] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" :disabled="form.processing">
                    {{ form.processing ? 'Creating…' : 'Create link' }}
                </button>
            </div>
        </form>
        <div v-if="links.length === 0" class="rounded-3xl border border-dashed border-[#d9dbe3] bg-white px-6 py-16 text-center">
            <p class="font-medium">No short links yet</p>
            <p class="mt-1 text-sm text-[#6d6a7a]">Enter a destination above, then copy the short link.</p>
        </div>
        <div v-else class="overflow-hidden rounded-3xl border border-[#e6e7ee] bg-white shadow-[0_1px_2px_rgba(20,18,31,0.04)]">
            <article v-for="link in links" :key="link.code" class="flex flex-wrap items-center gap-3 border-b border-[#f0f1f5] px-4 py-4 last:border-b-0 sm:px-5">
                <div class="min-w-0 flex-1">
                    <h2 class="truncate text-sm font-semibold">{{ link.title }}</h2>
                    <p class="truncate text-sm text-[#7a2030]">{{ link.short_url }}</p>
                    <p class="truncate text-xs text-[#6d6a7a]">{{ link.url }} · {{ link.clicks }} clicks</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border border-[#e6e7ee] px-3 py-1.5 text-sm" @click="copy(link)">
                        <Check v-if="copied === link.code" :size="15" />
                        <Copy v-else :size="15" />
                        {{ copied === link.code ? 'Copied' : 'Copy' }}
                    </button>
                    <Link :href="`/links/${link.code}`" class="rounded-full bg-[#f8e8ea] px-3 py-1.5 text-sm font-medium text-[#7a2030]">Manage</Link>
                    <button
                        type="button"
                        class="grid size-9 cursor-pointer place-items-center rounded-full border border-[#fecdd3] bg-[#fff1f2] text-[#9f1239] shadow-sm transition hover:bg-[#ffe4e6] active:scale-95"
                        aria-label="Delete"
                        @click="pending = link"
                    >
                        <Trash2 :size="16" />
                    </button>
                </div>
            </article>
        </div>
        <div v-if="pending" class="fixed inset-0 z-30 grid place-items-center bg-[#1c1b22]/40 px-4" @click.self="pending = null">
            <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-xl" role="alertdialog" aria-modal="true" aria-labelledby="delete-link-title">
                <h2 id="delete-link-title" class="text-lg font-semibold">Delete link?</h2>
                <p class="mt-2 text-sm leading-6 text-[#6d6a7a]">
                    <span class="font-medium text-[#1c1b22]">{{ pending.title }}</span>
                    will be deleted. Its short link will stop working.
                </p>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" class="cursor-pointer rounded-full border border-[#e6e7ee] px-4 py-2 text-sm font-medium" :disabled="deleting" @click="pending = null">Cancel</button>
                    <button type="button" class="cursor-pointer rounded-full bg-[#9f1239] px-4 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="deleting" @click="remove">
                        {{ deleting ? 'Deleting…' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </AppShell>
</template>
