<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded border-surface-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-surface-700 dark:bg-surface-900 dark:focus:ring-indigo-600 dark:focus:ring-offset-surface-800"
    />
</template>
