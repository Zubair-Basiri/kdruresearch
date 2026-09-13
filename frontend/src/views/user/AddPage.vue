<template>
  <section>
    <div class="container h-100 d-flex align-items-center justify-content-center">
      <div class="row justify-content-center w-100">
        <div class="col-md-12">
          <div class="card auth-card shadow-lg border-0 animate__animated animate__fadeInUp">
            <div class="card-body p-4 p-lg-3">

              <!-- Header -->
              <div class="text-center mb-4 mt-4">
                <h2 class="mb-1" style="font-size: 28px; font-weight: bold;">
                  {{ isEditMode ? 'Edit User' : 'Register New User' }}
                </h2>
                <p class="text-muted">
                  {{ isEditMode ? 'Update user information.' : 'Create a new user account.' }}
                </p>
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
                        <option value="lecturer_profile_admin">Lecturer Profile Admin</option>
                        <option
                          v-if="authStore.user?.role === 'ministry_authority'"
                          value="ministry_authority"
                        >
                          Ministry Authority
                        </option>
                      </select>
                    </div>
                  </div>

                  <!-- University dropdown -->
                  <div v-if="showUniversityDropdown" class="col-lg-6">
                    <div class="form-group">
                      <label for="university" class="form-label">University</label>
                      <select
                        class="form-control form-control-lg"
                        id="university"
                        v-model="form.university_id"
                        :disabled="isUniversityLocked"
                        required
                      >
                        <option value="">Select University</option>
                        <option v-for="uni in availableUniversities" :key="uni.id" :value="uni.id">
                          {{ uni.name }}
                        </option>
                      </select>
                      <small v-if="isUniversityLocked" class="text-muted">
                        Locked to your assigned university.
                      </small>
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
                          <strong>{{ option.name }}</strong>
                          <span v-if="option.faculty" class="text-muted ms-2">({{ option.faculty }})</span>
                          <small v-if="option.university" class="text-muted d-block">{{ option.university }}</small>
                        </template>
                        <template #singleLabel="{ option }">
                          <strong>{{ option.name }}</strong>
                          <span v-if="option.faculty" class="text-muted ms-2">({{ option.faculty }})</span>
                        </template>
                      </multiselect>
                      <small class="text-muted">
                        The user's name and university will be taken from the selected lecturer.
                      </small>
                    </div>
                  </div>

                  <!-- Name fields – visible only when role is NOT 'user' -->
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

                  <!-- Email -->
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="email" class="form-label">Email</label>
                      <input type="email" class="form-control form-control-lg" id="email"
                             v-model="form.email" placeholder="Enter email address" required />
                    </div>
                  </div>

                  <!-- Password -->
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="password" class="form-label">
                        Password {{ isEditMode ? '(leave blank to keep current)' : '' }}
                      </label>
                      <input type="password" class="form-control form-control-lg" id="password"
                             v-model="form.password"
                             :placeholder="isEditMode ? '••••••••' : 'Enter password'"
                             :required="!isEditMode" />
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <div class="form-group">
                      <label for="confirm-password" class="form-label">Confirm Password</label>
                      <input type="password" class="form-control form-control-lg" id="confirm-password"
                             v-model="form.password_confirmation" placeholder="Confirm password"
                             :required="!isEditMode" />
                    </div>
                  </div>
                </div>

                <!-- Errors -->
                <div v-if="error" class="alert alert-danger mt-4" role="alert">{{ error }}</div>
                <div v-if="validationErrors.length" class="alert alert-danger mt-4">
                  <ul class="mb-0">
                    <li v-for="(msg, idx) in validationErrors" :key="idx">{{ msg }}</li>
                  </ul>
                </div>

                <!-- Actions -->
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
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

const isEditMode = computed(() => !!route.params.id);

// ---- Actor role helpers ----
// const isMinistryAuthority = computed(() => authStore.user?.role === 'ministry_authority');
const isSuperOrAdmin      = computed(() => ['super_admin', 'admin'].includes(authStore.user?.role));

// ---- Form data ----
const form = ref({
  first_name: '',
  last_name: '',
  email: '',
  role: 'user',
  password: '',
  password_confirmation: '',
  lecturer_id: null,
  university_id: null,
});

// ---- UI state ----
const loading = ref(false);
const error = ref(null);
const validationErrors = ref([]);
const lecturers = ref([]);
const selectedLecturer = ref(null);
const universities = ref([]);

// ---- University dropdown visibility & lock ----
const showUniversityDropdown = computed(() =>
  form.value.role !== 'user' && form.value.role !== 'ministry_authority'
);

const isUniversityLocked = computed(() => isSuperOrAdmin.value);

const availableUniversities = computed(() => {
  if (isSuperOrAdmin.value) {
    return universities.value.filter(u => u.id === authStore.user?.university_id);
  }
  return universities.value;
});

