<script setup>
defineProps({
    value: {
        type: String,
    },
    invalid: Boolean
});
</script>

<template>
    <label
        class="block font-medium text-xs"
        :class="{
            'text-surface-700 dark:text-surface-300': !invalid,
            'text-red-500': invalid
        }"
    >
        <span v-if="value">
            {{ value }}<span class="text-red-500 text-sm font-medium"> *</span>
        </span>
        <div v-else>
            <div class="mb-0.5">
                <slot />
            </div>
        </div>
    </label>
</template>
