<script setup>
import { onMounted } from "vue";
import {
    IconCircleCheckFilled,
    IconAlertTriangleFilled,
    IconCircleXFilled,
    IconInfoOctagonFilled,
    IconX
} from '@tabler/icons-vue';

const props = defineProps({
    title: String,
    message: String,
    type: String, // Accepts 'success', 'info', 'warning', 'error'
    duration: {
        type: Number,
        default: 3000
    }
});

onMounted(() => {
    setTimeout(() => emit('remove'), props.duration);
});

const emit = defineEmits(['remove']);

// Determine icon based on the type
const iconComponent = {
    success: IconCircleCheckFilled,
    warning: IconAlertTriangleFilled,
    error: IconCircleXFilled,
    info: IconInfoOctagonFilled
}[props.type];

const bgColor = {
    success: 'bg-green-50/90 dark:bg-green-500/20',
    warning: 'bg-yellow-50/90 dark:bg-yellow-500/20',
    error: 'bg-red-50/90 dark:bg-red-500/20',
    info: 'bg-blue-50/90 dark:bg-blue-500/20',
}[props.type];

const borderColor = {
    success: 'border-green-200 dark:border-green-500/20',
    warning: 'border-yellow-200 dark:border-yellow-500/20',
    error: 'border-red-200 dark:border-red-500/20',
    info: 'border-blue-500/90 dark:border-blue-500/20',
}[props.type];

const textColor = {
    success: 'text-green-500',
    warning: 'text-yellow-500',
    error: 'text-red-500',
    info: 'text-blue-500',
}[props.type];

</script>
<template>
    <div
        class="mx-3 sm:mx-0 py-3 px-4 flex justify-center self-stretch gap-4 rounded border shadow-toast backdrop-blur-[10px]"
        :class="[
            message ? 'items-start' : 'items-center',
            borderColor,
            bgColor
        ]"
        role="alert"
    >
        <div :class="textColor">
            <component :is="iconComponent" size="20" />
        </div>
        <div
            class="flex flex-col items-start w-full text-sm"
            :class="{
                'gap-1': message
            }"
        >
            <div class="font-semibold" :class="textColor">
                {{ title }}
            </div>
            <div class="text-surface-700 text-xs dark:text-surface-300">
                {{ message }}
            </div>
        </div>
        <div
            class="hover:cursor-pointer select-none"
            :class="textColor"
            @click="emit('remove')"
        >
            <IconX size="16" stroke-width="1.5" />
        </div>
    </div>
</template>
