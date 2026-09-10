<template>
  <section class="login-content">
    <b-row class="m-0 align-items-stretch min-vh-100">
      <b-col md="12" class="d-flex align-items-center bg-white p-5">
        <b-row class="justify-content-center w-100">
          <b-col md="10" lg="8" xl="7">
            <b-card class="border-0 shadow auth-card" body-class="p-4 p-lg-5" no-body>
              <div class="text-center mb-4">
                <h3 class="mt-4 mb-1 fw-bold" style="font-size: 22px; color: #2b66a1;">
                  Reset Password
                </h3>
                <p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>
              </div>

              <form @submit.prevent="sendResetLink">
                <div class="mb-4">
                  <label for="email" class="form-label fw-medium text-secondary">Email address</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email"
                           v-model="email" placeholder="name@xyz.abc" required />
                  </div>
                </div>

                <div v-if="message" class="alert alert-success py-2" role="alert">
                  <i class="bi bi-check-circle-fill me-2"></i>
                  {{ message }}
                </div>
                <div v-if="error" class="alert alert-danger py-2" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  {{ error }}
                </div>

                <div class="d-grid gap-2 mt-4">
                  <button type="submit" class="btn btn-primary py-3 fw-semibold rounded-3" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    {{ loading ? 'Sending...' : 'Send Reset Link' }}
                  </button>
                </div>

                <p class="mt-3 text-center">
                  <router-link :to="{ name: 'auth.login' }" class="text-primary text-decoration-none">
                    ← Back to Login
                  </router-link>
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
import api from '@/services/api';

const email = ref('');
const message = ref('');
const error = ref('');
const loading = ref(false);

async function sendResetLink() {
  message.value = '';
  error.value = '';
  loading.value = true;
  try {
    const response = await api.post('/forgot-password', { email: email.value });
    message.value = response.data.message || 'Password reset link sent to your email.';
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to send reset link.';
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
/* reuse styles from Login.vue */
.login-content {
  position: relative;
  min-height: 100vh;
  background: url('@/assets/images/auth-pro/04.jpg') no-repeat center center fixed;
  background-size: cover;
  align-items: center;
  padding: 2rem;
}
.auth-card {
  max-width: 650px;
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
}
.form-control:focus {
  border-color: #dee2e6;
  box-shadow: none;
  border-left: none;
}
.btn-primary {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
  border: none;
  transition: all 0.3s ease;
}
.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(13, 110, 253, 0.3);
}
</style>