<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue';
import AppShell from '../Components/AppShell.vue';
import PreviewCard from '../Components/PreviewCard.vue';
import QrForm from '../Components/QrForm.vue';
import TypeGrid from '../Components/TypeGrid.vue';
import { api } from '../lib/api';
import { remember, secretFor } from '../lib/storage';

const props = defineProps({
    types: { type: Array, required: true },
    editingCode: { type: String, default: null },
});

const typeId = ref(props.types[0].id);
const title = ref('');
const fields = ref({});
const style = reactive({ fg: '#111827', bg: '#ffffff', shape: 'square', ecc: 'M', margin: 4 });
const smart = ref(false);
const contentFile = ref(null);
const logoFile = ref(null);
const hasStoredFile = ref(false);
const hasStoredLogo = ref(false);
const storedFileName = ref('');
const removeFile = ref(false);
const svg = ref('');
const previewError = ref('');
const loading = ref(false);
const saving = ref(false);
const saved = ref(null);
const baseline = ref('');
const attendances = ref([]);
const secretInput = ref('');
const needsSecret = ref(false);
const ready = ref(false);
const hydrating = ref(false);

const currentType = computed(() => props.types.find((type) => type.id === typeId.value) ?? props.types[0]);
const usingSmart = computed(() => smart.value || currentType.value.force_smart);
const dirty = computed(() => Boolean(saved.value) && baseline.value !== '' && baseline.value !== snapshot());

function blank(type) {
    const next = {};

    for (const field of type.fields) {
        if (field.type === 'toggle') {
            next[field.key] = Boolean(field.default);
        } else if (field.type === 'select') {
            next[field.key] = field.default || field.options?.[0]?.value || '';
        } else if (field.type === 'links') {
            next[field.key] = [{ label: '', url: '' }];
        } else {
            next[field.key] = field.default ?? '';
        }
    }

    return next;
}

fields.value = blank(currentType.value);

function snapshot() {
    return JSON.stringify({
        type: typeId.value,
        title: title.value,
        fields: fields.value,
        style: { ...style },
        removeFile: removeFile.value,
        logo: logoFile.value?.name ?? null,
        file: contentFile.value?.name ?? null,
    });
}

function missing() {
    const type = currentType.value;

    if (type.any?.length) {
        const satisfied = type.any.some((key) => {
            if (key === 'file') {
                return contentFile.value || (hasStoredFile.value && !removeFile.value);
            }

            const value = fields.value[key];

            if (Array.isArray(value)) {
                return value.some((item) => String(item.url ?? '').trim());
            }

            return String(value ?? '').trim() !== '';
        });

        if (!satisfied) {
            return true;
        }
    }

    for (const field of type.fields) {
        if (!field.required) {
            continue;
        }

        const value = fields.value[field.key];

        if (field.type === 'links' && !value?.some((item) => String(item.url ?? '').trim())) {
            return true;
        }

        if (field.type !== 'toggle' && field.type !== 'links' && String(value ?? '').trim() === '') {
            return true;
        }
    }

    return Boolean(type.logo_required && !logoFile.value && !hasStoredLogo.value);
}

function formData(extra = {}) {
    const data = new FormData();
    data.append('type', typeId.value);
    data.append('title', title.value);
    data.append('smart', usingSmart.value ? '1' : '0');
    data.append('fields', JSON.stringify(fields.value));
    data.append('style', JSON.stringify({ ...style }));

    if (logoFile.value) {
        data.append('logo', logoFile.value);
    }

    if (contentFile.value) {
        data.append('file', contentFile.value);
    }

    if (removeFile.value) {
        data.append('remove_file', '1');
    }

    const code = props.editingCode || saved.value?.code;

    if (code) {
        data.append('code', code);
        data.append('secret', saved.value?.secret || secretFor(code) || secretInput.value);
    }

    Object.entries(extra).forEach(([key, value]) => data.append(key, value));

    return data;
}

let timer;
let controller;

async function preview() {
    if (!ready.value || needsSecret.value) {
        return;
    }

    if (saved.value && !dirty.value && saved.value.svg) {
        svg.value = saved.value.svg;
        previewError.value = '';

        return;
    }

    if (missing()) {
        svg.value = '';
        previewError.value = '';
        loading.value = false;

        return;
    }

    controller?.abort();
    controller = new AbortController();
    loading.value = true;

    try {
        const data = await api('/qr/pratinjau', { body: formData(), signal: controller.signal });
        svg.value = data.svg;
        previewError.value = '';
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        previewError.value = error.message;
    } finally {
        loading.value = false;
    }
}

function schedule() {
    if (saving.value || hydrating.value) {
        return;
    }

    if (ready.value && missing() && !(saved.value && !dirty.value)) {
        svg.value = '';
        previewError.value = '';
    }

    clearTimeout(timer);
    timer = setTimeout(preview, 350);
}

watch(typeId, (id, previous) => {
    if (!previous || !ready.value || hydrating.value) {
        return;
    }

    const type = props.types.find((item) => item.id === id);
    fields.value = blank(type);
    contentFile.value = null;
    removeFile.value = false;
    hasStoredFile.value = false;
    storedFileName.value = '';

    if (!props.editingCode && !saved.value) {
        smart.value = Boolean(type.force_smart || type.smart_default);
    }

    if (id === 'shaped') {
        style.shape = 'dots';
    }

    schedule();
}, { flush: 'sync' });

