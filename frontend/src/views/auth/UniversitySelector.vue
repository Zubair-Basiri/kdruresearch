<template>
  <div class="university-selector">
    <div class="card p-4 shadow-sm" style="max-width: 500px; margin: 80px auto;">
      <h3 class="text-center mb-3">Select Your University</h3>
      <p class="text-muted text-center">Please select your university to continue.</p>
      
      <div v-if="loadingUniversities" class="text-center py-3">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2">Loading universities...</p>
      </div>
      
      <div v-else-if="error" class="alert alert-danger">
        {{ error }}
        <button class="btn btn-sm btn-outline-danger mt-2" @click="loadUniversities">Retry</button>
      </div>
      
      <template v-else>
        <div class="mb-3">
          <label class="form-label">University</label>
          <Multiselect
            v-model="selectedUniversity"
            :options="universityOptions"
            :searchable="true"
            :close-on-select="true"
            :show-labels="false"
            placeholder="Search and select..."
            label="label"
            track-by="id"
            :max-height="200"
            :options-limit="50"
            class="university-multiselect"
          />
        </div>
        
        <button 
          class="btn btn-primary w-100" 
          :disabled="!selectedUniversity || saving"
          @click="saveUniversity"
        >
          <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
          {{ saving ? 'Saving...' : 'Continue' }}
        </button>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import Multiselect from 'vue-multiselect';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
const router = useRouter();

const universities = ref([]);
const selectedUniversity = ref(null);
const loadingUniversities = ref(false);
const saving = ref(false);
const error = ref(null);
let loaded = false;

const universityOptions = computed(() =>
  universities.value.map(u => ({
    id: u.id,
    label: u.name,
  }))
);

const loadUniversities = async (retry = true) => {
  if (loaded) return;
  loadingUniversities.value = true;
  error.value = null;
  try {
    const response = await api.get('/universities');
    universities.value = response.data;
    loaded = true;
  } catch (err) {
    console.error('Load universities error:', err);
    if (retry) {
      // Retry once after a short delay
      setTimeout(() => loadUniversities(false), 1000);
      return;
    }
    error.value = err.response?.data?.message || 'Failed to load universities.';
  } finally {
    loadingUniversities.value = false;
  }
};

const saveUniversity = async () => {
  if (!selectedUniversity.value) return;
  saving.value = true;
  error.value = null;
  try {
    const response = await authStore.updateUniversity(selectedUniversity.value.id);
    console.log('University saved successfully:', response);
    router.push({ name: 'default.dashboard' });
  } catch (err) {
    console.error('Save university error:', err);
    console.error('Error response:', err.response);
    error.value = err.response?.data?.message || 'Failed to save university.';
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadUniversities();
});
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style scoped>
.university-selector {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fa;
}

.university-multiselect .multiselect__tags {
  border-radius: 8px;
  border: 1px solid #d0d7de;
  min-height: 36px;
}
</style>