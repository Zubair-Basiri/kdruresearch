<template>
  <default-sidebar>
    <ul class="navbar-nav iq-main-menu" id="sidebar-menu">
      <!-- Home – hidden for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin"
        title="Home"
        :static-item="true"
      ></side-menu>

      <!-- Dashboard – hidden for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin"
        isTag="router-link"
        title="Dashboard"
        icon="view-grid"
        :route="{ to: 'default.dashboard' }"
      ></side-menu>

      <!-- Add Section – hidden for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin && !authStore.isGuest"
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
          <side-menu
            v-if="authStore.canManageUniversityData"
            title="University"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="U"
            :route="{ to: 'universityList' }"
          ></side-menu>
          <side-menu
            v-if="authStore.canManageUniversityData"
            title="Faculty"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="F"
            :route="{ to: 'facultyTable' }"
          ></side-menu>
          <side-menu
            v-if="authStore.canManageUniversityData"
            title="Department"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="D"
            :route="{ to: 'departmentTable' }"
          ></side-menu>
          <side-menu
            v-if="authStore.canManageUniversityData"
            title="Lecturers"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="L"
            :route="{ to: 'teachers' }"
          ></side-menu>
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

      <!-- Lecturer Profiles – visible to the new role -->
      <side-menu
        v-if="authStore.hasRole(['super_admin', 'admin_admin', 'lecturer_profile_admin'])"
        title="Lecturer Profiles"
        icon="user-group"
        :route="{ to: 'lecturerProfiles' }"
      ></side-menu>

      <!-- Key Findings – hidden for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin && authStore.canManageUniversityData"
        title="Key Findings"
        icon="table"
        :route="{ to: 'default.keyFindings' }"
      ></side-menu>

      <li><hr class="hr-horizontal" /></li>

      <!-- Graphs section – hidden for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin"
        title="Graphs"
        :static-item="true"
      ></side-menu>
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin"
        title="Top Researchers"
        icon="table"
        :route="{ to: 'default.topResearcher' }"
      ></side-menu>
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin"
        title="Year Summary"
        icon="wallet"
        :route="{ to: 'default.YearSummary' }"
      ></side-menu>

      <!-- Faculty dropdown – hidden for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin"
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

      <!-- Grade Summary – hidden for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin"
        title="Grade Summary"
        icon="wallet"
        :route="{ to: 'default.GradeSummary' }"
      ></side-menu>

      <li><hr class="hr-horizontal" /></li>

      <!-- User Management – already admin-only, but also hide for lecturer_profile_admin -->
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin && authStore.canManageUsers"
        title="User Management"
        :static-item="true"
      ></side-menu>
      <side-menu
        v-if="!authStore.isLecturerProfileAdmin && authStore.canManageUsers"
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
            v-if="authStore.isAuthenticated"
            isTag="router-link"
            title="User Profile"
            icon="circle"
            :icon-size="10"
            icon-type="solid"
            miniTitle="UP"
            :route="{ to: 'default.user-profile' }"
          ></side-menu>
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