// ---- Fetch universities ----
const fetchUniversities = async () => {
  try {
    const res = await api.get('/universities');
    universities.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (err) {
    console.error('Failed to fetch universities', err);
  }
};

// ---- Lecturer options ----
const lecturerOptions = computed(() =>
  lecturers.value
    .filter(l => (l.lecturername || '').trim().length > 0)
    .map(lect => ({
      id:            lect.id,
      name:          (lect.lecturername || '').trim(),
      label:         (lect.lecturername || '').trim(),
      faculty:       lect.faculty?.facultyname || 'No Faculty',
      university:    lect.university?.name || 'No University',
      university_id: lect.university_id ?? lect.faculty?.university_id ?? null,
    }))
);

// ---- On lecturer selection ----
const onLecturerSelected = (lecturer) => {
  if (lecturer && form.value.role === 'user') {
    form.value.lecturer_id = lecturer.id;
    form.value.university_id = lecturer.university_id;
  }
};

// ---- Fetch lecturers ----
const fetchLecturers = async () => {
  try {
    const res = await api.get('/lecturers-for-dropdown');
    lecturers.value = Array.isArray(res.data) ? res.data : (res.data.data || []);
  } catch (err) {
    console.error('Failed to fetch lecturers', err);
  }
};

// ---- Load existing user for edit ----
const loadUserData = async () => {
  if (!isEditMode.value) return;
  try {
    loading.value = true;
    const response = await api.get(`/users/${route.params.id}`);
    const user = response.data;

    if (user.role !== 'user') {
      const nameParts = (user.name || '').trim().split(/\s+/);
      form.value.first_name = nameParts[0] || '';
      form.value.last_name  = nameParts.slice(1).join(' ') || '';
    } else {
      form.value.first_name = '';
      form.value.last_name  = '';
    }

    form.value.university_id = user.role === 'ministry_authority'
      ? null
      : (user.university_id || null);

    form.value.email = user.email;
    form.value.role  = user.role;

    // Pre-select the linked lecturer for role 'user'
    if (user.role === 'user' && user.lecturer_id) {
      const linked = lecturers.value.find(l => l.id === user.lecturer_id);
      if (linked) {
        selectedLecturer.value = {
          id:            linked.id,
          name:          (linked.lecturername || '').trim(),
          label:         (linked.lecturername || '').trim(),
          faculty:       linked.faculty?.facultyname,
          university:    linked.university?.name,
          university_id: linked.university_id ?? linked.faculty?.university_id ?? user.university_id,
        };
        form.value.lecturer_id   = linked.id;
        form.value.university_id = selectedLecturer.value.university_id;
      } else {
        console.warn('Lecturer not found in options for ID:', user.lecturer_id);
      }
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load user.';
  } finally {
    loading.value = false;
  }
};

// ---- Submit ----
async function handleSubmit() {
  error.value = null;
  validationErrors.value = [];

  // Password validation
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

  // Build payload
  const userData = {
    email: form.value.email,
    role:  form.value.role,
  };

  // Resolve university_id per target role
  if (form.value.role === 'ministry_authority') {
    userData.university_id = null;
  } else if (form.value.role === 'user') {
    if (!selectedLecturer.value) {
      validationErrors.value.push('Please select a lecturer for this user account.');
      return;
    }
    userData.name          = selectedLecturer.value.name;
    userData.lecturer_id   = selectedLecturer.value.id;
    userData.university_id = selectedLecturer.value.university_id;

    if (!userData.university_id) {
      validationErrors.value.push('The selected lecturer has no university assigned.');
      return;
    }
  } else {
    // admin / super_admin / lecturer_profile_admin
    if (!form.value.first_name || !form.value.last_name) {
      validationErrors.value.push('First name and last name are required for admin/super admin.');
      return;
    }
    userData.name = `${form.value.first_name} ${form.value.last_name}`.trim();

    if (isSuperOrAdmin.value) {
      userData.university_id = authStore.user?.university_id;
    } else {
      if (!form.value.university_id) {
        validationErrors.value.push('Please select a university for this role.');
        return;
      }
      userData.university_id = form.value.university_id;
    }
  }

  // Password (only when provided)
  if (form.value.password) {
    userData.password = form.value.password;
    userData.password_confirmation = form.value.password_confirmation;
  }

  try {
    loading.value = true;

    if (!isEditMode.value) {
      await api.post('/users', userData);
      alert('User created successfully.');
      router.push({ name: 'default.user-list' });
    } else {
      await api.put(`/users/${route.params.id}`, userData);
      alert('User updated successfully.');
      router.push({ name: 'default.user-list' });
    }
  } catch (err) {
    if (err.response?.data?.errors) {
      validationErrors.value = Object.values(err.response.data.errors).flat();
    } else {
      error.value = err.response?.data?.message || 'Operation failed.';
    }
  } finally {
    loading.value = false;
  }
}

// ---- Watcher: reset dependent fields on role change ----
watch(() => form.value.role, (newRole) => {
  if (newRole !== 'user') {
    selectedLecturer.value = null;
    form.value.lecturer_id = null;
  } else {
    form.value.first_name = '';
    form.value.last_name  = '';
  }

  if (newRole === 'ministry_authority') {
    form.value.university_id = null;
  } else if (isSuperOrAdmin.value) {
    form.value.university_id = authStore.user?.university_id || null;
  }
});

onMounted(async () => {
  await fetchLecturers();
  await loadUserData();
  await fetchUniversities();
});
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style lang="scss" scoped>
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
  .multiselect__input,
  .multiselect__single {
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