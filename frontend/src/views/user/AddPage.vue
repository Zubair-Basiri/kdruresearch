<template>
  <section>
    <div class="container h-100 d-flex align-items-center justify-content-center">
      <div class="row justify-content-center">
        <div class="col-md-12">
          <div class="card auth-card shadow-lg border-0 animate__animated animate__fadeInUp">
            <div class="card-body p-4 p-lg-3">
              <!-- Header -->
              <div class="text-center mb-4 mt-4">
                <h2 class="mb-1" style="font-size: 28px; font-weight: bold;">
                  {{ isEditMode ? 'Edit User' : 'Register New User' }}
                </h2>
                <p class="text-muted">{{ isEditMode ? 'Update user information.' : 'Create a new user account.' }}</p>
              </div>

              <!-- Form -->
              <form @submit.prevent="handleSubmit">
                <div class="row g-3">

                  <!-- Role Selection -->
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="role" class="form-label">Account Type</label>
                      <select class="form-control form-control-lg" id="role" v-model="form.role" required>
                        <option value="user">Regular User (Lecturer)</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                      </select>
                    </div>
                  </div>

                  <!-- Lecturer Dropdown – only for role 'user' -->
                  <div v-if="form.role === 'user'" class="col-lg-6">
                    <div class="form-group">
                      <label for="lecturer" class="form-label">Link to Lecturer (Select from list)</label>
                      <multiselect
                        v-model="selectedLecturer"
                        :options="lecturerOptions"
                        :searchable="true"
                        :close-on-select="true"
                        :show-labels="false"
                        placeholder="Search and select a lecturer..."
                        label="label"
                        track-by="id"
                        @select="onLecturerSelected"
                        class="multiselect-custom"
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
                      <small class="text-muted">The user's name will be taken from the selected lecturer.</small>
                    </div>
                  </div>

                  <!-- Name fields – visible only for admin / super_admin -->
                  <template v-if="form.role !== 'user'">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="first-name" class="form-label">First Name</label>
                        <input type="text" class="form-control form-control-lg" id="first-name"
                               v-model="form.first_name" placeholder="Enter first name" required />
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label for="last-name" class="form-label">Last Name</label>
                        <input type="text" class="form-control form-control-lg" id="last-name"
                               v-model="form.last_name" placeholder="Enter last name" required />
                      </div>
                    </div>
                  </template>

                  <!-- Email – always visible -->
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" class="form-control form-control-lg" id="email"
                             v-model="form.email" placeholder="Enter email address" required />
                    </div>
                  </div>

                  <!-- Password Fields – always visible -->
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="password" class="form-label">
                        Password {{ isEditMode ? '(leave blank to keep current)' : '' }}
                      </label>
                      <input type="password" class="form-control form-control-lg" id="password"
                             v-model="form.password" :placeholder="isEditMode ? '••••••••' : 'Enter password'"
                             :required="!isEditMode" />
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="confirm-password" class="form-label">
                        Confirm Password
                      </label>
                      <input type="password" class="form-control form-control-lg" id="confirm-password"
                             v-model="form.password_confirmation" placeholder="Confirm password"
                             :required="!isEditMode" />
                    </div>
                  </div>
                </div>

                <!-- Error Messages -->
                <div v-if="error" class="alert alert-danger mt-4" role="alert">
                  {{ error }}
                </div>
                <div v-if="validationErrors.length" class="alert alert-danger mt-4">
                  <ul class="mb-0">
                    <li v-for="(msg, idx) in validationErrors" :key="idx">{{ msg }}</li>
                  </ul>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-center gap-3 mt-4 mb-3">
                  <router-link :to="{ name: 'default.user-list' }" class="btn btn-outline-secondary px-4">
                    Cancel
                  </router-link>
                  <button type="submit" class="btn btn-primary px-5" :disabled="loading">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
                    {{ isEditMode ? 'Update User' : 'Create User' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Multiselect from 'vue-multiselect';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();

// Mode
const isEditMode = computed(() => !!route.params.id);

// Form data
const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  role: 'user',
  password: '',
  password_confirmation: '',
  lecturer_id: null,
});

// UI state
const loading = ref(false);
const error = ref(null);
const validationErrors = ref([]);
const lecturers = ref([]);
const selectedLecturer = ref(null);

// Prepare lecturer options for multiselect
const lecturerOptions = computed(() => 
  lecturers.value.map(lect => ({
    id: lect.id,
    label: lect.lecturername,
    faculty: lect.faculty?.facultyname || 'No Faculty',
    fullName: lect.lecturername,
  }))
);

