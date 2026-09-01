<template>
  <nav :class="['nav', 'navbar', 'navbar-expand-xl', 'navbar-light', 'iq-navbar', headerNavbar]">
    <div class="container-fluid navbar-inner">
      <slot></slot>
      <div class="d-flex align-items-center gap-2">
        <RouterLink
          v-if="authStore.isAuthenticated && !authStore.isGuest"
          to="/dashboard/academicJournals/form?submission=true"
          class="btn btn-add btn-sm d-flex gap-2 align-items-center"
        >
          <i class="bi bi-plus-circle"></i> New Submission
        </RouterLink>
      </div>
      <button
        class="navbar-toggler"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >
        <span class="navbar-toggler-icon">
          <span class="mt-2 navbar-toggler-bar bar1"></span>
          <span class="navbar-toggler-bar bar2"></span>
          <span class="navbar-toggler-bar bar3"></span>
        </span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="mb-2 navbar-nav ms-auto align-items-center navbar-list mb-lg-0">
          <!-- <li class="me-0 me-xl-2" v-if="isGoPro">
            <a
              class="btn btn-primary btn-sm d-flex gap-2 align-items-center"
              href="http://hopeui.iqonic.design/pro?utm_source=hopeui-free-demo&utm_medium=hopeui-free-demo&utm_campaign=hopeui-pro-launch"
              target="_blank"
            >
              <icon-component type="outlined" :size="16" icon-name="location-arrow"></icon-component>
              Go Pro
            </a>
          </li> -->
          <li class="nav-item dropdown">
            <a
              class="nav-link py-0 d-flex align-items-center"
              href="#"
              id="navbarDropdown"
              role="button"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <img
                src="@/assets/images/avatars/avatar.jpg"
                alt="User-Profile"
                class="theme-color-default-img img-fluid avatar avatar-50 avatar-rounded"
              />
              <img
                src="@/assets/images/avatars/avatar.jpg"
                alt="User-Profile"
                class="theme-color-purple-img img-fluid avatar avatar-50 avatar-rounded"
              />
              <img
                src="@/assets/images/avatars/avatar.jpg"
                alt="User-Profile"
                class="theme-color-blue-img img-fluid avatar avatar-50 avatar-rounded"
              />
              <img
                src="@/assets/images/avatars/avatar.jpg"
                alt="User-Profile"
                class="theme-color-green-img img-fluid avatar avatar-50 avatar-rounded"
              />
              <img
                src="@/assets/images/avatars/avatar.jpg"
                alt="User-Profile"
                class="theme-color-yellow-img img-fluid avatar avatar-50 avatar-rounded"
              />
              <img
                src="@/assets/images/avatars/avatar.jpg"
                alt="User-Profile"
                class="theme-color-pink-img img-fluid avatar avatar-50 avatar-rounded"
              />
              <div class="caption ms-3 d-none d-md-block">
                <h6 class="mb-0 caption-title">{{ user?.name || 'User' }}</h6>
                <p class="mb-0 caption-sub-title">{{ userRole }}</p>
              </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
              <li>
                <router-link class="dropdown-item" :to="{ name: 'default.user-profile' }">
                  Profile
                </router-link>
              </li>
              <li><hr class="dropdown-divider" /></li>
              <li>
                <a class="dropdown-item" href="#" @click.prevent="logout">Logout</a>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup>
/* eslint-disable no-undef */
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { useSettingStore } from '@/stores/setting'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

defineProps({
  isGoPro: { type: Boolean, default: false },
  isSearch: { type: Boolean, default: true }
})

const settingStore = useSettingStore()
const authStore = useAuthStore()
const router = useRouter()

// Local reactive ref that syncs with the store
const headerNavbar = ref(settingStore.headerNavbar)

// Watch store changes and update local ref
watch(() => settingStore.headerNavbar, (newVal) => {
  headerNavbar.value = newVal
})

const user = computed(() => authStore.user)
const userRole = computed(() => {
  if (authStore.isMinistryAuthority) return 'Ministry Authority'
  if (authStore.isSuperAdmin) return 'Super Admin'
  if (authStore.isAdmin) return 'Admin'
  if (authStore.isUser) return 'User'
  return 'Guest'
})

const logout = async () => {
  await authStore.logout()
  router.push({ name: 'auth.login' })
}

const onscroll = () => {
  const yOffset = document.documentElement.scrollTop
  const navbar = document.querySelector('.navs-sticky')
  if (navbar) {
    if (yOffset >= 100) {
      navbar.classList.add('menu-sticky')
    } else {
      navbar.classList.remove('menu-sticky')
    }
  }
}

onMounted(() => {
  window.addEventListener('scroll', onscroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', onscroll)
})
</script>

<style scoped>
/* Add styles for the button if needed */
.btn-add {
  background: linear-gradient(135deg, #1971c2, #4dabf7);
  color: #fff;
  border: none;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-add:hover {
  background: linear-gradient(135deg, #0f5a9e, #3a8cdb);
  color: #fff;
}
</style>