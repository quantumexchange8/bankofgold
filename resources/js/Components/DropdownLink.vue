<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-surface-700 transition duration-150 ease-in-out hover:bg-surface-100 focus:bg-surface-100 focus:outline-none dark:text-surface-300 dark:hover:bg-surface-800 dark:focus:bg-surface-800"
    >
        <slot />
    </Link>
</template>
