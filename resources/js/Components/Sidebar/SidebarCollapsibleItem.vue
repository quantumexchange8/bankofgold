<script setup>
import { Link } from '@inertiajs/vue3'
import {IconCircle} from "@tabler/icons-vue";
import {sidebarState} from "@/Composables/index.js";
import Badge from 'primevue/badge';

const props = defineProps({
    href: String,
    title: String,
    active: {
        type: Boolean,
        default: false,
    },
    external: {
        type: Boolean,
        default: false,
    },
    pendingCounts: Number
})

const { external } = props

const Tag = external ? 'a' : Link
</script>

<template>
    <li
        :class="[
            'text-sm rounded-lg hover:cursor-pointer hover:bg-primary-50 dark:hover:bg-transparent mt-1',
        ]"
    >
        <component
            :is="Tag"
            :href="href"
            v-bind="$attrs"
            :class="[
                'p-3 flex gap-3 items-center hover:text-primary-500 dark:hover:text-primary-300 w-full',
                {
                    'text-primary-500 dark:text-primary-300': active,
                    'text-surface-500 dark:text-surface-400': !active,
                },
            ]"
        >
            <div class="p-1 flex items-center justify-center">
                <IconCircle
                    size="10"
                />
            </div>
            <div v-show="sidebarState.isOpen || sidebarState.isHovered" class="font-medium">
                {{ title }}
            </div>
            <Badge
                v-if="pendingCounts"
                :value="pendingCounts"
                size="small"
                severity="info"
            ></Badge>
        </component>
    </li>
</template>
