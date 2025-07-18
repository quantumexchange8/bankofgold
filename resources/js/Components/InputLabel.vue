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
            'text-gray-700': !invalid,
            'text-red-500': invalid
        }"
    >
        <div v-if="value">
            {{ value }}<span class="text-red-500 text-xs font-medium"> *</span>
        </div>
        <div v-else>
            <slot />
        </div>
    </label>
</template>
