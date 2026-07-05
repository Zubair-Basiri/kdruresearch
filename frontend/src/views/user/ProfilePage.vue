<template>
  <b-row>
    <b-col lg="12">
      <b-card class="card-size">
        <div class="d-flex flex-wrap align-items-center justify-content-between">
          <div class="d-flex flex-wrap align-items-center">
            <div class="profile-img position-relative me-3 mb-3 mb-lg-0 profile-logo profile-logo1">
              <img src="@/assets/images/avatars/avatar.jpg" alt="User-Profile" class="theme-color-default-img img-fluid rounded-pill avatar-100" loading="lazy" />
            </div>
            <div class="d-flex flex-wrap align-items-center mb-3 mb-sm-0">
              <h4 class="me-2 h4">{{ user?.name || 'User' }}</h4>
              <span>- {{ user?.role || 'Role' }}</span>
            </div>
          </div>
        </div>
      </b-card>
    </b-col>

    <b-col lg="12">
      <b-card>
        <b-card-header>
          <h5 class="mb-0">Edit Profile</h5>
        </b-card-header>
        <b-card-body>
          <b-form @submit.prevent="updateProfile">
            <!-- Name -->
            <b-form-group label="Name" label-for="name">
              <b-form-input id="name" v-model="form.name" required></b-form-input>
            </b-form-group>

            <!-- Email (readonly) -->
            <b-form-group label="Email" label-for="email">
              <b-form-input id="email" :value="form.email" readonly></b-form-input>
            </b-form-group>

            <hr />
            <h4 class="mb-4">Change Password (optional)</h4>

            <!-- Current Password -->
            <b-form-group label="Current Password" label-for="current-password">
              <b-form-input
                id="current-password"
                type="password"
                v-model="form.current_password"
                autocomplete="off"
              ></b-form-input>
            </b-form-group>

            <!-- New Password -->
            <b-form-group label="New Password" label-for="new-password">
              <b-form-input
                id="new-password"
                type="password"
                v-model="form.new_password"
                autocomplete="off"
              ></b-form-input>
            </b-form-group>

            <!-- Confirm New Password -->
            <b-form-group label="Confirm New Password" label-for="new-password-confirm">
              <b-form-input
                id="new-password-confirm"
                type="password"
                v-model="form.new_password_confirmation"
                autocomplete="off"
              ></b-form-input>
            </b-form-group>

            <!-- Alerts -->
            <b-alert v-if="error" variant="danger" show>{{ error }}</b-alert>
            <b-alert v-if="success" variant="success" show>{{ success }}</b-alert>

            <!-- Submit Button -->
            <b-button type="submit" variant="primary" :disabled="loading">
              <b-spinner v-if="loading" small></b-spinner>
              <span v-else>Update Profile</span>
            </b-button>
          </b-form>
        </b-card-body>
      </b-card>
    </b-col>
  </b-row>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import api from '@/services/api';

const authStore = useAuthStore();
const user = authStore.user;

const form = reactive({
  name: user?.name || '',
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});

const loading = ref(false);
const error = ref(null);
const success = ref(null);

onMounted(() => {
  if (user) {
    form.name = user.name;
    form.email = user.email;
  }
});

const updateProfile = async () => {
  error.value = null;
  success.value = null;

  // Basic client-side validation
  if (form.new_password && form.new_password !== form.new_password_confirmation) {
    error.value = 'New password and confirmation do not match.';
    return;
  }

  const changingPassword = form.current_password || form.new_password || form.new_password_confirmation;
  if (changingPassword && (!form.current_password || !form.new_password || !form.new_password_confirmation)) {
    error.value = 'To change password, please fill all password fields.';
    return;
  }

  loading.value = true;
  try {
    const payload = {
      name: form.name,
    };
    if (changingPassword) {
      payload.current_password = form.current_password;
      payload.new_password = form.new_password;
      payload.new_password_confirmation = form.new_password_confirmation;
    }

    const response = await api.put('/user/profile', payload);

    // Update auth store with new name
    if (authStore.user) {
      authStore.user.name = form.name;
    }

    success.value = response.data.message || 'Profile updated successfully.';
    // Clear password fields
    form.current_password = '';
    form.new_password = '';
    form.new_password_confirmation = '';
  } catch (err) {
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors;
      error.value = Object.values(errors).flat().join(', ');
    } else if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else {
      error.value = 'An error occurred. Please try again.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.card-size{
  --bs-card-spacer-y: 0rem !important;
    margin-top: 1rem !important;
}
</style>