<template>
  <section class="login-content">
    <b-row class="m-0 align-items-stretch min-vh-100">
      <b-col md="12" class="d-flex align-items-center bg-white p-5">
        <b-row class="justify-content-center w-100">
          <b-col md="10" lg="8" xl="7">
            <b-card class="border-0 shadow auth-card" body-class="p-4 p-lg-5" no-body>
              <!-- Logo and header -->
              <div class="text-center mb-4">
                <router-link :to="{ name: 'default.dashboard' }" class="navbar-brand d-inline-flex align-items-center text-primary text-decoration-none">
                  <brand-logo></brand-logo>
                  <h4 class="logo-title ms-2 mb-0 fw-semibold">
                    <brand-name></brand-name>
                  </h4>
                </router-link>
                <h3 class="mt-4 mb-1 fw-bold" style="font-size: 22px; color: #2b66a1;">
                  Welcome To Research Database System
                </h3>
              </div>

              <!-- Login form -->
              <form @submit.prevent="handleLogin">
                <div class="mb-4">
                  <label for="email" class="form-label fw-medium text-secondary">Email address</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email"
                           v-model="form.email" placeholder="name@xyz.abc" required />
                  </div>
                </div>

                <div class="mb-4">
                  <label for="password" class="form-label fw-medium text-secondary">Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password"
                           v-model="form.password" placeholder="••••••••" required />
                  </div>
                </div>

                <!-- Error message -->
                <div v-if="error" class="alert alert-danger py-2" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  {{ error }}
                </div>

                <div class="d-grid gap-2 mt-5">
                  <button type="submit" class="btn btn-primary py-3 fw-semibold rounded-3"
                          :disabled="authStore.loading">
                    <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2"
                          role="status" aria-hidden="true"></span>
                    {{ authStore.loading ? 'Signing in...' : 'Sign In' }}
                  </button>
                </div>

                <!-- Register link -->
                <p class="mt-4 text-center text-muted">
                  Don't have an account?
                  <router-link :to="{ name: 'auth.user-register' }" class="text-primary fw-medium text-decoration-none">
                    Register here
                  </router-link>
                </p>

                <p class="mt-2 text-center">
                  <a href="#" class="text-primary fw-medium text-decoration-none" @click.prevent="guestLogin">
                    Or login as a guest
                  </a>
                </p>

                <!-- Designed by footer -->
                <hr class="my-4" />
                <p class="text-center footer-credit small">
                  Conceptualized and designed by <strong>Dr. Rahmatullah Pashtoon (PhD)</strong>, embodying academic integrity,
                  innovation, and a vision for empowering research and institutional excellence. ©
                  {{ new Date().getFullYear() }}
                </p>
              </form>
            </b-card>
          </b-col>
        </b-row>
      </b-col>
    </b-row>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
  email: '',
  password: '',
});
const error = ref(null);

async function handleLogin() {
  error.value = null;
  try {
    await authStore.login(form.value);
    router.push({ name: 'default.dashboard' });
  } catch (err) {
    error.value = authStore.error || 'Login failed. Please check your credentials.';
  }
}

async function guestLogin() {
  error.value = null;
  try {
    await authStore.guestLogin();
    // Small delay to ensure session is fully set
    await new Promise(resolve => setTimeout(resolve, 100));
    const user = authStore.user;
    if (user?.university_id) {
      router.push({ name: 'default.dashboard' });
    } else {
      router.push({ name: 'university-selector' });
    }
  } catch (err) {
    // Log the actual error for debugging
    console.error('Guest login error:', err);
    error.value = authStore.error || 'Guest login failed. Please try again.';
  }
}
</script>

<style lang="scss" scoped>
.login-content {
  position: relative;
  min-height: 100vh;
  background: url('@/assets/images/auth-pro/04.jpg') no-repeat center center fixed;
  background-size: cover;
  align-items: center;
  padding: 2rem;
}

.auth-card {
  max-width: 650px; /* Changed from 420px to 600px */
  margin: 0 auto;
  background: transparent;
}

.input-group-text {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  color: #6c757d;
}

.form-control {
  border-left: none;
  padding-left: 0;
  &:focus {
    border-color: #dee2e6;
    box-shadow: none;
    border-left: none;
  }
}

.btn-primary {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
  border: none;
  transition: all 0.3s ease;
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
  }
}

/* Styled footer credit */
.footer-credit {
  color: #f0921f; /* A dark gray-blue for better visibility */
  font-weight: 400;
  letter-spacing: 0.3px;
  strong {
    color: #0dfd41; /* Primary blue for emphasis */
    font-weight: 600;
  }
}
</style>