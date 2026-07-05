<template>
  <router-view />
</template>

<script>
import { onMounted, onUnmounted, computed, watch } from 'vue';
import { useSettingStore } from '@/stores/setting';
import { useAuthStore } from '@/stores/auth';
import '@/plugins/styles';

export default {
    name: 'App',
    setup() {
        const authStore = useAuthStore();
        const settingStore = useSettingStore();

        // Initialize settings with defaults
        settingStore.setSetting();

        const sidebarType = computed(() => settingStore.sidebar_type);

        const applyThemeColors = () => {
            const themeColors = settingStore.theme_color?.colors;
            if (!themeColors) return;
            const prefix = 'bs-';
            Object.entries(themeColors).forEach(([key, value]) => {
                const finalKey = key.replace('{{prefix}}', prefix);
                document.documentElement.style.setProperty(finalKey, value);
            });
            const primary = getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#3a57e8';
            document.documentElement.style.setProperty('--subheader-primary', primary);
        };

        const applyThemeScheme = () => {
            document.documentElement.setAttribute('data-theme', settingStore.theme_scheme);
        };

        watch(() => settingStore.theme_color, () => {
            applyThemeColors();
        }, { deep: true, immediate: true });

        watch(() => settingStore.theme_scheme, () => {
            applyThemeScheme();
            applyThemeColors();
        }, { immediate: true });

        // 🔥 NEW: Load theme settings after login
        watch(() => authStore.isAuthenticated, async (isAuth) => {
            if (isAuth) {
                console.log('User authenticated, loading theme settings...');
                await settingStore.loadFromBackend();
                // Apply loaded settings
                applyThemeColors();
                applyThemeScheme();
            }
        }, { immediate: true });

        const resizePlugin = () => {
            const sidebarResponsive = document.querySelector('[data-sidebar="responsive"]');
            if (window.innerWidth < 1025) {
                if (sidebarResponsive !== null) {
                    if (!sidebarResponsive.classList.contains('sidebar-mini')) {
                        sidebarResponsive.classList.add('on-resize');
                        settingStore.updateSidebarType([...sidebarType.value, 'sidebar-mini']);
                    }
                }
            } else {
                if (sidebarResponsive !== null) {
                    if (sidebarResponsive.classList.contains('sidebar-mini') && sidebarResponsive.classList.contains('on-resize')) {
                        sidebarResponsive.classList.remove('on-resize');
                        settingStore.updateSidebarType(
                            sidebarType.value.filter((item) => item !== 'sidebar-mini')
                        );
                    }
                }
            }
        };

        onMounted(() => {
            // Initial theme apply (defaults before login)
            applyThemeColors();
            applyThemeScheme();
            window.addEventListener('resize', resizePlugin);
            setTimeout(() => {
                resizePlugin();
            }, 200);
        });

        onUnmounted(() => {
            window.removeEventListener('resize', resizePlugin);
        });
    },
};
</script>

<style lang="scss">
@import '@/assets/custom-vue/scss/styles.scss';
</style>