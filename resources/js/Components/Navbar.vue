<script setup>
// import {isDark, sidebarState, toggleDarkMode} from '@/Composables'
import { sidebarState } from '@/Composables'
import {
    IconLanguage,
    IconLogout,
    IconMenu2,
    IconMoon,
    IconSun
} from '@tabler/icons-vue';
// import ProfilePhoto from "@/Components/ProfilePhoto.vue";
import {Link, router, usePage} from "@inertiajs/vue3";
import Menu from 'primevue/menu';
import Button from "primevue/button";
import {ref} from "vue";
// import {loadLanguageAsync} from "laravel-vue-i18n";

defineProps({
    title: String
})

// const menu = ref();
// const locales = ref([
//     {
//         label: 'English',
//         command: () => {
//             changeLanguage('en');
//         }
//     },
//     {
//         label: '中文',
//         command: () => {
//             changeLanguage('tw')
//         }
//     }
// ]);
//
// const toggle = (event) => {
//     menu.value.toggle(event);
// };
//
// const changeLanguage = async (langVal) => {
//     try {
//         await loadLanguageAsync(langVal);
//         await axios.get(`/locale/${langVal}`);
//     } catch (error) {
//         console.error('Error changing locale:', error);
//     }
// };

const handleLogOut = () => {
    router.post(route('logout'))
}
</script>

<template>
    <nav
        aria-label="secondary"
        class="sticky top-0 z-20 py-2 px-3 md:px-5 bg-surface-50 dark:bg-surface-950 flex items-center gap-3 transition-colors duration-200 w-full"
    >
        <!-- <Button
            type="button"
            severity="secondary"
            rounded
            variant="text"
            class="shrink-0"
            @click="sidebarState.isOpen = !sidebarState.isOpen"
        >
            <template #icon>
                <IconMenu2 size="18" stroke-width="1.5" />
            </template>
        </Button> -->

        <div
            class="font-semibold text-surface-700 dark:text-surface-300 text-nowrap"
        >
            {{ title }}
        </div>
        <div class="flex items-center justify-end w-full">
            <!-- <Button
                type="button"
                severity="secondary"
                rounded
                variant="text"
                @click="() => { toggleDarkMode() }"
            >
                <template #icon>
                    <IconSun v-if="!isDark" size="20" stroke-width="1.5" />
                    <IconMoon v-if="isDark" size="20" stroke-width="1.5" />
                </template>
            </Button> -->

            <!-- <Button
                type="button"
                severity="secondary"
                rounded
                variant="text"
            >
                <template #icon>
                    <IconLanguage size="20" stroke-width="1.5" />
                </template>
            </Button> -->

            <Button
                type="button"
                severity="secondary"
                rounded
                variant="text"
                @click="handleLogOut"
            >
                <template #icon>
                    <IconLogout size="20" stroke-width="1.5" />
                </template>
            </Button>
        </div>
    </nav>

<!--    <Menu-->
<!--        ref="menu"-->
<!--        id="overlay_menu"-->
<!--        :model="locales"-->
<!--        :popup="true"-->
<!--        class="w-32"-->
<!--    />-->
</template>
