<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppShell from '../../Components/AppShell.vue';
import DateField from '../../Components/DateField.vue';

const props = defineProps({
    link: { type: Object, required: true },
    filters: { type: Object, required: true },
    total: { type: Number, required: true },
    days: { type: Array, required: true },
    places: { type: Array, required: true },
    clicks: { type: Array, required: true },
});

const page = usePage();
const success = computed(() => page.props.flash?.success ?? '');
const form = useForm({
    url: props.link.url,
    title: props.link.title,
});
const dari = ref(props.filters.from);
const sampai = ref(props.filters.to);
const scroller = ref(null);
const peak = computed(() => Math.max(1, ...props.days.map((day) => day.count)));
const busiestPlace = computed(() => Math.max(1, ...props.places.map((place) => place.count)));

function save() {
    form.put(`/tautan/${props.link.code}`, { preserveScroll: true });
}

function apply() {
    router.get(`/tautan/${props.link.code}`, {
        dari: dari.value,
        sampai: sampai.value,
    }, {
        preserveScroll: true,
    });
}

function dayNumber(date) {
    return date.slice(8);
}

function monthName(date, index) {
    const month = date.slice(0, 7);
    const previous = props.days[index - 1]?.date.slice(0, 7);

    if (month === previous) {
        return '';
    }

    return new Intl.DateTimeFormat('en', { month: 'short' }).format(new Date(`${date}T00:00:00`));
}

function scrollChart(direction) {
    scroller.value?.scrollBy({ left: direction * 220, behavior: 'smooth' });
}

function formatTime(value) {
    return new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>

<template>
    <Head :title="link.title" />
    <AppShell>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <Link href="/tautan" class="text-sm text-[#7a2030]">Short links</Link>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ link.title }}</h1>
                <p class="mt-1 break-all text-sm text-[#7a2030]">{{ link.short_url }}</p>
            </div>
            <p class="text-sm text-[#6d6a7a]">{{ link.clicks }} clicks</p>
        </div>
        <p v-if="success" class="mb-4 rounded-2xl bg-[#ecfdf3] px-4 py-3 text-sm text-[#166534]">{{ success }}</p>
        <form class="mb-4 grid gap-4 rounded-3xl border border-[#e6e7ee] bg-white p-5 sm:grid-cols-2" @submit.prevent="save">
            <label class="grid gap-1.5 text-sm sm:col-span-2">
                <span class="font-medium">Destination</span>
                <input v-model="form.url" type="text" required class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]" />
                <span v-if="form.errors.url" class="text-[#9f1239]">{{ form.errors.url }}</span>
            </label>
            <label class="grid gap-1.5 text-sm">
                <span class="font-medium">Name</span>
                <input v-model="form.title" type="text" maxlength="140" class="rounded-xl border border-[#e6e7ee] px-3 py-2.5 outline-none focus:border-[#e3b3ba] focus:ring-4 focus:ring-[#f8e8ea]" />
            </label>
            <div class="flex items-end">
                <button type="submit" class="rounded-full bg-[#7a2030] px-4 py-2.5 text-sm font-medium text-white disabled:opacity-50" :disabled="form.processing">
                    {{ form.processing ? 'Saving…' : 'Save destination' }}
                </button>
            </div>
        </form>
        <form class="relative z-10 mb-4 flex flex-wrap items-end gap-3 rounded-3xl border border-[#e6e7ee] bg-white p-4" @submit.prevent="apply">
            <DateField v-model="dari" label="From" />
            <DateField v-model="sampai" label="To" />
            <button type="submit" class="rounded-full bg-[#f8e8ea] px-4 py-2 text-sm font-medium text-[#7a2030]">View</button>
            <p class="text-sm text-[#6d6a7a]">{{ total }} clicks in this range</p>
        </form>
        <section class="rounded-3xl border border-[#e6e7ee] bg-white p-5">
            <h2 class="text-base font-semibold">Clicks by date</h2>
            <div class="mt-4 flex items-center gap-2">
                <button type="button" class="grid size-8 shrink-0 cursor-pointer place-items-center rounded-full border border-[#e6e7ee] text-[#7a2030]" aria-label="Earlier dates" @click="scrollChart(-1)">
                    <ChevronLeft :size="16" />
                </button>
                <div ref="scroller" class="chart-scroll min-w-0 flex-1 overflow-x-auto">
                    <div class="flex h-44 min-w-max items-end gap-2 pt-5">
                        <div v-for="(day, index) in days" :key="day.date" class="relative flex w-8 shrink-0 flex-col items-center gap-1">
                            <span v-if="monthName(day.date, index)" class="absolute -top-5 left-0 text-[11px] font-medium whitespace-nowrap text-[#7a2030]">{{ monthName(day.date, index) }}</span>
                            <span class="text-[11px] text-[#6d6a7a]">{{ day.count || '' }}</span>
                            <div class="flex h-24 w-full items-end rounded-md bg-[#f8e8ea]">
                                <div class="w-full rounded-md bg-[#7a2030]" :style="{ height: `${Math.max(day.count ? 8 : 0, (day.count / peak) * 100)}%` }" />
                            </div>
                            <span class="text-[11px] text-[#6d6a7a]">{{ dayNumber(day.date) }}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="grid size-8 shrink-0 cursor-pointer place-items-center rounded-full border border-[#e6e7ee] text-[#7a2030]" aria-label="Later dates" @click="scrollChart(1)">
                    <ChevronRight :size="16" />
                </button>
            </div>
        </section>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <section class="rounded-3xl border border-[#e6e7ee] bg-white p-5">
                <h2 class="text-base font-semibold">Locations</h2>
                <p v-if="places.length === 0" class="mt-3 text-sm text-[#6d6a7a]">No clicks in this date range.</p>
                <ul v-else class="mt-4 grid gap-3">
                    <li v-for="place in places" :key="place.name">
                        <div class="mb-1 flex justify-between gap-3 text-sm">
                            <span class="font-medium">{{ place.name }}</span>
                            <span class="text-[#6d6a7a]">{{ place.count }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-[#f8e8ea]">
                            <div class="h-2 rounded-full bg-[#7a2030]" :style="{ width: `${(place.count / busiestPlace) * 100}%` }" />
                        </div>
                    </li>
                </ul>
            </section>
            <section class="rounded-3xl border border-[#e6e7ee] bg-white p-5">
                <h2 class="text-base font-semibold">Visitors</h2>
                <p v-if="clicks.length === 0" class="mt-3 text-sm text-[#6d6a7a]">No clicks in this date range.</p>
                <ul v-else class="mt-3 divide-y divide-[#f0f1f5]">
                    <li v-for="(click, index) in clicks" :key="`${click.at}-${index}`" class="grid gap-0.5 py-3 text-sm">
                        <span class="font-medium">{{ click.device }}</span>
                        <span class="text-[#6d6a7a]">{{ click.location }}</span>
                        <span class="text-xs text-[#8a8794]">{{ formatTime(click.at) }}</span>
                    </li>
                </ul>
            </section>
        </div>
    </AppShell>
</template>
