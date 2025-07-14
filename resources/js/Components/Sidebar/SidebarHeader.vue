<script setup>
import { Link } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { sidebarState } from '@/Composables'
import { IconX } from '@tabler/icons-vue';
</script>

<template>
    <div
        class="flex gap-2 w-full py-[18px] sticky"
        :class="{
                'pr-2 lg:pr-5 px-5': sidebarState.isOpen || sidebarState.isHovered,
                'px-3': !sidebarState.isOpen && !sidebarState.isHovered,
            }"
    >
        <Link
            :href="route('dashboard')"
            class="w-full flex items-center"
            :class="{
                'justify-center': !sidebarState.isOpen && !sidebarState.isHovered,
            }"
        >
            <div class="flex items-center self-stretch gap-2">
                <ApplicationLogo aria-hidden="true" class="w-10 h-10 shrink-0 grow-0 overflow-hidden fill-primary" />
                <div
                    v-show="sidebarState.isOpen || sidebarState.isHovered"
                    class="font-bold text-sm text-surface-800 dark:text-white"
                >
                    {{ $t('public.lucky_star_admin') }}
                </div>
            </div>
        </Link>

        <div
            class="inline-flex items-center justify-center p-3.5 hover:select-none hover:cursor-pointer lg:hidden"
            @click="sidebarState.isOpen = !sidebarState.isOpen"
            v-show="sidebarState.isOpen || sidebarState.isHovered"
        >
            <IconX size="20" color="#182230" stroke-width="1.5" />
        </div>
    </div>
</template>
