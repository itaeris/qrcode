<script setup>
import { computed } from 'vue';
import { icons } from '../icons';

const props = defineProps({
    name: { type: String, required: true },
    size: { type: Number, default: 16 },
});

const icon = computed(() => icons[props.name] ?? icons.QrCode);
</script>

<template>
    <component :is="icon" :size="size" :stroke-width="2" />
</template>
