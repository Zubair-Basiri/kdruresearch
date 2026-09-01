<template>
  <div class="container-fluid p-3 bg-light">
    <div class="card shadow-sm">

      <!-- Header -->
      <div class="card-header table-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-white fw-semibold">Faculty Table</h6>
        <button 
          v-if="authStore.canManageUniversityData"
          class="btn btn-add btn-sm" 
          @click="openAddModal"
        >
          <i class="bi bi-plus-circle me-1"></i> Add
        </button>
      </div>

      <div class="card-body pt-3">

        <!-- Search + Rows Per Page -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <input
            v-model="search"
            type="text"
            class="form-control w-25"
            placeholder="Search faculty..."
          />

          <div>
            <label class="me-2 fw-semibold">Rows:</label>
            <select v-model="perPage" class="form-select d-inline-block w-auto">
              <option v-for="n in [5,10,15,20]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="th-id">#</th>
                <th class="th-name">Faculty Name</th>
                <th class="th-univ">University</th>
                <th class="th-action text-center">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(f, i) in paginatedData" :key="f.id">
                <td>{{ startIndex + i + 1 }}</td>
                <td>{{ f.facultyname }}</td>
                <td>{{ f.university?.name }}</td>
                <td class="text-center">
                  <template v-if="authStore.canManageUniversityData">
                    <button class="btn btn-sm btn-warning me-2" @click="openEditModal(f)">Edit</button>
                    <button class="btn btn-sm btn-danger" @click="deleteFaculty(f)">Delete</button>
                  </template>
                </td>
              </tr>

              <tr v-if="filteredFaculties.length === 0">
                <td colspan="4" class="text-center text-muted py-3">
                  No results found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="fw-semibold">
            Page {{ currentPage }} of {{ totalPages }}
          </span>

          <div>
            <button class="btn btn-sm btn-secondary me-2" :disabled="currentPage===1" @click="currentPage--">Prev</button>
            <button class="btn btn-sm btn-secondary" :disabled="currentPage===totalPages" @click="currentPage++">Next</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div class="modal fade" id="facultyModal" tabindex="-1" aria-hidden="true" ref="facultyModalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">{{ isEdit ? 'Edit Faculty' : 'Add Faculty' }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Faculty Name</label>
              <input v-model="form.facultyname" type="text" class="form-control" placeholder="Faculty Name" />
            </div>
            <div class="mb-3">
              <label class="form-label">University</label>
              <select v-model="form.university_id" class="form-select">
                <option value="">Select University</option>
                <option v-for="u in universities" :key="u.id" :value="u.id">{{ u.name }}</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="closeModal">Cancel</button>
            <button class="btn btn-primary" @click="saveFaculty">{{ isEdit ? 'Update' : 'Add' }}</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import bootstrap from 'bootstrap/dist/js/bootstrap.bundle'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const faculties = ref([])
const universities = ref([])
const search = ref('')
const perPage = ref(5)
const currentPage = ref(1)

/* ===== FETCH FACULTIES ===== */
const fetchFaculties = async () => {
  try {
    const res = await api.get('/faculties')
    faculties.value = res.data
  } catch (err) {
    console.error(err)
  }
}

/* ===== FETCH UNIVERSITIES ===== */
const fetchUniversities = async () => {
  try {
    const res = await api.get('/universities')
    universities.value = res.data
  } catch (err) {
    console.error(err)
  }
}

/* ===== SEARCH & PAGINATION ===== */
const filteredFaculties = computed(() =>
  faculties.value.filter(f =>
    f.facultyname.toLowerCase().includes(search.value.toLowerCase()) ||
    f.university?.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

watch(search, () => currentPage.value = 1)

const totalPages = computed(() => Math.ceil(filteredFaculties.value.length / perPage.value))
const startIndex = computed(() => (currentPage.value - 1) * perPage.value)
const paginatedData = computed(() =>
  filteredFaculties.value.slice(startIndex.value, startIndex.value + perPage.value)
)

/* ===== MODAL LOGIC ===== */
const facultyModalRef = ref(null)
let modalInstance = null
const form = ref({ id: null, facultyname: '', university_id: '' })
const isEdit = ref(false)

onMounted(() => {
  modalInstance = new bootstrap.Modal(facultyModalRef.value)
  fetchFaculties()
  fetchUniversities()
})

const openAddModal = () => {
  form.value = { id: null, facultyname: '', university_id: '' }
  isEdit.value = false
  modalInstance.show()
}

const openEditModal = (faculty) => {
  form.value = { id: faculty.id, facultyname: faculty.facultyname, university_id: faculty.university_id }
  isEdit.value = true
  modalInstance.show()
}

const closeModal = () => modalInstance.hide()

/* ===== SAVE ===== */
const saveFaculty = async () => {
  if (!form.value.facultyname || !form.value.university_id) return alert('All fields required!')
  try {
    if (isEdit.value) {
      await api.put(`/faculties/${form.value.id}`, form.value)
    } else {
      await api.post('/faculties', form.value)
    }
    await fetchFaculties()
    modalInstance.hide()
  } catch (err) {
    console.error(err.response?.data || err)
  }
}

/* ===== DELETE ===== */
const deleteFaculty = async (faculty) => {
  if (!confirm('Delete this faculty?')) return
  try {
    await api.delete(`/faculties/${faculty.id}`)
    await fetchFaculties()
  } catch (err) {
    console.error(err.response?.data || err)
  }
}
</script>

<style scoped>
.table-header {
  background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
  padding: 0.55rem 1rem;
}

.btn-add {
  background: linear-gradient(135deg, #4dabf7, #74c0fc);
  color: #fff;
  border: none;
}
.btn-add:hover { background: linear-gradient(135deg, #339af0, #4dabf7); color: #fff; }

th { color: #fff; font-weight: 600; }
.th-id { background: #495057; }
.th-name { background: #4dabf7; }
.th-univ { background: #63e6be; color: #fff; }
.th-action { background: #74c0fc; }

td { color: #000; }

[data-bs-theme='dark'] td,
.dark td,
.theme-dark td { color: #f1f3c2 !important; }
[data-bs-theme='dark'] tbody tr:hover { background-color: rgba(255, 255, 255, 0.05); }

.table td, .table th { padding: 0.55rem; vertical-align: middle; }
</style>
