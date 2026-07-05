<template>
  <default-navbar :isGoPro="false" :isSearch="true">
    <a href="#" class="navbar-brand">
      <brand-logo :color="true" />
      <h4 class="logo-title d-block d-xl-none" data-setting="app_name"><brand-name></brand-name></h4>
    </a>
    <div class="sidebar-toggle" data-toggle="sidebar" data-active="true" @click="toggleSidebar">
      <i class="icon d-flex">
        <svg width="20px" viewBox="0 0 24 24">
          <path fill="currentColor" d="M4,11V13H16L10.5,18.5L11.92,19.92L19.84,12L11.92,4.08L10.5,5.5L16,11H4Z" />
        </svg>
      </i>
    </div>
  </default-navbar>
</template>

<script setup>
/* eslint-disable no-undef */
import { computed, onMounted, onUnmounted } from 'vue'
import { useSettingStore } from '@/stores/setting'
// import { useMainStore } from '@/stores/main'
import DefaultNavbar from '../custom/navbar/DefaultNavbar.vue'

// Define props
defineProps({
  fullsidebar: { type: Boolean, default: false }
})

const settingStore = useSettingStore()
// const mainStore = useMainStore()

// Computed properties from stores
// const carts = computed(() => mainStore.carts) // Make sure this exists in main store
// const headerNavbar = computed(() => settingStore.headerNavbar)
const sidebarType = computed(() => settingStore.sidebarType)
// const themeSchemeDirection = computed(() => settingStore.themeSchemeDirection)

// const fullScreen = ref(false)
// const isHidden = ref(false)

// const openFullScreen = () => {
//   if (fullScreen.value) {
//     fullScreen.value = false
//     document.exitFullscreen()
//   } else {
//     fullScreen.value = true
//     document.documentElement.requestFullscreen()
//   }
// }

const onscroll = () => {
  const yOffset = document.documentElement.scrollTop
  const navbar = document.querySelector('.navs-sticky')
  if (navbar !== null) {
    if (yOffset >= 100) {
      navbar.classList.add('menu-sticky')
    } else {
      navbar.classList.remove('menu-sticky')
    }
  }
}

const toggleSidebar = () => {
  // Code Here
  if (sidebarType.value.includes('sidebar-mini')) {
    settingStore.updateSidebarType(
      sidebarType.value.filter((item) => item !== 'sidebar-mini')
    )
  } else {
    settingStore.updateSidebarType([...sidebarType.value, 'sidebar-mini'])
  }
}

// const updateRadio = (size) => {
//   settingStore.updateThemeFontSize(size)
// }

onMounted(() => {
  window.addEventListener('scroll', onscroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', onscroll)
})
</script>

<style>
.iq-product-menu-responsive .offcanvas-header {
  display: none;
}
</style>