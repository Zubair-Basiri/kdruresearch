<template>
  <section class="login-content">
    <div class="login-wrapper">
      <b-card class="border-0 shadow auth-card" body-class="p-4 p-lg-5" no-body>
        <!-- Logo and header -->
        <div class="text-center mb-4 mt-3">
          <router-link :to="{ name: 'default.dashboard' }" class="navbar-brand d-inline-flex align-items-center text-primary text-decoration-none">
            <brand-logo></brand-logo>
            <h4 class="logo-title ms-2 mb-0 fw-semibold">
              <brand-name></brand-name>
            </h4>
          </router-link>
          <h3 class="mt-4 mb-1 fw-bold heading-one-line" style="font-size: 22px; color: #2b66a1;">
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

          <div class="d-grid gap-2 mt-3">
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

          <!-- Forgot password (left) and Login as guest (right) — as buttons -->
          <div class="d-flex justify-content-between align-items-center mt-3 gap-2">
            <router-link :to="{ name: 'auth.forgot-password' }" class="btn btn-outline-primary btn-sm flex-fill">
              Forgot your password?
            </router-link>
            <a href="#" class="btn btn-outline-secondary btn-sm flex-fill" @click.prevent="guestLogin">
              Login as a guest
            </a>
          </div>
        </form>
      </b-card>
    </div>

    <!-- Copyright footer -->
    <footer class="page-footer">
      <p class="text-center footer-credit small mb-0">
        Conceptualized and designed by <strong>Dr. Rahmatullah Pashtoon (PhD)</strong>, embodying academic integrity,
        innovation, and a vision for empowering research and institutional excellence. ©
        {{ new Date().getFullYear() }} All rights reserved.
      </p>
    </footer>
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
    await new Promise(resolve => setTimeout(resolve, 100));
    const user = authStore.user;
    if (user?.university_id) {
      router.push({ name: 'default.dashboard' });
    } else {
      router.push({ name: 'university-selector' });
    }
  } catch (err) {
    console.error('Guest login error:', err);
    error.value = authStore.error || 'Guest login failed. Please try again.';
  }
}
</script>

<style lang="scss" scoped>
/* ===== Page wrapper ===== */
.login-content {
  position: relative;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem 8rem;    /* extra bottom padding so footer sits higher */
  background: linear-gradient(135deg, #eef2ff 0%, #e0f2fe 50%, #f0f9ff 100%);
}

/* ===== Card wrapper ===== */
.login-wrapper {
  width: 100%;
  max-width: 640px;
  margin: auto;
}

/* ===== Card — light background + extra bottom padding ===== */
.auth-card {
  background: #f5f8fc;        /* light bluish background instead of pure white */
  backdrop-filter: blur(12px);
  border-radius: 1.25rem;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08) !important;
  padding-bottom: 1.25rem;    /* extra space inside so buttons don't touch the border */
}

/* ===== Heading on one line ===== */
.heading-one-line {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* ===== Inputs ===== */
.input-group-text {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
  color: #6c757d;
}

.form-control {
  border-left: none;
  padding-left: 0;
  padding-top: 0.7rem;
  padding-bottom: 0.7rem;
  font-size: 1rem;
  &:focus {
    border-color: #dee2e6;
    box-shadow: none;
    border-left: none;
  }
}

/* ===== Primary button ===== */
.btn-primary {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
  border: none;
  transition: all 0.3s ease;
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
  }
}

/* ===== Outline buttons (Forgot / Guest) ===== */
.btn-outline-primary,
.btn-outline-secondary {
  padding: 0.55rem 0.75rem;
  font-weight: 500;
  border-radius: 0.5rem;
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

.btn-outline-primary {
  color: #0d6efd;
  border-color: #0d6efd;
  &:hover {
    background: #0d6efd;
    color: #fff;
  }
}

.btn-outline-secondary {
  color: #495057;
  border-color: #adb5bd;
  &:hover {
    background: #6c757d;
    color: #fff;
  }
}

/* ===== Copyright footer — sits higher now ===== */
.page-footer {
  position: absolute;
  bottom: 2rem;              /* pushed up from the bottom edge */
  left: 0;
  right: 0;
  padding: 1rem;
  text-align: center;
}

.footer-credit {
  color: #2a1ff0;
  font-weight: 600;
  letter-spacing: 0.3px;
  strong {
    color: #0dfd41;
    font-weight: 600;
  }
}

/* ===== Responsive ===== */
@media (max-width: 768px) {
  .login-content {
    padding: 1rem 0.75rem 6rem;
  }

  .login-wrapper {
    max-width: 100%;
  }

  .auth-card {
    border-radius: 1rem;
    padding-bottom: 1rem;
  }

  .heading-one-line {
    white-space: normal;
    font-size: 18px !important;
  }

  /* Stack the two buttons on very small screens */
  .d-flex.justify-content-between {
    flex-direction: column;
  }
}
</style>