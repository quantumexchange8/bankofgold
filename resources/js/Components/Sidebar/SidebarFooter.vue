
<script setup>
import { Avatar } from "primevue";
import { sidebarState } from '@/Composables'
import {usePage, Link} from "@inertiajs/vue3";
import {
    IconUser,
} from "@tabler/icons-vue";

const user = usePage().props.auth.user;
</script>

<template>
    <div
        class="flex-shrink-0 sticky bottom-0 p-3"
    >
        <Link
            :href="route('profile.edit')"
            :class="[
                'flex select-none self-stretch cursor-pointer transition-colors duration-200',
                sidebarState.isOpen || sidebarState.isHovered ? 'gap-3 py-2 px-3 items-center' : 'justify-center p-0',
                {
                    'text-surface-950 dark:text-white hover:text-primary hover:bg-primary-50 dark:hover:bg-surface-800 dark:hover:text-primary-500 rounded-lg': !route().current('profile.edit'),
                    'text-primary hover:bg-primary-100 rounded-lg': route().current('profile.edit'),
                    'bg-primary-50 dark:bg-surface-900 border border-primary': route().current('profile.edit') && (sidebarState.isOpen || sidebarState.isHovered),
                },
            ]"
        >
            <Avatar
                v-if="usePage().props.auth.profile_photo"
                :image="usePage().props.auth.profile_photo"
            />
            <Avatar
                v-else
                :class="{'border border-primary': route().current('profile.edit') && !sidebarState.isOpen && !sidebarState.isHovered}"
            >
                <template #icon>
                    <IconUser size="20" stroke-width="1.5" />
                </template>
            </Avatar>

            <div v-if="sidebarState.isOpen || sidebarState.isHovered" class="flex flex-col text-sm">
                <div class="flex items-center gap-1 font-semibold">
                    <span class="max-w-28 truncate">{{ user.username }}</span>
                </div>
                <span class="text-xs text-surface-500 dark:text-surface-400 max-w-36 truncate">{{ user.email }}</span>
            </div>
        </Link>
    </div>
</template>
