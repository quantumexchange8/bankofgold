<script setup>
defineProps({
    header: String,
    caption: String,
})
</script>

<template>
    <div class="flex flex-col items-center self-stretch w-full transition-colors duration-200 gap-3">
        <span class="text-lg md:text-xl font-semibold text-surface-950 dark:text-white">{{ header}}</span>
        <span class="font-medium text-xs md:text-base text-surface-500">{{ caption }}</span>
    </div>
</template>
