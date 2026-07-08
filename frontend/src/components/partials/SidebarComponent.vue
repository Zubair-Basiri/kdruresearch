<template>
  <!-- Sidebar Component Start Here -->
  <default-sidebar>
    <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
      <!-- Home -->
      <side-menu title="Home" :static-item="true"></side-menu>
      <side-menu
        isTag="router-link"
        title="Dashboard"
        icon="view-grid"
        :route="{ to: 'default.dashboard' }"
      ></side-menu>

      <!-- Add Section – visible to admin & super_admin only -->
      <side-menu
      v-if="!authStore.isGuest"
        title="Add Section"
        icon="adjustment"
        toggle-id="menu-style"
        :caret-icon="true"
        :route="{ popup: 'false', to: 'menu-style' }"
        @onClick="toggle"
        :active="currentRoute.includes('menu-style')"
      >
        <b-collapse
          tag="ul"
          class="sub-nav"
          id="menu-style"
          accordion="sidebar-menu"
          :visible="currentRoute.includes('menu-style')"
        >
          <!-- Admin-only items -->
          <side-menu
            v-if="authStore.hasRole(['admin', 'super_admin'])"
            title="University"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="U"
            :route="{ to: 'universityList' }"
          ></side-menu>
          <side-menu
            v-if="authStore.hasRole(['admin', 'super_admin'])"
            title="Faculty"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="F"
            :route="{ to: 'facultyTable' }"
          ></side-menu>
          <side-menu
            v-if="authStore.hasRole(['admin', 'super_admin'])"
            title="Department"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="D"
            :route="{ to: 'departmentTable' }"
          ></side-menu>
          <side-menu
            v-if="authStore.hasRole(['admin', 'super_admin'])"
            title="Lecturers"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="L"
            :route="{ to: 'teachers' }"
          ></side-menu>
          <!-- Academic Journals – visible to all -->
          <side-menu
            title="Academic Journals"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="A"
            :route="{ to: 'academicJournals' }"
          ></side-menu>
        </b-collapse>
      </side-menu>

      <side-menu
      v-if="authStore.hasRole(['admin', 'super_admin'])"
        title="Key Findings"
        icon="table"
        :route="{ to: 'default.keyFindings' }"
      ></side-menu>

      <li><hr class="hr-horizontal" /></li>

      <!-- Graphs – visible to all authenticated users -->
      <side-menu title="Graphs" :static-item="true"></side-menu>
      <side-menu
        title="Top Researchers"
        icon="table"
        :route="{ to: 'default.topResearcher' }"
      ></side-menu>
      <side-menu
        title="Year Summary"
        icon="wallet"
        :route="{ to: 'default.YearSummary' }"
      ></side-menu>

      <!-- Faculty dropdown – visible to all -->
      <side-menu
        title="Faculty"
        icon="document"
        toggle-id="special-pages"
        :caret-icon="true"
        :route="{ popup: 'false', to: 'special-pages' }"
        @onClick="toggle"
        :active="currentRoute.includes('special-pages')"
      >
        <b-collapse
          tag="ul"
          class="sub-nav"
          id="special-pages"
          accordion="sidebar-menu"
          :visible="currentRoute.includes('special-pages')"
        >
          <!-- <side-menu
            title="Faculty Components"
            icon="brief-case"
            :route="{ to: 'default.facultySummary' }"
          ></side-menu> -->
          <side-menu
            title="Faculty Summary"
            icon="document"
            :route="{ to: 'default.facultySummaryTable' }"
          ></side-menu>
          <side-menu
            title="Aggregate Summary"
            icon="offer"
            :route="{ to: 'default.facultyAggregation' }"
          ></side-menu>
        </b-collapse>
      </side-menu>

      <side-menu
        title="Grade Summary"
        icon="wallet"
        :route="{ to: 'default.GradeSummary' }"
      ></side-menu>

      <li><hr class="hr-horizontal" /></li>

      <!-- User Management – visible to admin & super_admin only -->
      <side-menu
        v-if="authStore.hasRole(['super_admin'])"
        title="User Management"
        :static-item="true"
      ></side-menu>
      <!-- <side-menu v-if="authStore.hasRole(['admin', 'super_admin'])" title="Authentication" icon="shield-check" toggle-id="auth-skins" :caret-icon="true" :route="{ popup: 'false', to: 'auth' }" @onClick="toggle" :active="currentRoute.includes('auth')">
        <b-collapse tag="ul" class="sub-nav" id="auth-skins" accordion="sidebar-menu" :visible="currentRoute.includes('auth')">
          <side-menu isTag="router-link" title="Login" icon="circle" :icon-size="10" icon-type="solid" miniTitle="L" :route="{ to: 'auth.login' }"></side-menu>
          <side-menu isTag="router-link" title="Register" icon="circle" :icon-size="10" icon-type="solid" miniTitle="R" :route="{ to: 'auth.register' }"></side-menu>
          <side-menu isTag="router-link" title="Confirm Mail" icon="circle" :icon-size="10" icon-type="solid" miniTitle="CM" :route="{ to: 'auth.varify-email' }"></side-menu>
          <side-menu isTag="router-link" title="Lock Screen" icon="circle" :icon-size="10" icon-type="solid" miniTitle="LS" :route="{ to: 'auth.lock-screen' }"></side-menu>
          <side-menu isTag="router-link" title="Recover Password" icon="circle" :icon-size="10" icon-type="solid" miniTitle="RP" :route="{ to: 'auth.reset-password' }"></side-menu>
        </b-collapse>
      </side-menu> -->
      <side-menu
        v-if="authStore.hasRole(['super_admin'])"
        title="Users"
        icon="user-group"
        toggle-id="users"
        :caret-icon="true"
        :route="{ popup: 'false', to: 'user' }"
        @onClick="toggle"
        :active="currentRoute.includes('user')"
      >
        <b-collapse
          tag="ul"
          class="sub-nav"
          id="users"
          accordion="sidebar-menu"
          :visible="currentRoute.includes('user')"
        >
          <side-menu
            isTag="router-link"
            title="User Profile"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="UP"
            :route="{ to: 'default.user-profile' }"
          ></side-menu>
          <!-- <side-menu
            isTag="router-link"
            title="User Add"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="UA"
            :route="{ to: 'default.user-add' }"
          ></side-menu> -->
          <side-menu
            isTag="router-link"
            title="User List"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="UL"
            :route="{ to: 'default.user-list' }"
          ></side-menu>
        </b-collapse>
      </side-menu>
    </ul>
  </default-sidebar>
  <!-- Sidebar Component End Here-->
</template>

<script setup>
import DefaultSidebar from '@/components/custom/sidebar/DefaultSidebar.vue'
import SideMenu from '@/components/custom/nav/SideMenu.vue'
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const route = useRoute()
const currentRoute = ref('')

// Toggle logic for collapsible menus
const toggle = (menu) => {
  if (menu === currentRoute.value && menu.includes('.')) {
    const parts = currentRoute.value.split('.')
    currentRoute.value = parts[parts.length - 2]
  } else if (menu !== currentRoute.value && currentRoute.value.includes(menu)) {
    currentRoute.value = ''
  } else if (menu !== currentRoute.value) {
    currentRoute.value = menu
  } else if (menu === currentRoute.value) {
    currentRoute.value = ''
  } else {
    currentRoute.value = ''
  }
}

// Initialize currentRoute based on the current route name
watch(
  () => route.name,
  (newName) => {
    if (newName) toggle(newName)
  },
  { immediate: true }
)
</script>

<style scoped>
/* Add any custom styles if needed */
</style>