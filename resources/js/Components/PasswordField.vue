<script setup>
import { Eye, EyeOff } from '@lucide/vue';
import { ref } from 'vue';

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    autocomplete: { type: String, default: 'current-password' },
    error: { type: String, default: '' },
});

const model = defineModel({ type: String, required: true });
const visible = ref(false);
</script>

<template>
    <div class="grid gap-1.5 text-sm">
        <label :for="id" class="font-medium">{{ label }}</label>
        <div class="relative">
            <input
                :id="id"
                v-model="model"
                :type="visible ? 'text' : 'password'"
                :autocomplete="autocomplete"
                required
                class="w-full rounded-xl border border-[#e6e7ee] px-3 py-2.5 pr-11 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]"
            />
            <button
                type="button"
                class="absolute inset-y-0 right-0 grid w-11 place-items-center text-[#6d6a7a]"
                :aria-label="visible ? 'Hide password' : 'Show password'"
                @click="visible = !visible"
            >
                <EyeOff v-if="visible" :size="16" />
                <Eye v-else :size="16" />
            </button>
        </div>
        <p v-if="error" class="text-[#9f1239]">{{ error }}</p>
    </div>
</template>
