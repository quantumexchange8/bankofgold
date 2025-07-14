<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-surface-300 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-surface-700 shadow-sm transition duration-150 ease-in-out hover:bg-surface-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 dark:border-surface-500 dark:bg-surface-800 dark:text-surface-300 dark:hover:bg-surface-700 dark:focus:ring-offset-surface-800"
    >
        <slot />
    </button>
</template>