watch([fields, title, smart, contentFile, logoFile, removeFile], schedule, { deep: true });
watch(style, schedule, { deep: true });
watch(contentFile, (file) => {
    if (file) {
        smart.value = true;
    }
});

async function save() {
    saving.value = true;
    previewError.value = '';

    try {
        const code = props.editingCode || saved.value?.code;
        const data = await api(code ? `/qr/${code}` : '/qr', { body: formData() });
        const secret = saved.value?.secret || secretFor(code) || data.secret;
        saved.value = { ...data, secret };
        svg.value = data.svg;
        smart.value = true;
        remember({
            code: data.code,
            secret,
            title: data.title,
            type: data.type,
            label: data.type_label,
        });

        if (logoFile.value) {
            hasStoredLogo.value = true;
        }

        if (contentFile.value) {
            hasStoredFile.value = true;
            storedFileName.value = contentFile.value.name;
        }

        if (removeFile.value) {
            hasStoredFile.value = false;
            storedFileName.value = '';
        }

        contentFile.value = null;
        logoFile.value = null;
        removeFile.value = false;
        baseline.value = snapshot();
    } catch (error) {
        previewError.value = error.message;
    } finally {
        saving.value = false;
    }
}

async function hydrate(secret) {
    const data = await api(`/qr/${props.editingCode}/buka`, { body: { secret } });
    const type = props.types.find((item) => item.id === data.type) ?? props.types[0];

    hydrating.value = true;
    typeId.value = data.type;
    title.value = data.title ?? '';
    fields.value = { ...blank(type), ...data.fields };
    Object.assign(style, data.style ?? {});
    smart.value = true;
    hasStoredFile.value = Boolean(data.has_file);
    hasStoredLogo.value = Boolean(data.has_logo);
    storedFileName.value = data.file_name ?? '';
    attendances.value = data.attendances ?? [];
    saved.value = { ...data, secret };
    svg.value = data.svg;
    needsSecret.value = false;
    await nextTick();
    hydrating.value = false;
    ready.value = true;
    baseline.value = snapshot();
}

async function unlock() {
    previewError.value = '';

    try {
        await hydrate(secretInput.value.trim());
    } catch (error) {
        previewError.value = error.message;
    }
}

function formatTime(value) {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}

onMounted(async () => {
    if (!props.editingCode) {
        smart.value = Boolean(currentType.value.force_smart || currentType.value.smart_default);
        ready.value = true;

        return;
    }

    const secret = secretFor(props.editingCode);

    if (!secret) {
        needsSecret.value = true;
        ready.value = true;

        return;
    }

    try {
        await hydrate(secret);
    } catch (error) {
        needsSecret.value = error.status === 403;
        previewError.value = error.message;
        ready.value = true;
        hydrating.value = false;
    }
});
</script>

<template>
    <Head :title="editingCode ? 'Kelola QR' : 'Buat QR'" />
    <AppShell>
        <div v-if="needsSecret" class="mx-auto max-w-md rounded-3xl border border-[#e6e7ee] bg-white p-6">
            <h1 class="text-lg font-semibold">Kunci QR</h1>
            <p class="mt-1 text-sm text-[#6d6a7a]">Kunci ini hanya tersimpan di browser yang membuat QR. Tempel jika kamu menyalinnya.</p>
            <input v-model="secretInput" type="text" class="mt-4 w-full rounded-xl border border-[#e6e7ee] px-3 py-2.5 text-sm outline-none focus:border-[#c4b5fd]" placeholder="Kunci rahasia" />
            <p v-if="previewError" class="mt-2 text-sm text-[#9f1239]">{{ previewError }}</p>
            <button type="button" class="mt-4 rounded-full bg-[#6d28d9] px-4 py-2.5 text-sm font-medium text-white" @click="unlock">Buka</button>
        </div>
        <div v-else class="grid gap-6">
            <TypeGrid v-model="typeId" :types="types" />
            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <QrForm
                    v-model:fields="fields"
                    v-model:title="title"
                    v-model:smart="smart"
                    v-model:style="style"
                    v-model:remove-file="removeFile"
                    :type="currentType"
                    :editing="Boolean(editingCode || saved)"
                    :has-stored-file="hasStoredFile"
                    :stored-file-name="storedFileName"
                    :has-stored-logo="hasStoredLogo"
                    @file="contentFile = $event"
                    @logo="logoFile = $event"
                />
                <PreviewCard
                    :svg="svg"
                    :loading="loading"
                    :error="previewError"
                    :smart="usingSmart"
                    :saved="saved"
                    :dirty="dirty"
                    :saving="saving"
                    :background="style.bg"
                    :foreground="style.fg"
                    :filename="`qr-${typeId}`"
                    @save="save"
                />
            </div>
            <section v-if="attendances.length" class="rounded-3xl border border-[#e6e7ee] bg-white p-5">
                <h2 class="text-base font-semibold">Kehadiran</h2>
                <ul class="mt-3 divide-y divide-[#f0f1f5] text-sm">
                    <li v-for="(person, index) in attendances" :key="index" class="flex items-center justify-between py-2">
                        <span>{{ person.name }}</span>
                        <span class="text-[#8a8794]">{{ formatTime(person.created_at) }}</span>
                    </li>
                </ul>
            </section>
        </div>
    </AppShell>
</template>
