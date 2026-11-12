<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppShell from '../Components/AppShell.vue';

const props = defineProps({
    qr: { type: Object, required: true },
    filters: { type: Object, required: true },
    total: { type: Number, required: true },
    days: { type: Array, required: true },
    places: { type: Array, required: true },
    scans: { type: Array, required: true },
});

const dari = ref(props.filters.from);
const sampai = ref(props.filters.to);
const peak = computed(() => Math.max(1, ...props.days.map((day) => day.count)));
const busiestPlace = computed(() => Math.max(1, ...props.places.map((place) => place.count)));

function apply() {
    router.get(`/kelola/${props.qr.code}`, {
        dari: dari.value,
        sampai: sampai.value,
    }, {
        preserveScroll: true,
    });
}

function formatTime(value) {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>

<template>
    <Head :title="`Analisis ${qr.title}`" />
    <AppShell>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <Link href="/" class="text-sm text-[#7a2030]">Proyek</Link>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">{{ qr.title }}</h1>
                <p class="mt-1 text-sm text-[#6d6a7a]">{{ qr.type_label }} · {{ qr.scans }} pindaian</p>
            </div>
            <Link :href="qr.edit_url" class="rounded-full bg-[#7a2030] px-4 py-2 text-sm font-medium text-white">Edit</Link>
        </div>
        <form class="mb-4 flex flex-wrap items-end gap-3 rounded-3xl border border-[#e6e7ee] bg-white p-4" @submit.prevent="apply">
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Dari</span>
                <input v-model="dari" type="date" required class="rounded-xl border border-[#e6e7ee] px-3 py-2 outline-none focus:border-[#e3b3ba]" />
            </label>
            <label class="grid gap-1 text-sm">
                <span class="font-medium">Sampai</span>
                <input v-model="sampai" type="date" required class="rounded-xl border border-[#e6e7ee] px-3 py-2 outline-none focus:border-[#e3b3ba]" />
            </label>
            <button type="submit" class="rounded-full bg-[#f8e8ea] px-4 py-2 text-sm font-medium text-[#7a2030]">Lihat</button>
            <p class="text-sm text-[#6d6a7a]">{{ total }} pindaian pada rentang ini</p>
        </form>
        <section class="rounded-3xl border border-[#e6e7ee] bg-white p-5">
            <h2 class="text-base font-semibold">Pindaian per tanggal</h2>
            <div class="mt-4 flex h-40 items-end gap-2 overflow-x-auto">
                <div v-for="day in days" :key="day.date" class="flex w-10 shrink-0 flex-col items-center gap-1">
                    <span class="text-[11px] text-[#6d6a7a]">{{ day.count }}</span>
                    <div class="flex h-28 w-full items-end rounded-lg bg-[#f8e8ea]">
                        <div class="w-full rounded-lg bg-[#7a2030]" :style="{ height: `${Math.max(day.count ? 8 : 0, (day.count / peak) * 100)}%` }" />
                    </div>
                    <span class="text-center text-[10px] leading-tight text-[#6d6a7a]">{{ day.label }}</span>
                </div>
            </div>
        </section>
        <div class="mt-4 grid gap-4 lg:grid-cols-2">
            <section class="rounded-3xl border border-[#e6e7ee] bg-white p-5">
                <h2 class="text-base font-semibold">Lokasi</h2>
                <p v-if="places.length === 0" class="mt-3 text-sm text-[#6d6a7a]">Belum ada pindaian pada tanggal ini.</p>
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
                <h2 class="text-base font-semibold">Siapa yang memindai</h2>
                <p v-if="scans.length === 0" class="mt-3 text-sm text-[#6d6a7a]">Belum ada pindaian pada tanggal ini.</p>
                <ul v-else class="mt-3 divide-y divide-[#f0f1f5]">
                    <li v-for="(scan, index) in scans" :key="`${scan.at}-${index}`" class="grid gap-0.5 py-3 text-sm">
                        <span class="font-medium">{{ scan.device }}</span>
                        <span class="text-[#6d6a7a]">{{ scan.location }}</span>
                        <span class="text-xs text-[#8a8794]">{{ formatTime(scan.at) }}</span>
                    </li>
                </ul>
            </section>
        </div>
    </AppShell>
</template>
