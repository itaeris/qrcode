<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppShell from '../../Components/AppShell.vue';

const props = defineProps({
    qr: { type: Object, required: true },
});

const page = usePage();
const form = useForm({ name: '' });
const presentation = computed(() => props.qr.presentation ?? {});
const success = computed(() => page.props.flash?.success);

function checkIn() {
    form.post(`/q/${props.qr.code}/check-in`, {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
}
</script>

<template>
    <Head :title="qr.title" />
    <AppShell>
        <article class="mx-auto max-w-lg rounded-[28px] border border-[#e6e7ee] bg-white p-6 shadow-[0_1px_2px_rgba(20,18,31,0.04)] sm:p-8">
            <div class="text-xs font-medium tracking-wide text-[#7a2030] uppercase">{{ qr.type_label }}</div>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ presentation.headline || qr.title }}</h1>
            <p v-if="presentation.subline" class="mt-2 text-sm text-[#6d6a7a]">{{ presentation.subline }}</p>
            <div
                v-if="presentation.code"
                class="mt-5 rounded-2xl border border-dashed border-[#d8d4ea] bg-[#faf8ff] px-4 py-5 text-center font-mono text-2xl font-semibold tracking-[0.18em] text-[#4c1d95]"
            >
                {{ presentation.code }}
            </div>
            <p v-if="presentation.body" class="mt-4 text-sm leading-6 whitespace-pre-line text-[#3f3d48]">{{ presentation.body }}</p>
            <dl v-if="presentation.rows?.length" class="mt-4 grid gap-3">
                <div v-for="row in presentation.rows" :key="row.label">
                    <dt class="text-xs text-[#8a8794]">{{ row.label }}</dt>
                    <dd class="text-sm font-medium break-words">{{ row.value }}</dd>
                </div>
            </dl>
            <img v-if="presentation.media === 'image' && qr.file_url" :src="qr.file_url" :alt="qr.title" class="mt-5 max-h-80 w-full rounded-2xl object-contain" />
            <audio v-else-if="presentation.media === 'audio' && qr.file_url" :src="qr.file_url" controls class="mt-5 w-full" />
            <video v-else-if="presentation.media === 'video' && qr.file_url" :src="qr.file_url" controls class="mt-5 w-full rounded-2xl" />
            <iframe v-else-if="presentation.media === 'pdf' && qr.file_url" :src="qr.file_url" class="mt-5 h-96 w-full rounded-2xl border border-[#e6e7ee]" title="PDF preview" />
            <div v-if="presentation.actions?.length" class="mt-5 grid gap-2">
                <a
                    v-for="action in presentation.actions"
                    :key="action.href + action.label"
                    :href="action.href"
                    class="rounded-full bg-[#7a2030] px-4 py-2.5 text-center text-sm font-medium text-white"
                >
                    {{ action.label }}
                </a>
            </div>
            <form v-if="presentation.check_in" class="mt-5 grid gap-2" @submit.prevent="checkIn">
                <label class="text-sm font-medium" for="attendee">Name</label>
                <input id="attendee" v-model="form.name" type="text" maxlength="80" required class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 text-sm outline-none focus:border-[#e3b3ba]" placeholder="Your name" />
                <p v-if="form.errors.name" class="text-sm text-[#9f1239]">{{ form.errors.name }}</p>
                <p v-if="success" class="text-sm text-[#166534]">{{ success }}</p>
                <button type="submit" class="rounded-full bg-[#111827] px-4 py-2.5 text-sm font-medium text-white" :disabled="form.processing">
                    Check in
                </button>
            </form>
        </article>
        <p v-if="page.props.auth?.user" class="mt-6 text-center text-sm text-[#8a8794]">
            <Link href="/create" class="text-[#7a2030]">Create your own QR</Link>
        </p>
    </AppShell>
</template>
