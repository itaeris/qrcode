<script setup>
import { computed, ref } from 'vue';
import { Search } from '@lucide/vue';
import TypeIcon from './TypeIcon.vue';

const props = defineProps({
    types: { type: Array, required: true },
    modelValue: { type: String, required: true },
});

const emit = defineEmits(['update:modelValue']);
const query = ref('');

const visible = computed(() => {
    const needle = query.value.trim().toLowerCase();

    if (!needle) {
        return props.types;
    }

    return props.types.filter((type) => type.label.toLowerCase().includes(needle));
});
</script>

<template>
    <section>
        <div class="mb-3 flex items-center justify-between gap-3">
            <h1 class="text-sm font-medium text-[#6d6a7a]">Pilih jenis QR</h1>
            <label class="flex h-9 w-full max-w-[220px] items-center gap-2 rounded-full border border-[#e6e7ee] bg-white px-3 text-sm text-[#6d6a7a] shadow-sm">
                <Search :size="15" />
                <input v-model="query" type="search" placeholder="Cari jenis" class="w-full bg-transparent outline-none placeholder:text-[#a1a0ab]" />
            </label>
        </div>
        <div class="flex flex-wrap gap-2">
            <button
                v-for="type in visible"
                :key="type.id"
                type="button"
                class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-[13px] font-medium transition"
                :class="modelValue === type.id
                    ? 'border-[#e4d4ff] bg-[#efe7ff] text-[#6d28d9]'
                    : 'border-[#e6e7ee] bg-white text-[#2a2a32] shadow-[0_1px_2px_rgba(20,18,31,0.04)] hover:border-[#d5d6e0]'"
                :aria-pressed="modelValue === type.id"
                @click="emit('update:modelValue', type.id)"
            >
                <TypeIcon :name="type.icon" />
                <span>{{ type.label }}</span>
            </button>
        </div>
        <p v-if="visible.length === 0" class="mt-4 text-sm text-[#6d6a7a]">Tidak ada jenis yang cocok.</p>
    </section>
</template>
