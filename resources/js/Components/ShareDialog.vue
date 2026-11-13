<script setup>
import { ChevronDown } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { api } from '../lib/api';

const props = defineProps({
    project: { type: Object, required: true },
    accounts: { type: Array, required: true },
});

const emit = defineEmits(['close', 'shares']);

const roles = [
    { value: 'edit', label: 'Can edit' },
    { value: 'view', label: 'View only' },
];

const selectedEmail = ref('');
const role = ref('edit');
const query = ref('');
const shares = ref([...(props.project.shares ?? [])]);
const busy = ref(false);
const error = ref('');
const userOpen = ref(false);
const roleOpen = ref(false);
const openShareRole = ref(null);
const userMenu = ref(null);
const roleMenu = ref(null);

const selected = computed(() => props.accounts.find((account) => account.email === selectedEmail.value) ?? null);

const available = computed(() => {
    const taken = new Set(shares.value.map((share) => share.email.toLowerCase()));
    const needle = query.value.trim().toLowerCase();

    return props.accounts.filter((account) => {
        if (taken.has(account.email.toLowerCase())) {
            return false;
        }

        if (needle === '') {
            return true;
        }

        return account.name.toLowerCase().includes(needle) || account.email.toLowerCase().includes(needle);
    });
});

function roleLabel(value) {
    return roles.find((item) => item.value === value)?.label ?? 'Can edit';
}

function closeMenus(event) {
    if (userMenu.value && !userMenu.value.contains(event.target)) {
        userOpen.value = false;
    }

    if (roleMenu.value && !roleMenu.value.contains(event.target)) {
        roleOpen.value = false;
    }

    if (!event.target.closest('[data-role-menu]')) {
        openShareRole.value = null;
    }
}

onMounted(() => document.addEventListener('pointerdown', closeMenus));
onBeforeUnmount(() => document.removeEventListener('pointerdown', closeMenus));

function chooseUser(account) {
    selectedEmail.value = account.email;
    userOpen.value = false;
    query.value = '';
}

function chooseRole(value) {
    role.value = value;
    roleOpen.value = false;
}

function apply(next) {
    shares.value = next;
    emit('shares', next);
}

async function add() {
    if (busy.value) {
        return;
    }

    if (selectedEmail.value === '') {
        error.value = 'Choose a user.';

        return;
    }

    busy.value = true;
    error.value = '';

    try {
        const data = await api(`/qr/${props.project.code}/shares`, {
            body: { email: selectedEmail.value, role: role.value },
        });
        apply(data.shares ?? []);
        selectedEmail.value = '';
    } catch (reason) {
        error.value = reason.message;
    } finally {
        busy.value = false;
    }
}

async function changeRole(share, next) {
    const previous = share.role;
    openShareRole.value = null;
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
            <form class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-start" @submit.prevent="add">
                <div ref="userMenu" class="relative min-w-0 flex-1">
                    <button
                        type="button"
                        class="flex w-full cursor-pointer items-center justify-between gap-2 rounded-xl border border-[#e6e7ee] px-3 py-2 text-left text-sm"
                        :aria-expanded="userOpen"
                        aria-haspopup="listbox"
                        @click="userOpen = !userOpen"
                    >
                        <span class="min-w-0 truncate" :class="selected ? 'text-[#1c1b22]' : 'text-[#8a8794]'">
                            {{ selected ? selected.name : 'Choose a user' }}
                        </span>
                        <ChevronDown :size="16" class="shrink-0 text-[#6d6a7a]" />
                    </button>
                    <div v-if="userOpen" class="absolute top-full right-0 left-0 z-20 mt-2 overflow-hidden rounded-2xl border border-[#e6e7ee] bg-white p-1 shadow-lg" role="listbox">
                        <input
                            v-model="query"
                            type="text"
                            placeholder="Search users"
                            class="w-full rounded-xl px-3 py-2 text-sm outline-none"
                        />
                        <div class="max-h-52 overflow-y-auto">
                            <button
                                v-for="account in available"
                                :key="account.id"
                                type="button"
                                class="block w-full cursor-pointer rounded-xl px-3 py-2 text-left hover:bg-[#f8e8ea]"
                                role="option"
                                @click="chooseUser(account)"
                            >
                                <span class="block text-sm font-medium">{{ account.name }}</span>
                                <span class="block truncate text-xs text-[#6d6a7a]">{{ account.email }}</span>
                            </button>
                            <p v-if="available.length === 0" class="px-3 py-2 text-sm text-[#6d6a7a]">No other accounts to add.</p>
                        </div>
                    </div>
                </div>
                <div ref="roleMenu" class="relative">
                    <button
                        type="button"
                        class="inline-flex w-full cursor-pointer items-center justify-between gap-2 rounded-xl border border-[#e6e7ee] px-3 py-2 text-sm sm:w-auto"
                        :aria-expanded="roleOpen"
                        aria-haspopup="menu"
                        @click="roleOpen = !roleOpen"
                    >
                        {{ roleLabel(role) }}
                        <ChevronDown :size="16" class="text-[#6d6a7a]" />
                    </button>
                    <div v-if="roleOpen" class="absolute right-0 z-20 mt-2 w-36 overflow-hidden rounded-2xl border border-[#e6e7ee] bg-white p-1 shadow-lg" role="menu">
                        <button
                            v-for="item in roles"
                            :key="item.value"
                            type="button"
                            class="block w-full cursor-pointer rounded-xl px-3 py-2 text-left text-sm hover:bg-[#f8e8ea]"
                            :class="role === item.value ? 'bg-[#f8e8ea] font-medium text-[#7a2030]' : ''"
                            role="menuitem"
                            @click="chooseRole(item.value)"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                </div>
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
                    <div class="relative" data-role-menu>
                        <button
                            type="button"
                            class="inline-flex cursor-pointer items-center gap-1 rounded-full border border-[#e6e7ee] px-3 py-1 text-xs"
                            :aria-expanded="openShareRole === share.user_id"
                            aria-label="Role"
                            @click="openShareRole = openShareRole === share.user_id ? null : share.user_id"
                        >
                            {{ roleLabel(share.role) }}
                            <ChevronDown :size="14" />
                        </button>
                        <div v-if="openShareRole === share.user_id" class="absolute right-0 z-20 mt-1 w-32 overflow-hidden rounded-2xl border border-[#e6e7ee] bg-white p-1 shadow-lg" role="menu">
                            <button
                                v-for="item in roles"
                                :key="item.value"
                                type="button"
                                class="block w-full cursor-pointer rounded-xl px-3 py-2 text-left text-xs hover:bg-[#f8e8ea]"
                                :class="share.role === item.value ? 'bg-[#f8e8ea] font-medium text-[#7a2030]' : ''"
                                @click="changeRole(share, item.value)"
                            >
                                {{ item.label }}
                            </button>
                        </div>
                    </div>
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
