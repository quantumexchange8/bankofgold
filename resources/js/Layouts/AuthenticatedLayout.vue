<script setup>
import {Head} from "@inertiajs/vue3";
import { sidebarState } from "@/Composables";
import Sidebar from "@/Components/Sidebar/Sidebar.vue";
import Navbar from "@/Components/Navbar.vue";
import ToastList from "@/Components/ToastList.vue";
import ConfirmationDialog from "@/Components/ConfirmationDialog.vue";

defineProps({
    title: String
})
</script>

<template>
    <Head :title="title"></Head>

    <div class="min-h-screen bg-surface-50 dark:bg-surface-950 transition-colors duration-200">
        <!-- Sidebar -->
        <!-- <Sidebar /> -->

        <div
            style="transition-property: margin; transition-duration: 150ms"
            :class="[
                'min-h-screen flex flex-col',
                // {
                //     'lg:ml-[252px]': sidebarState.isOpen,
                //     'md:ml-0 lg:ml-[84px]': !sidebarState.isOpen,
                // },
            ]"
        >
            <!-- Navbar -->
            <Navbar :title="title" />

            <!-- Page Content -->
            <main class="flex flex-1 justify-center items-start px-3 pt-3 pb-12 md:px-5 md:pt-5">
                <div class="w-full max-w-[1440px]">
                    <!-- Toast -->
                    <ToastList />
                    <!-- Confirmation Dialog -->
                    <ConfirmationDialog />

                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
