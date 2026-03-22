<script setup lang="ts">
defineProps<{
    color?: string;
}>();
</script>

<template>
    <span
        class="inline-block"
        :style="{
            color: color || 'var(--nd-red)',
            animation: 'nd-blink 1s step-end infinite',
            fontFamily: 'var(--nd-font-mono)',
        }"
        >_</span
    >
</template>
