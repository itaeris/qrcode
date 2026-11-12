<script setup>
import { Calendar, ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const model = defineModel({ type: String, required: true });

defineProps({
    label: { type: String, required: true },
});

const open = ref(false);
const root = ref(null);
const view = ref(startOfMonth(parse(model.value)));
const weekdays = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

const formatted = computed(() => new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(parse(model.value)));
const monthLabel = computed(() => new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' }).format(view.value));
const cells = computed(() => {
    const first = startOfMonth(view.value);
    const offset = (first.getDay() + 6) % 7;
    const start = new Date(first.getFullYear(), first.getMonth(), 1 - offset);

    return Array.from({ length: 42 }, (_, index) => {
        const date = new Date(start.getFullYear(), start.getMonth(), start.getDate() + index);

        return {
            iso: iso(date),
            day: date.getDate(),
            outside: date.getMonth() !== view.value.getMonth(),
        };
    });
});

function parse(value) {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

function iso(date) {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

function startOfMonth(date) {
    return new Date(date.getFullYear(), date.getMonth(), 1);
}

function toggle() {
    if (!open.value) {
        view.value = startOfMonth(parse(model.value));
    }

    open.value = !open.value;
}

function shiftMonth(amount) {
    view.value = new Date(view.value.getFullYear(), view.value.getMonth() + amount, 1);
}

function pick(value) {
    model.value = value;
    open.value = false;
}

function onPointerDown(event) {
    if (!root.value?.contains(event.target)) {
        open.value = false;
    }
}

onMounted(() => document.addEventListener('pointerdown', onPointerDown));
onBeforeUnmount(() => document.removeEventListener('pointerdown', onPointerDown));
</script>

<template>
    <div ref="root" class="relative grid gap-1 text-sm">
        <span class="font-medium">{{ label }}</span>
        <button
            type="button"
            class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-[#e6e7ee] bg-white px-3 py-2 text-left"
            :aria-expanded="open"
            @click="toggle"
        >
            <Calendar :size="15" class="text-[#7a2030]" />
            {{ formatted }}
        </button>
        <div v-if="open" class="absolute top-full left-0 z-30 mt-2 w-[272px] rounded-2xl border border-[#e6e7ee] bg-white p-3 shadow-lg">
            <div class="mb-2 flex items-center justify-between">
                <button type="button" class="grid size-8 cursor-pointer place-items-center rounded-full text-[#7a2030] hover:bg-[#f8e8ea]" aria-label="Previous month" @click="shiftMonth(-1)">
                    <ChevronLeft :size="16" />
                </button>
                <span class="text-sm font-medium capitalize">{{ monthLabel }}</span>
                <button type="button" class="grid size-8 cursor-pointer place-items-center rounded-full text-[#7a2030] hover:bg-[#f8e8ea]" aria-label="Next month" @click="shiftMonth(1)">
                    <ChevronRight :size="16" />
                </button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-[11px] text-[#8a8794]">
                <span v-for="weekday in weekdays" :key="weekday">{{ weekday }}</span>
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                <button
                    v-for="cell in cells"
                    :key="cell.iso"
                    type="button"
                    class="grid h-8 cursor-pointer place-items-center rounded-full text-sm"
                    :class="cell.iso === model ? 'bg-[#7a2030] text-white' : cell.outside ? 'text-[#c4c2cb] hover:bg-[#f8e8ea]' : 'text-[#1c1b22] hover:bg-[#f8e8ea]'"
                    @click="pick(cell.iso)"
                >
                    {{ cell.day }}
                </button>
            </div>
        </div>
    </div>
</template>
