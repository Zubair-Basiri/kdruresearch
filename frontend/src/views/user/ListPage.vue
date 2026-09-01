<template>
  <b-row>
    <b-col sm="12">
      <div class="card shadow-sm border-0">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom-0 pt-4 px-4">
          <div class="header-title">
            <h4 class="card-title fw-bold mb-0">User List</h4>
          </div>
          <div class="d-flex gap-2 align-items-center">
            <!-- Search Bar -->
            <div class="search-wrapper">
              <i class="bi bi-search search-icon"></i>
              <input 
                type="text" 
                v-model="searchTerm" 
                class="form-control search-input" 
                placeholder="Search by name or email..."
              />
              <i v-if="searchTerm" class="bi bi-x-circle clear-icon" @click="searchTerm = ''"></i>
            </div>
            <b-button variant="primary" :to="{ name: 'default.user-add' }" size="sm" class="px-4 py-2 rounded-pill shadow-sm">
              <i class="bi bi-plus-circle me-2"></i>Add User
            </b-button>
          </div>
        </div>
        <div class="card-body px-4 pb-4">
          <div v-if="loading" class="text-center py-4">
            <b-spinner variant="primary" label="Loading..."></b-spinner>
          </div>
          <div v-else-if="error" class="alert alert-danger">{{ error }}</div>
          <div v-else class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="user-list-table">
              <thead class="bg-light">
                <tr>
                  <th class="border-0 rounded-start" style="width: 60px;">No.</th>
                  <th class="border-0">Name</th>
                  <th class="border-0">Email</th>
                  <th class="border-0">Role</th>
                  <th class="border-0">Approved</th>
                  <th class="border-0 rounded-end" style="width: 180px;">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(user, index) in paginatedUsers" :key="user.id" class="border-bottom">
                  <td class="fw-medium">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-40 bg-soft-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <span>{{ user.name.charAt(0) }}</span>
                      </div>
                      <span class="fw-semibold">{{ user.name }}</span>
                    </div>
                  </td>
                  <td>{{ user.email }}</td>
                  <td><span class="badge bg-primary">{{ user.role }}</span></td>
                  <td>
                    <span :class="['badge', user.is_approved ? 'bg-success' : 'bg-warning']">
                      {{ user.is_approved ? 'Yes' : 'No' }}
                    </span>
                  </td>
                  <td>
                    <div class="btn-group" role="group">
                      <b-button 
                        :variant="user.is_approved ? 'outline-danger' : 'outline-success'" 
                        size="sm" 
                        class="btn-icon me-1" 
                        @click="toggleApproval(user.id, user.is_approved)"
                      >
                        <i :class="user.is_approved ? 'bi bi-x-circle' : 'bi bi-check-circle'"></i>
                        {{ user.is_approved ? 'Disable' : 'Approve' }}
                      </b-button>
                      <b-button 
                        variant="outline-primary" 
                        size="sm" 
                        class="btn-icon me-1" 
                        v-b-tooltip.hover title="Edit"
                        @click="editUser(user.id)"
                      >
                        <i class="bi bi-pencil">Edit</i>
                      </b-button>
                      <b-button 
                        variant="outline-danger" 
                        size="sm" 
                        class="btn-icon" 
                        v-b-tooltip.hover title="Delete"
                        @click="confirmDelete(user.id, user.name)"
                      >
                        <i class="bi bi-trash">Delete</i>
                      </b-button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- Pagination -->
            <div v-if="filteredUsers.length > perPage" class="pagination-wrapper mt-3 d-flex justify-content-end">
              <b-pagination
                v-model="currentPage"
                :total-rows="filteredUsers.length"
                :per-page="perPage"
                size="sm"
                class="mb-0"
              />
            </div>
            <div v-if="filteredUsers.length === 0" class="text-center text-muted py-4">
              No users found matching "{{ searchTerm }}"
            </div>
          </div>
        </div>
      </div>
    </b-col>
  </b-row>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';

const router = useRouter();
const users = ref([]);
const loading = ref(false);
const error = ref(null);
const searchTerm = ref('');
const currentPage = ref(1);
const perPage = ref(5);

// Filter users by name or email (case-insensitive)
const filteredUsers = computed(() => {
  if (!searchTerm.value.trim()) return users.value;
  const term = searchTerm.value.toLowerCase().trim();
  return users.value.filter(user => 
    user.name.toLowerCase().includes(term) || 
    user.email.toLowerCase().includes(term)
  );
});

// Pagination
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredUsers.value.slice(start, start + perPage.value);
});

// Reset page when search changes
const resetPage = () => {
  currentPage.value = 1;
};

// Watch search term to reset pagination
import { watch } from 'vue';
watch(searchTerm, resetPage);

// Fetch users from API
const fetchUsers = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/users');
    users.value = response.data;
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load users.';
  } finally {
    loading.value = false;
  }
};

