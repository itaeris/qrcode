<script setup>
import { ref } from 'vue';
import { api } from '../lib/api';

const props = defineProps({
    project: { type: Object, required: true },
});

const emit = defineEmits(['close', 'shares']);

const email = ref('');
const role = ref('edit');
const shares = ref([...(props.project.shares ?? [])]);
const busy = ref(false);
const error = ref('');

function apply(next) {
    shares.value = next;
    emit('shares', next);
}

async function add() {
    if (busy.value) {
        return;
    }

    busy.value = true;
    error.value = '';

    try {
        const data = await api(`/qr/${props.project.code}/shares`, {
            body: { email: email.value.trim(), role: role.value },
        });
        apply(data.shares ?? []);
        email.value = '';
    } catch (reason) {
        error.value = reason.message;
    } finally {
        busy.value = false;
    }
}

async function changeRole(share, next) {
    const previous = share.role;
    share.role = next;
    error.value = '';

    try {
        const data = await api(`/qr/${props.project.code}/shares/${share.user_id}`, {
            body: { role: next },
        });
        apply(data.shares ?? []);
    } catch (reason) {
        share.role = previous;
        error.value = reason.message;
    }
}

async function remove(share) {
    if (busy.value) {
        return;
    }

    busy.value = true;
    error.value = '';

    try {
        const data = await api(`/qr/${props.project.code}/shares/${share.user_id}/delete`, { body: {} });
        apply(data.shares ?? []);
    } catch (reason) {
        error.value = reason.message;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="fixed inset-0 z-30 grid place-items-center bg-[#1c1b22]/40 px-4" @click.self="emit('close')">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-labelledby="share-title">
            <h2 id="share-title" class="text-lg font-semibold">Share project</h2>
            <p class="mt-1 text-sm text-[#6d6a7a]">{{ project.title }}</p>
            <form class="mt-4 flex flex-col gap-2 sm:flex-row" @submit.prevent="add">
                <input
                    v-model="email"
                    type="email"
                    required
                    placeholder="Email"
                    class="min-w-0 flex-1 rounded-xl border border-[#e6e7ee] px-3 py-2 text-sm outline-none focus:border-[#e3b3ba]"
                />
                <select v-model="role" class="rounded-xl border border-[#e6e7ee] px-3 py-2 text-sm outline-none focus:border-[#e3b3ba]" aria-label="Role">
                    <option value="edit">Can edit</option>
                    <option value="view">View only</option>
                </select>
                <button type="submit" class="cursor-pointer rounded-full bg-[#7a2030] px-4 py-2 text-sm font-medium text-white disabled:opacity-50" :disabled="busy">
                    Share
                </button>
            </form>
            <p v-if="error" class="mt-2 text-sm text-[#9f1239]">{{ error }}</p>
            <ul v-if="shares.length" class="mt-4 divide-y divide-[#f0f1f5] text-sm">
                <li v-for="share in shares" :key="share.user_id" class="flex items-center gap-3 py-3">
                    <div class="min-w-0 flex-1">
                        <div class="truncate font-medium">{{ share.name }}</div>
                        <div class="truncate text-xs text-[#6d6a7a]">{{ share.email }}</div>
                    </div>
                    <select
                        :value="share.role"
                        class="rounded-full border border-[#e6e7ee] px-2 py-1 text-xs"
                        aria-label="Role"
                        @change="changeRole(share, $event.target.value)"
                    >
                        <option value="edit">Can edit</option>
                        <option value="view">View only</option>
                    </select>
                    <button type="button" class="cursor-pointer text-xs font-medium text-[#9f1239]" @click="remove(share)">Remove</button>
                </li>
            </ul>
            <p v-else class="mt-4 text-sm text-[#6d6a7a]">Only you can see this project.</p>
            <div class="mt-5 flex justify-end">
                <button type="button" class="cursor-pointer rounded-full border border-[#e6e7ee] px-4 py-2 text-sm font-medium" @click="emit('close')">Done</button>
            </div>
        </div>
    </div>
</template>
