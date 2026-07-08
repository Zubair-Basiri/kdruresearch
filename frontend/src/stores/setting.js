// src/stores/setting.js
import { defineStore } from 'pinia';
import api from '@/services/api';

export const useSettingStore = defineStore('setting', {
    state: () => ({
        sidebar_type: [],
        sidebar_color: 'sidebar-default',
        sidebar_menu_style: 'sidebar-default',
        header_navbar: 'navs-default',
        app_name: 'KURD',
        footer_style: '',
        header_banner: 'default',
        theme_scheme_direction: 'ltr',
        theme_font_size: 'theme-fs-sm',
        theme_scheme: 'light',
        theme_color: {
            value: 'theme-color-default',
            colors: {
                '--bs-primary': '#3a57e8',
                '--bs-info': '#08B1BA'
            }
        },
        // Flag to prevent infinite loops
        _isSyncing: false,
    }),
getters: {
    sidebarType: (state) => state.sidebar_type,
    sidebarColor: (state) => state.sidebar_color,
    sidebarMenuStyle: (state) => state.sidebar_menu_style,
    headerNavbar: (state) => state.header_navbar,
    appName: (state) => state.app_name,
    footerStyle: (state) => state.footer_style,
    headerBanner: (state) => state.header_banner,
    themeSchemeDirection: (state) => state.theme_scheme_direction,
    themeFontSize: (state) => state.theme_font_size,
    themeScheme: (state) => state.theme_scheme,
    themeColor: (state) => state.theme_color,
},
actions: {
    // Initialize from backend after login
    async loadFromBackend() {
        console.log('loadFromBackend called');
        try {
            const res = await api.get('/user/theme/load');
            console.log('Backend response:', res.data);
            const settings = res.data.settings;
            if (settings) {
                this.setSetting(settings);
                console.log('Settings applied to store, new state:', this.$state);
            }
        } catch (error) {
            console.warn('Could not load theme settings from backend', error);
        }
    },
    // Save current state to backend after each change
    async saveToBackend() {
        if (this._isSyncing) return;
        try {
            await api.post('/user/theme/save', { settings: this.$state });
        } catch (error) {
            console.warn('Could not save theme settings to backend', error);
        }
    },
    setSetting(payload = {}) {
        Object.keys(payload).forEach(key => {
            if (Object.prototype.hasOwnProperty.call(this, key)) {
                this[key] = payload[key];
            }
        });
    },
    updateSidebarType(newValue) {
        this.sidebar_type = newValue;
        this.saveToBackend();
    },
    updateSidebarColor(newColor) {
        this.sidebar_color = newColor;
        this.saveToBackend();
    },
    updateSidebarMenuStyle(newStyle) {
        this.sidebar_menu_style = newStyle;
        this.saveToBackend();
    },
    updateHeaderNavbar(newValue) {
        this.header_navbar = newValue;
        this.saveToBackend();
    },
    updateHeaderBanner(newValue) {
        this.header_banner = newValue;
    },
    updateThemeFontSize(newSize) {
        this.theme_font_size = newSize;
    },
    updateThemeSchemeDirection(newDirection) {
        this.theme_scheme_direction = newDirection;
    },
    updateThemeScheme(scheme) {
        this.theme_scheme = scheme;
        // Optional: automatically load preset colors based on scheme
        if (scheme === 'dark') {
            this.theme_color = {
                value: 'theme-color-dark',
                colors: {
                    '--{{prefix}}primary': '#6366f1',
                    '--{{prefix}}info': '#0ea5e9'
                }
            };
        } else if (scheme === 'custom') {
            // keep current custom colors; do nothing
        } else {
            // default / light
            this.theme_color = {
                value: 'theme-color-default',
                colors: {
                    '--{{prefix}}primary': '#3a57e8',
                    '--{{prefix}}info': '#08B1BA'
                }
            };
        }
        this.saveToBackend();
    },
    updateThemeColor(newColor) {
        this.theme_color = newColor;
        this.saveToBackend();
    },
    resetState() {
        this._isSyncing = true;
        const defaults = {
            sidebar_type: [],
            sidebar_color: 'sidebar-default',
            sidebar_menu_style: 'sidebar-default',
            header_navbar: 'navs-default',
            app_name: 'KURD',
            footer_style: '',
            header_banner: 'default',
            theme_scheme_direction: 'ltr',
            theme_font_size: 'theme-fs-sm',
            theme_scheme: 'light',
            theme_color: {
                value: 'theme-color-default',
                colors: {
                    '--bs-primary': '#3a57e8',
                    '--bs-info': '#08B1BA'
                }
            },
        };
        Object.assign(this.$state, defaults);
        this._isSyncing = false;
        this.saveToBackend();
    },
}
});