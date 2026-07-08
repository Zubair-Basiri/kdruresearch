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
                  Create Your Account
                </h3>
                <p class="text-muted">Registration is subject to admin approval.</p>
              </div>

              <!-- Registration form -->
              <form @submit.prevent="handleRegister">
                <!-- Lecturer Dropdown -->
                <div class="mb-4">
                  <label for="lecturer" class="form-label fw-medium text-secondary">Select Your Name</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                      <i class="bi bi-person"></i>
                    </span>
                    <multiselect
                      v-model="selectedLecturer"
                      :options="lecturerOptions"
                      :searchable="true"
                      :close-on-select="true"
                      :show-labels="false"
                      placeholder="Search for your name..."
                      label="label"
                      track-by="id"
                      class="multiselect-custom w-100"
                    >
                      <template #option="{ option }">
                        <strong>{{ option.label }}</strong>
                        <span v-if="option.faculty" class="text-muted ms-2">({{ option.faculty }})</span>
                      </template>
                      <template #singleLabel="{ option }">
                        <strong>{{ option.label }}</strong>
                        <span v-if="option.faculty" class="text-muted ms-2">({{ option.faculty }})</span>
                      </template>
                    </multiselect>
                  </div>
                  <small class="text-muted">Please select your name from the list of registered lecturers. If your name does not appear in the list, please submit your details (Full Name, Email, Phone Number, Faculty, Department) by emailing vicechancellor@kdru.edu.af or by contacting +93 771 260 003.</small>
                </div>

                <!-- Email -->
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

                <!-- Password -->
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

                <!-- Confirm Password -->
                <div class="mb-4">
                  <label for="password_confirmation" class="form-label fw-medium text-secondary">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password_confirmation"
                           v-model="form.password_confirmation" placeholder="••••••••" required />
                  </div>
                </div>

                <!-- Error messages -->
                <div v-if="error" class="alert alert-danger py-2" role="alert">
                  <i class="bi bi-exclamation-triangle-fill me-2"></i>
                  {{ error }}
                </div>
                <div v-if="validationErrors.length" class="alert alert-danger py-2">
                  <ul class="mb-0">
                    <li v-for="(msg, idx) in validationErrors" :key="idx">{{ msg }}</li>
                  </ul>
                </div>

                <!-- Submit -->
                <div class="d-grid gap-2 mt-5">
                  <button type="submit" class="btn btn-primary py-3 fw-semibold rounded-3"
                          :disabled="loading || !selectedLecturer">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2"
                          role="status" aria-hidden="true"></span>
                    {{ loading ? 'Registering...' : 'Register' }}
                  </button>
                </div>

                <!-- Login link -->
                <p class="mt-4 text-center text-muted">
                  Already have an account?
                  <router-link :to="{ name: 'auth.login' }" class="text-primary fw-medium text-decoration-none">
                    Sign In
                  </router-link>
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
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Multiselect from 'vue-multiselect';
import api from '@/services/api';

const router = useRouter();

const form = ref({
  email: '',
  password: '',
  password_confirmation: '',
});

const loading = ref(false);
const error = ref(null);
const validationErrors = ref([]);
const lecturers = ref([]);
const selectedLecturer = ref(null);

const lecturerOptions = computed(() => 
  lecturers.value.map(lect => ({
    id: lect.id,
    label: lect.lecturername,
    faculty: lect.faculty?.facultyname || 'No Faculty',
  }))
);

const fetchLecturers = async () => {
  try {
    const res = await api.get('/lecturers-for-dropdown');
    lecturers.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch lecturers', err);
  }
};

async function handleRegister() {
  error.value = null;
  validationErrors.value = [];

  if (!selectedLecturer.value) {
    validationErrors.value.push('Please select your name from the list.');
    return;
  }

  if (form.value.password !== form.value.password_confirmation) {
    validationErrors.value.push('Passwords do not match.');
    return;
  }
  if (form.value.password.length < 8) {
    validationErrors.value.push('Password must be at least 8 characters.');
    return;
  }

  try {
    loading.value = true;
    await api.post('/users', {
      name: selectedLecturer.value.label,
      email: form.value.email,
      password: form.value.password,
      password_confirmation: form.value.password_confirmation,
      role: 'user',
      lecturer_id: selectedLecturer.value.id,
    });
    alert('Registration successful. Please wait for admin approval.');
    router.push({ name: 'auth.login' });
  } catch (err) {
    if (err.response?.data?.errors?.email) {
      // Email already taken – show a custom alert
      alert('This email is already registered. Please use a different email or sign in.');
      validationErrors.value = ['Email already taken.'];
    } else if (err.response?.data?.errors) {
      const errors = err.response.data.errors;
      validationErrors.value = Object.values(errors).flat();
    } else {
      error.value = err.response?.data?.message || 'Registration failed. Please try again.';
    }
  } finally {
    loading.value = false;
  }
}

onMounted(fetchLecturers);
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style lang="scss" scoped>
/* Same styles as SignIn.vue – copied below */
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
  &:focus {
    border-color: #dee2e6;
    box-shadow: none;
    border-left: none;
  }
}

.multiselect-custom {
  flex: 1;
  .multiselect__tags {
    border: 1px solid #ced4da;
    border-radius: 0 0.375rem 0.375rem 0;
    border-left: none;
    background: transparent;
    padding: 6px 40px 0 8px;
    min-height: 38px;
  }
  .multiselect__input, .multiselect__single {
    font-size: 1rem;
    color: #212529;
  }
  .multiselect__placeholder {
    color: #6c757d;
    font-size: 1rem;
  }
  .multiselect__tag {
    background: #0d6efd;
    color: white;
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

.footer-credit {
  color: #f0921f;
  font-weight: 400;
  letter-spacing: 0.3px;
  strong {
    color: #0dfd41;
    font-weight: 600;
  }
}

@media (max-width: 768px) {
  .auth-card {
    margin: 1rem;
  }
}
</style>