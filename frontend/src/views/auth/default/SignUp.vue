<template>
  <section class="login-content">
    <div class="background-overlay"></div>
    <div class="container h-100 d-flex align-items-center justify-content-center">
      <div class="row justify-content-center w-100">
        <div class="col-md-10 col-lg-8 col-xl-6">
          <div class="card auth-card shadow-lg border-0 animate__animated animate__fadeInUp">
            <div class="card-body p-4 p-lg-3">
              <!-- Logo and academic header -->
              <div class="text-center mb-4">
                <router-link :to="{ name: 'default.dashboard' }" class="navbar-brand d-flex align-items-center justify-content-center mb-3 text-primary">
                  <brand-logo></brand-logo>
                  <h4 class="logo-title ms-3 mb-0">
                    <brand-name></brand-name>
                  </h4>
                </router-link>
                <h2 class="mb-1" style="font-size: 28px; font-weight: bold;">Academic Registration</h2>
                <p class="text-muted">Create an account to access the dashboard.</p>
              </div>

              <!-- Registration form -->
              <form @submit.prevent="handleRegister">
                <div class="row g-3">
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="first-name" class="form-label">First Name</label>
                      <input type="text" class="form-control form-control-lg" id="first-name"
                             v-model="form.first_name" placeholder="Enter your name" required />
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="last-name" class="form-label">Last Name</label>
                      <input type="text" class="form-control form-control-lg" id="last-name"
                             v-model="form.last_name" placeholder="Enter your last name" required />
                    </div>
                  </div>
                  <div class="col-lg-12">
                    <div class="form-group">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" class="form-control form-control-lg" id="email"
                             v-model="form.email" placeholder="Enter your email address" required />
                    </div>
                  </div>
                  
                  <!-- Role Selection -->
                  <div class="col-lg-12">
                    <div class="form-group">
                      <label for="role" class="form-label">Account Type</label>
                      <select class="form-control form-control-lg" id="role" v-model="form.role" required>
                        <option value="user">Regular User</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                      </select>
                    </div>
                  </div>

                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="password" class="form-label">Password</label>
                      <input type="password" class="form-control form-control-lg" id="password"
                             v-model="form.password" placeholder="••••••••" required />
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="confirm-password" class="form-label">Confirm Password</label>
                      <input type="password" class="form-control form-control-lg" id="confirm-password"
                             v-model="form.password_confirmation" placeholder="••••••••" required />
                    </div>
                  </div>
                </div>

                <!-- Error display -->
                <div v-if="error" class="alert alert-danger mt-4" role="alert">
                  {{ error }}
                </div>
                <div v-if="validationErrors.length" class="alert alert-danger mt-4">
                  <ul class="mb-0">
                    <li v-for="(msg, idx) in validationErrors" :key="idx">{{ msg }}</li>
                  </ul>
                </div>

                <!-- Submit button -->
                <div class="d-flex justify-content-center mt-4">
                  <button type="submit" class="btn btn-primary btn-lg px-5" :disabled="authStore.loading">
                    <span v-if="authStore.loading" class="spinner-border spinner-border-sm me-2"
                          role="status" aria-hidden="true"></span>
                    {{ authStore.loading ? 'Creating account...' : 'Create Account' }}
                  </button>
                </div>

                <!-- Login link -->
                <p class="text-center mt-3">
                  Already have an account?
                  <router-link :to="{ name: 'auth.login' }" class="text-primary">Sign in</router-link>
                </p>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  role: 'user', // Default role
  password: '',
  password_confirmation: '',
});

const error = ref(null);
const validationErrors = ref([]);

async function handleRegister() {
  error.value = null;
  validationErrors.value = [];

  if (form.value.password !== form.value.password_confirmation) {
    validationErrors.value.push('Passwords do not match.');
    return;
  }

  try {
    const userData = {
      name: form.value.first_name + ' ' + form.value.last_name,
      email: form.value.email,
      role: form.value.role, // Include selected role
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
    };
    
    await authStore.register(userData);
    router.push({ name: 'default.dashboard' });
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors;
      validationErrors.value = Object.values(errors).flat();
    } else {
      error.value = err.response?.data?.message || 'Registration failed. Please try again.';
    }
  }
}
</script>

<style lang="scss" scoped>
.login-content {
  position: relative;
  min-height: 100vh;
  background: url('@/assets/images/auth-pro/04.jpg') no-repeat center center fixed;
  background-size: cover;
  display: flex;
  align-items: center;
  padding: 2rem;

  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.4); /* Dark overlay for better contrast */
    z-index: 1;
  }

  .container {
    position: relative;
    z-index: 2;
  }
}

.auth-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 1.5rem;
  transition: transform 0.3s ease;

  &:hover {
    transform: translateY(-5px);
  }
}

.form-group {
  margin-bottom: 1rem;
}

.form-control-lg {
  font-size: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid #e0e0e0;
  transition: border-color 0.2s, box-shadow 0.2s;

  &:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
  }
}

.btn-primary {
  background-color: #0d6efd;
  border: none;
  padding: 0.75rem 2.5rem;
  font-weight: 500;
  border-radius: 2rem;
  transition: all 0.2s;

  &:hover {
    background-color: #0b5ed7;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
  }

  &:disabled {
    background-color: #6c757d;
    transform: none;
    box-shadow: none;
  }
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .auth-card {
    margin: 1rem;
  }
}
</style>