// When a lecturer is selected, auto‑fill the user's name (for role user)
const onLecturerSelected = (lecturer) => {
  if (lecturer && form.value.role === 'user') {
    // No need to store first/last separately – we'll use the lecturer's name as full name.
    // We keep first_name/last_name empty, and during submit we'll use the lecturer's name.
  }
};

// Fetch lecturers list
const fetchLecturers = async () => {
  try {
    const res = await api.get('/lecturers-for-dropdown');
    lecturers.value = res.data.data || [];
  } catch (err) {
    console.error('Failed to fetch lecturers', err);
  }
};

// Load user data for editing
const loadUserData = async () => {
  if (!isEditMode.value) return;
  try {
    loading.value = true;
    const response = await api.get(`/users/${route.params.id}`);
    const user = response.data;
    
    // For admin/super_admin roles, split the name
    if (user.role !== 'user') {
      const nameParts = user.name.split(' ');
      form.value.first_name = nameParts[0] || '';
      form.value.last_name = nameParts.slice(1).join(' ') || '';
    } else {
      // For user role, we don't have first/last name fields – clear them
      form.value.first_name = '';
      form.value.last_name = '';
    }
    
    form.value.email = user.email;
    form.value.role = user.role;
    
    // If user is a lecturer, pre-select their linked lecturer
    if (user.role === 'user' && user.lecturer_id) {
      const linkedLecturer = lecturers.value.find(l => l.id === user.lecturer_id);
      if (linkedLecturer) {
        selectedLecturer.value = {
          id: linkedLecturer.id,
          label: linkedLecturer.lecturername,
          faculty: linkedLecturer.faculty?.facultyname,
        };
        form.value.lecturer_id = linkedLecturer.id;
      }
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load user.';
  } finally {
    loading.value = false;
  }
};

// Submit handler
async function handleSubmit() {
  error.value = null;
  validationErrors.value = [];

  // Validate password
  if (!isEditMode.value || (form.value.password || form.value.password_confirmation)) {
    if (form.value.password !== form.value.password_confirmation) {
      validationErrors.value.push('Passwords do not match.');
      return;
    }
    if (form.value.password && form.value.password.length < 8) {
      validationErrors.value.push('Password must be at least 8 characters.');
      return;
    }
  }

  // Build user data payload
  let userData = {
    email: form.value.email,
    role: form.value.role,
  };

  // For admin/super_admin: use first_name + last_name
  if (form.value.role !== 'user') {
    if (!form.value.first_name || !form.value.last_name) {
      validationErrors.value.push('First name and last name are required for admin/super admin.');
      return;
    }
    userData.name = `${form.value.first_name} ${form.value.last_name}`.trim();
  } 
  // For user role: name must come from selected lecturer
  else {
    if (!selectedLecturer.value) {
      validationErrors.value.push('Please select a lecturer for this user account.');
      return;
    }
    // Use the lecturer's full name
    userData.name = selectedLecturer.value.label;
    userData.lecturer_id = selectedLecturer.value.id;
  }

  // Add password if provided
  if (form.value.password) {
    userData.password = form.value.password;
    userData.password_confirmation = form.value.password_confirmation;
  }

  try {
    loading.value = true;
    if (!isEditMode.value) {
      await api.post('/users', userData);
      alert('Registration successful. Please wait for admin approval before logging in.');
      router.push({ name: 'auth.login' });
    } else {
      await api.put(`/users/${route.params.id}`, userData);
      alert('User updated successfully.');
      router.push({ name: 'default.user-list' });
    }
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors;
      validationErrors.value = Object.values(errors).flat();
    } else {
      error.value = err.response?.data?.message || 'Operation failed.';
    }
  } finally {
    loading.value = false;
  }
}

// Reset lecturer selection when role changes from user to something else
watch(() => form.value.role, (newRole) => {
  if (newRole !== 'user') {
    selectedLecturer.value = null;
    form.value.lecturer_id = null;
  } else {
    // When switching to user, clear any name fields (they are hidden)
    form.value.first_name = '';
    form.value.last_name = '';
  }
});

onMounted(async () => {
  await fetchLecturers();
  await loadUserData();
});
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style lang="scss" scoped>
/* Your existing styles (same as before) – keep the same */
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
    background: rgba(0, 0, 0, 0.4);
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

.multiselect-custom {
  .multiselect__tags {
    border-radius: 0.5rem;
    border: 1px solid #e0e0e0;
    padding: 6px 40px 0 8px;
  }
  .multiselect__input, .multiselect__single {
    font-size: 1rem;
  }
  .multiselect__placeholder {
    color: #6c757d;
    font-size: 1rem;
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

@media (max-width: 768px) {
  .auth-card {
    margin: 1rem;
  }
}
</style>