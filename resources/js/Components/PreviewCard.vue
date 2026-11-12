<script setup>
import { computed, ref } from 'vue';
import { Check, Copy, Download } from '@lucide/vue';
import { downloadPng, downloadSvg } from '../lib/download';

const props = defineProps({
    svg: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    smart: { type: Boolean, default: false },
    saved: { type: Object, default: null },
    dirty: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    background: { type: String, default: '#ffffff' },
    foreground: { type: String, default: '#111827' },
    filename: { type: String, default: 'qr' },
});

const emit = defineEmits(['save']);
const copied = ref(false);
const downloadError = ref('');

const safeSvg = computed(() => (props.svg.startsWith('<svg') && !props.svg.includes('<script') ? props.svg : ''));
const canDownload = computed(() => safeSvg.value && (!props.smart || (props.saved && !props.dirty)));
const showSave = computed(() => props.saving || (Boolean(safeSvg.value) && (!props.saved || props.dirty)));

const contrastLow = computed(() => {
    const ratio = contrast(props.foreground, props.background);

    return ratio < 3;
});

function channel(hex) {
    const value = Number.parseInt(hex.slice(1), 16);

    return [((value >> 16) & 255) / 255, ((value >> 8) & 255) / 255, (value & 255) / 255].map((part) => (
        part <= 0.03928 ? part / 12.92 : ((part + 0.055) / 1.055) ** 2.4
    ));
}

function contrast(first, second) {
    if (!/^#[0-9a-fA-F]{6}$/.test(first) || !/^#[0-9a-fA-F]{6}$/.test(second)) {
        return 21;
    }

    const left = channel(first);
    const right = channel(second);
    const luminance = (rgb) => (0.2126 * rgb[0]) + (0.7152 * rgb[1]) + (0.0722 * rgb[2]);
    const a = luminance(left);
    const b = luminance(right);
    const [high, low] = a > b ? [a, b] : [b, a];

    return (high + 0.05) / (low + 0.05);
}

async function png() {
    downloadError.value = '';

    try {
        await downloadPng(safeSvg.value, 512, `${props.filename}.png`, props.background);
    } catch {
        downloadError.value = 'This browser could not create the PNG. Download the SVG.';
    }
}

function svgFile() {
    downloadSvg(safeSvg.value, `${props.filename}.svg`);
}

async function copyLink() {
    if (!props.saved?.url) {
        return;
    }

    await navigator.clipboard.writeText(props.saved.url);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 1600);
}
</script>

<template>
    <aside class="lg:sticky lg:top-24">
        <div class="rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)]">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold">Preview</h2>
                <span v-if="loading" class="text-xs text-[#8a8794]">Building…</span>
            </div>
            <div class="qr-preview rounded-2xl border border-[#f0f1f5] bg-[#fafafb] p-4">
                <div v-if="safeSvg" v-html="safeSvg" />
                <div v-else class="grid aspect-square place-items-center px-6 text-center text-sm text-[#8a8794]">
                    Fill in the details to see the QR.
                </div>
            </div>
            <p v-if="smart && (!saved || dirty)" class="mt-3 text-xs leading-5 text-[#6d6a7a]">
                This preview still encodes the content. Save first so the downloaded QR uses the short link.
            </p>
            <p v-if="contrastLow" class="mt-3 text-xs text-[#9a3412]">The color contrast is low. The QR may be hard to scan.</p>
            <p v-if="error" class="mt-3 text-sm text-[#9f1239]">{{ error }}</p>
            <p v-if="downloadError" class="mt-3 text-sm text-[#9f1239]">{{ downloadError }}</p>
            <div v-if="saved && !dirty" class="mt-3 rounded-2xl bg-[#f7f6fb] px-3 py-2 text-xs break-all text-[#4c4a57]">
                <div class="mb-1 font-medium text-[#1c1b22]">Short link</div>
                {{ saved.url }}
                <div v-if="saved.scans !== undefined" class="mt-1 text-[#8a8794]">{{ saved.scans }} scans</div>
            </div>
            <div class="mt-4 grid gap-2">
                <button
                    v-if="showSave"
                    type="button"
                    class="rounded-full bg-[#7a2030] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50"
                    :disabled="saving"
                    @click="emit('save')"
                >
                    {{ saving ? 'Saving…' : 'Save' }}
                </button>
                <button type="button" class="inline-flex items-center justify-center gap-2 rounded-full border border-[#e6e7ee] px-4 py-2.5 text-sm font-medium disabled:opacity-40" :disabled="!canDownload" @click="png">
                    <Download :size="16" />
                    PNG
                </button>
                <button type="button" class="rounded-full border border-[#e6e7ee] px-4 py-2.5 text-sm font-medium disabled:opacity-40" :disabled="!canDownload" @click="svgFile">
                    Download SVG
                </button>
                <button v-if="saved?.url" type="button" class="inline-flex items-center justify-center gap-2 rounded-full px-4 py-2 text-sm text-[#7a2030]" @click="copyLink">
                    <Check v-if="copied" :size="16" />
                    <Copy v-else :size="16" />
                    {{ copied ? 'Copied' : 'Copy link' }}
                </button>
            </div>
        </div>
    </aside>
</template>
