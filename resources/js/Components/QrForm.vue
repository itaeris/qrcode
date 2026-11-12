<script setup>
import { ref, watch } from 'vue';

defineProps({
    type: { type: Object, required: true },
    hasStoredFile: { type: Boolean, default: false },
    storedFileName: { type: String, default: '' },
    hasStoredLogo: { type: Boolean, default: false },
});

const fields = defineModel('fields', { type: Object, required: true });
const title = defineModel('title', { type: String, required: true });
const style = defineModel('style', { type: Object, required: true });
const removeFile = defineModel('removeFile', { type: Boolean, required: true });

const emit = defineEmits(['file', 'logo']);

const inputClass = 'w-full rounded-xl border border-[#e6e7ee] bg-white px-3 py-2.5 text-sm outline-none transition focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]';
const hexClass = 'min-w-0 flex-1 rounded-xl border border-[#e6e7ee] bg-white px-3 py-2.5 font-mono text-sm uppercase outline-none transition focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]';

const shapes = [
    { id: 'square', label: 'Square' },
    { id: 'rounded', label: 'Rounded' },
    { id: 'dots', label: 'Dots' },
];

const fgText = ref(style.value.fg);
const bgText = ref(style.value.bg);
const logoName = ref('');
const contentName = ref('');

watch(() => style.value.fg, (value) => {
    fgText.value = value;
});

watch(() => style.value.bg, (value) => {
    bgText.value = value;
});

function setStyle(key, value) {
    style.value = { ...style.value, [key]: value };
}

function onColorText(key, event) {
    const raw = event.target.value;

    if (key === 'fg') {
        fgText.value = raw;
    } else {
        bgText.value = raw;
    }

    if (/^#[0-9a-fA-F]{6}$/.test(raw)) {
        setStyle(key, raw.toLowerCase());
    }
}

function commitColorText(key) {
    const raw = key === 'fg' ? fgText.value : bgText.value;

    if (/^#[0-9a-fA-F]{6}$/.test(raw)) {
        setStyle(key, raw.toLowerCase());

        return;
    }

    if (key === 'fg') {
        fgText.value = style.value.fg;
    } else {
        bgText.value = style.value.bg;
    }
}

function setField(key, value) {
    fields.value = { ...fields.value, [key]: value };
}

function setLink(index, key, value) {
    const links = fields.value.links.map((item, itemIndex) => (itemIndex === index ? { ...item, [key]: value } : item));
    setField('links', links);
}

function addLink() {
    setField('links', [...(fields.value.links ?? []), { label: '', url: '' }]);
}

function removeLink(index) {
    const links = fields.value.links.filter((_, itemIndex) => itemIndex !== index);
    setField('links', links.length ? links : [{ label: '', url: '' }]);
}

function onFile(event) {
    const file = event.target.files?.[0] ?? null;
    contentName.value = file?.name ?? '';
    emit('file', file);
}

function onLogo(event) {
    const file = event.target.files?.[0] ?? null;
    logoName.value = file?.name ?? '';
    emit('logo', file);
}
</script>

