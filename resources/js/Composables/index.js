// import { useDark, useToggle } from '@vueuse/core'
import { reactive } from 'vue'

// export const isDark = useDark({disableTransition: false})
// export const toggleDarkMode = useToggle(isDark)

export const sidebarState = reactive({
    isOpen: window.innerWidth > 1024,
    isHovered: false,
    handleHover(value) {
        if (window.innerWidth < 1024) {
            return
        }
        sidebarState.isHovered = value
    },
    handleWindowResize() {
        sidebarState.isOpen = window.innerWidth > 1024;
    },
})
