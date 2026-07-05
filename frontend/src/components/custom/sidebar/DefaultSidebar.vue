<template>
  <aside
    id="first-tour"
    :class="`sidebar sidebar-base ${sidebarColor} ${sidebarMenuStyle} ${sidebarType.join(' ')}`"
    data-toggle="main-sidebar"
    data-sidebar="responsive"
  >
    <div class="sidebar-header d-flex align-items-center justify-content-start">
      <router-link :to="{ name: 'default.dashboard' }" class="navbar-brand">
        <brand-logo></brand-logo>
        <h4 class="logo-title" data-setting="app_name">
          <brand-name></brand-name>
        </h4>
      </router-link>
      <div class="sidebar-toggle" @click="toggleSidebar">
        <i class="icon">
          <svg
            width="20"
            height="20"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M4.25 12.2744L19.25 12.2744"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
            <path
              d="M10.2998 18.2988L4.2498 12.2748L10.2998 6.24976"
              stroke="currentColor"
              stroke-width="1.5"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </i>
      </div>
    </div>
    <div class="sidebar-body pt-0 data-scrollbar">
      <slot name="profile-card"></slot>
      <div class="sidebar-list">
        <slot></slot>
      </div>
    </div>
    <div class="sidebar-footer"></div>
  </aside>
</template>

<script setup>
import { onMounted, computed } from 'vue'
import { useSettingStore } from '@/stores/setting'
import Scrollbar from 'smooth-scrollbar'

const settingStore = useSettingStore()

// If you have other settings like sidebar_color, sidebar_menu_style, you need to add them to the setting store.
// For now, we keep them as static or computed from store.
// Since your original code used Vuex getters from 'setting' module, we assume those exist in the Pinia store.
// You may need to add them to setting.js state/getters accordingly.
const sidebarColor = computed(() => settingStore.sidebar_color)
const sidebarMenuStyle = computed(() => settingStore.sidebar_menu_style)
const sidebarType = computed(() => settingStore.sidebarType)

const toggleSidebar = () => {
  if (sidebarType.value.includes('sidebar-mini')) {
    settingStore.updateSidebarType(
      sidebarType.value.filter((item) => item !== 'sidebar-mini')
    )
  } else {
    settingStore.updateSidebarType([...sidebarType.value, 'sidebar-mini'])
  }
}

onMounted(() => {
  Scrollbar.init(document.querySelector('.data-scrollbar'), { continuousScrolling: false })
})
</script>

<style scoped>
@media (max-width: 767px) {
  .mobile-sidebar {
    width: 80% !important;
  }
}
</style>