<template>
    <div class="space-y-5">
        <section class="rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)]">
            <div class="mb-4">
                <h2 class="text-base font-semibold">{{ type.label }}</h2>
                <p class="mt-1 text-sm text-[#6d6a7a]">{{ type.hint }}</p>
            </div>
            <div class="grid gap-4">
                <label v-for="field in type.fields" :key="field.key" class="grid gap-1.5 text-sm">
                    <span class="font-medium">
                        {{ field.label }}
                        <span v-if="field.required" class="text-[#9b2335]">*</span>
                    </span>
                    <textarea
                        v-if="field.type === 'textarea'"
                        :value="fields[field.key]"
                        rows="4"
                        :maxlength="field.max"
                        :placeholder="field.placeholder"
                        :class="inputClass"
                        @input="setField(field.key, $event.target.value)"
                    />
                    <select
                        v-else-if="field.type === 'select'"
                        :value="fields[field.key]"
                        :class="inputClass"
                        @change="setField(field.key, $event.target.value)"
                    >
                        <option v-for="option in field.options" :key="option.value" :value="option.value">{{ option.label }}</option>
                    </select>
                    <span v-else-if="field.type === 'toggle'" class="flex items-center gap-2 font-normal text-[#3f3d48]">
                        <input
                            :checked="fields[field.key]"
                            type="checkbox"
                            class="size-4 accent-[#9b2335]"
                            @change="setField(field.key, $event.target.checked)"
                        />
                        Enable
                    </span>
                    <div v-else-if="field.type === 'links'" class="grid gap-2">
                        <div v-for="(link, index) in fields.links" :key="index" class="grid gap-2 sm:grid-cols-[160px_1fr_auto]">
                            <input :value="link.label" type="text" placeholder="Label" :class="inputClass" @input="setLink(index, 'label', $event.target.value)" />
                            <input :value="link.url" type="url" placeholder="https://" :class="inputClass" @input="setLink(index, 'url', $event.target.value)" />
                            <button type="button" class="rounded-xl px-3 text-[#6d6a7a] hover:bg-[#f4f5f8]" @click="removeLink(index)">Remove</button>
                        </div>
                        <button type="button" class="justify-self-start rounded-full bg-[#f8e8ea] px-3 py-1.5 text-[13px] font-medium text-[#7a2030]" @click="addLink">
                            Add link
                        </button>
                    </div>
                    <input
                        v-else
                        :value="fields[field.key]"
                        :type="field.type === 'password' ? 'password' : field.type === 'datetime' ? 'datetime-local' : field.type"
                        :placeholder="field.placeholder"
                        :maxlength="field.max"
                        :class="inputClass"
                        @input="setField(field.key, $event.target.value)"
                    />
                    <span v-if="field.help" class="text-xs text-[#8a8794]">{{ field.help }}</span>
                </label>
                <div v-if="type.file" class="grid gap-1.5 text-sm">
                    <span class="font-medium">Upload file</span>
                    <label class="flex min-w-0 cursor-pointer items-center gap-3">
                        <input type="file" class="sr-only" :accept="type.accept || undefined" @change="onFile" />
                        <span class="shrink-0 rounded-full bg-[#f8e8ea] px-3 py-1.5 text-[13px] font-medium text-[#7a2030]">Choose file</span>
                        <span class="truncate text-[#6d6a7a]">{{ contentName || (hasStoredFile && !removeFile ? storedFileName : 'No file yet') }}</span>
                    </label>
                    <span v-if="hasStoredFile && !removeFile" class="text-xs text-[#8a8794]">Saved file: {{ storedFileName }}</span>
                    <label v-if="hasStoredFile" class="flex cursor-pointer items-center gap-2 text-[#5c5a66]">
                        <input v-model="removeFile" type="checkbox" class="size-4 accent-[#9b2335]" />
                        Remove saved file
                    </label>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-[#e6e7ee] bg-white p-5 shadow-[0_1px_2px_rgba(20,18,31,0.04)]">
            <h2 class="text-base font-semibold">Appearance</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1.5 text-sm">
                    <span class="font-medium">QR name</span>
                    <input v-model="title" type="text" maxlength="140" placeholder="Optional, shown in the project list" :class="inputClass" />
                </label>
                <div class="grid min-w-0 gap-1.5 text-sm">
                    <span class="font-medium">
                        Center logo
                        <span v-if="type.logo_required" class="text-[#9b2335]">*</span>
                    </span>
                    <label class="flex min-w-0 cursor-pointer items-center gap-3">
                        <input type="file" class="sr-only" accept="image/png,image/jpeg,image/webp,image/gif" @change="onLogo" />
                        <span class="shrink-0 rounded-full bg-[#f8e8ea] px-3 py-1.5 text-[13px] font-medium text-[#7a2030]">Choose logo</span>
                        <span class="truncate text-[#6d6a7a]">{{ logoName || (hasStoredLogo ? 'Saved logo' : 'No file yet') }}</span>
                    </label>
                    <span v-if="hasStoredLogo && !logoName" class="text-xs text-[#8a8794]">The previous logo stays until you replace it.</span>
                </div>
                <div class="grid min-w-0 gap-1.5 text-sm">
                    <span class="font-medium">Module color</span>
                    <span class="flex min-w-0 items-center gap-2">
                        <input :value="style.fg" type="color" class="color-well" aria-label="Choose module color" @input="setStyle('fg', $event.target.value)" />
                        <input :value="fgText" type="text" maxlength="7" spellcheck="false" :class="hexClass" aria-label="Module color code" @input="onColorText('fg', $event)" @change="commitColorText('fg')" />
                    </span>
                </div>
                <div class="grid min-w-0 gap-1.5 text-sm">
                    <span class="font-medium">Background color</span>
                    <span class="flex min-w-0 items-center gap-2">
                        <input :value="style.bg" type="color" class="color-well" aria-label="Choose background color" @input="setStyle('bg', $event.target.value)" />
                        <input :value="bgText" type="text" maxlength="7" spellcheck="false" :class="hexClass" aria-label="Background color code" @input="onColorText('bg', $event)" @change="commitColorText('bg')" />
                    </span>
                </div>
                <div class="grid gap-1.5 text-sm">
                    <span class="font-medium">Module shape</span>
                    <div class="flex rounded-xl border border-[#e6e7ee] p-1">
                        <button
                            v-for="shape in shapes"
                            :key="shape.id"
                            type="button"
                            class="flex-1 cursor-pointer rounded-lg px-2 py-1.5 text-[13px] font-medium transition active:scale-95"
                            :class="style.shape === shape.id ? 'bg-[#f8e8ea] text-[#7a2030] shadow-sm' : 'text-[#5c5a66] hover:bg-[#f8e8ea]/70'"
                            @click="setStyle('shape', shape.id)"
                        >
                            {{ shape.label }}
                        </button>
                    </div>
                </div>
                <label class="grid gap-1.5 text-sm sm:col-span-2">
                    <span class="flex justify-between font-medium">
                        <span>Margin</span>
                        <span class="text-[#6d6a7a]">{{ style.margin }}</span>
                    </span>
                    <input :value="style.margin" type="range" min="0" max="8" step="1" class="cursor-pointer accent-[#9b2335]" @input="setStyle('margin', Number($event.target.value))" />
                </label>
            </div>
        </section>
    </div>
</template>