// Navigate to edit page
const editUser = (id) => {
  router.push({ name: 'default.user-edit', params: { id } });
};

// Toggle approval (approve/unapprove)
const toggleApproval = async (id, currentStatus) => {
  const action = currentStatus ? 'disable' : 'approve';
  if (confirm(`Are you sure you want to ${action} this user?`)) {
    try {
      const response = await api.put(`/users/${id}/toggle-approval`);
      const user = users.value.find(u => u.id === id);
      if (user) user.is_approved = response.data.is_approved;
    } catch (err) {
      alert(err.response?.data?.message || 'Operation failed');
    }
  }
};

// Delete with confirmation
const confirmDelete = (id, name) => {
  if (confirm(`Are you sure you want to delete user "${name}"?`)) {
    deleteUser(id);
  }
};

const deleteUser = async (id) => {
  try {
    await api.delete(`/users/${id}`);
    users.value = users.value.filter(user => user.id !== id);
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to delete user.');
  }
};

onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
/* Academic style enhancements */
.card {
  border-radius: 1rem;
  transition: all 0.2s ease;
}
.card-header {
  padding-bottom: 0.5rem;
}
.table thead th {
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #2c3e50;
  background-color: #f8fafc;
  padding: 1rem 0.75rem;
}
.table tbody td {
  padding: 1rem 0.75rem;
  color: #4a5568;
  font-size: 0.95rem;
}
.avatar-40 {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bg-soft-primary {
  background-color: rgba(52, 152, 219, 0.1);
}
.btn-icon {
  width: 50px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}
.btn-outline-primary:hover {
  background-color: #3498db;
  border-color: #3498db;
  color: white;
}
.btn-outline-danger:hover {
  background-color: #e74c3c;
  border-color: #e74c3c;
  color: white;
}
.password-placeholder {
  font-family: monospace;
  letter-spacing: 2px;
  color: #95a5a6;
}

/* Search bar styling */
.search-wrapper {
  position: relative;
  width: 260px;
}
.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #9aa8b5;
  font-size: 0.9rem;
  pointer-events: none;
}
.search-input {
  padding-left: 32px;
  padding-right: 32px;
  border-radius: 30px;
  border: 1px solid #e2e8f0;
  background-color: #fff;
}
.search-input:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
}
.clear-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #9aa8b5;
  font-size: 1rem;
}
.clear-icon:hover {
  color: #e74c3c;
}

.pagination-wrapper {
  padding-top: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .table {
    font-size: 0.85rem;
  }
  .avatar-40 {
    width: 30px;
    height: 30px;
  }
  .search-wrapper {
    width: 180px;
  }
  .card-header {
    flex-direction: column;
    gap: 10px;
  }
  .d-flex.gap-2 {
    width: 100%;
    justify-content: space-between;
  }
}
</style>

<style scoped>
/* Academic style enhancements */
.card {
  border-radius: 1rem;
  transition: all 0.2s ease;
}
.card-header {
  padding-bottom: 0.5rem;
}
.table thead th {
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #2c3e50;
  background-color: #f8fafc;
  padding: 1rem 0.75rem;
}
.table tbody td {
  padding: 1rem 0.75rem;
  color: #4a5568;
  font-size: 0.95rem;
}
.avatar-40 {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.bg-soft-primary {
  background-color: rgba(52, 152, 219, 0.1);
}
.btn-icon {
  width: 50px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
}
.btn-outline-primary:hover {
  background-color: #3498db;
  border-color: #3498db;
  color: white;
}
.btn-outline-danger:hover {
  background-color: #e74c3c;
  border-color: #e74c3c;
  color: white;
}
.password-placeholder {
  font-family: monospace;
  letter-spacing: 2px;
  color: #95a5a6;
}

/* Search bar styling */
.search-wrapper {
  position: relative;
  width: 260px;
}
.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #9aa8b5;
  font-size: 0.9rem;
  pointer-events: none;
}
.search-input {
  padding-left: 32px;
  padding-right: 32px;
  border-radius: 30px;
  border: 1px solid #e2e8f0;
  background-color: #fff;
}
.search-input:focus {
  border-color: #3498db;
  box-shadow: 0 0 0 2px rgba(52,152,219,0.2);
}
.clear-icon {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  color: #9aa8b5;
  font-size: 1rem;
}
.clear-icon:hover {
  color: #e74c3c;
}

.pagination-wrapper {
  padding-top: 10px;
}

/* Responsive */
@media (max-width: 768px) {
  .table {
    font-size: 0.85rem;
  }
  .avatar-40 {
    width: 30px;
    height: 30px;
  }
  .search-wrapper {
    width: 180px;
  }
  .card-header {
    flex-direction: column;
    gap: 10px;
  }
  .d-flex.gap-2 {
    width: 100%;
    justify-content: space-between;
  }
}
</style>