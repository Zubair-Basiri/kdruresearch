<template>
  <div class="container-fluid p-3 bg-light">
    <div class="card shadow-sm">

      <!-- Header -->
      <div class="card-header table-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-white fw-semibold">Department Table</h6>
        <button 
          v-if="authStore.canManageUniversityData"
          class="btn btn-add btn-sm" 
          @click="openAddModal"
        >
          <i class="bi bi-plus-circle me-1"></i> Add
        </button>
      </div>

      <div class="card-body pt-3">

        <!-- Search + Rows -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <input v-model="search" type="text" class="form-control w-25" placeholder="Search department..." />
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
                <th class="th-name">Department</th>
                <th class="th-faculty">Faculty</th>
                <th class="th-univ">University</th>
                <th class="th-action text-center">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(d, i) in paginatedData" :key="d.id">
                <td>{{ startIndex + i + 1 }}</td>
                <td>{{ d.deptname }}</td>
                <td>{{ d.faculty_name }}</td>
                <td>{{ d.university_name }}</td>
                <td class="text-center">
                  <template v-if="authStore.canManageUniversityData">
                    <button class="btn btn-sm btn-warning me-2" @click="openEditModal(d)">Edit</button>
                    <button class="btn btn-sm btn-danger me-2" @click="deleteDepartment(d.id)">Delete</button>
                    <button v-if="d.deleted_at" class="btn btn-sm btn-success" @click="restoreDepartment(d.id)">Restore</button>
                  </template>
                  <span v-else class="text-muted">—</span>
                </td>
              </tr>

              <tr v-if="filteredDepartments.length === 0">
                <td colspan="5" class="text-center text-muted py-3">No results found</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="fw-semibold">Page {{ currentPage }} of {{ totalPages }}</span>
          <div>
            <button class="btn btn-sm btn-secondary me-2" :disabled="currentPage===1" @click="currentPage--">Prev</button>
            <button class="btn btn-sm btn-secondary" :disabled="currentPage===totalPages" @click="currentPage++">Next</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Add/Edit -->
    <div class="modal fade" id="departmentModal" tabindex="-1" aria-hidden="true" ref="departmentModalRef">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">{{ isEdit ? 'Edit Department' : 'Add Department' }}</h5>
            <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Department Name</label>
              <input v-model="form.deptname" type="text" class="form-control" />
            </div>
            <div class="mb-3">
              <label class="form-label">Faculty</label>
              <select v-model="form.faculty_id" class="form-select">
                <option value="">Select Faculty</option>
                <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.facultyname }}</option>
              </select>
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
            <button class="btn btn-primary" @click="saveDepartment">{{ isEdit ? 'Update' : 'Add' }}</button>
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

// ===== DATA =====
const departments = ref([])
const faculties = ref([])
const universities = ref([])
const form = ref({ id: null, deptname: '', faculty_id: '', university_id: '' })
const search = ref('')
const perPage = ref(5)
const currentPage = ref(1)
const isEdit = ref(false)
const departmentModalRef = ref(null)
let modalInstance = null

// ===== FETCH DATA =====
const fetchDepartments = async () => {
  try {
    const res = await api.get('/departments')
    departments.value = res.data.map(d => ({
      ...d,
      faculty_name: d.faculty.facultyname,
      university_name: d.university.name
    }))
  } catch (err) { console.error(err) }
}

const fetchFaculties = async () => {
  try {
    const res = await api.get('/faculties')
    faculties.value = res.data
  } catch (err) { console.error(err) }
}

const fetchUniversities = async () => {
  try {
    const res = await api.get('/universities')
    universities.value = res.data
  } catch (err) { console.error(err) }
}

// ===== COMPUTED =====
const filteredDepartments = computed(() =>
  departments.value.filter(d =>
    d.deptname.toLowerCase().includes(search.value.toLowerCase()) ||
    d.faculty_name.toLowerCase().includes(search.value.toLowerCase()) ||
    d.university_name.toLowerCase().includes(search.value.toLowerCase())
  )
)

watch(search, () => currentPage.value = 1)

const totalPages = computed(() =>
  Math.ceil(filteredDepartments.value.length / perPage.value)
)
const startIndex = computed(() => (currentPage.value - 1) * perPage.value)
const paginatedData = computed(() =>
  filteredDepartments.value.slice(startIndex.value, startIndex.value + perPage.value)
)

// ===== MODAL =====
onMounted(() => {
  modalInstance = new bootstrap.Modal(departmentModalRef.value)
  fetchDepartments()
  fetchFaculties()
  fetchUniversities()
})

const openAddModal = () => {
  form.value = { id: null, deptname: '', faculty_id: '', university_id: '' }
  isEdit.value = false
  modalInstance.show()
}

const openEditModal = (d) => {
  form.value = {
    id: d.id,
    deptname: d.deptname,
    faculty_id: d.faculty_id,
    university_id: d.university_id
  }
  isEdit.value = true
  modalInstance.show()
}

const closeModal = () => modalInstance.hide()

// ===== SAVE / UPDATE =====
const saveDepartment = async () => {
  if (!form.value.deptname || !form.value.faculty_id || !form.value.university_id) {
    return alert('All fields required!')
  }

  try {
    if (isEdit.value) {
      await api.put(`/departments/${form.value.id}`, form.value)
    } else {
      await api.post('/departments', form.value)
    }
    await fetchDepartments()
    modalInstance.hide()
  } catch (err) {
    console.error(err.response?.data || err)
    alert('Validation error: ' + JSON.stringify(err.response?.data?.errors))
  }
}

// ===== DELETE / RESTORE =====
const deleteDepartment = async (id) => {
  if (!confirm('Delete this department?')) return
  try {
    await api.delete(`/departments/${id}`)
    await fetchDepartments()
  } catch (err) { console.error(err) }
}

const restoreDepartment = async (id) => {
  try {
    await api.post(`/departments/${id}/restore`)
    await fetchDepartments()
  } catch (err) { console.error(err) }
}
</script>

<style scoped>
/* header, buttons, table styles same as before */
.table-header { background: linear-gradient(135deg, #a1c4fd, #c2e9fb); padding:0.55rem 1rem; }
.btn-add { background: linear-gradient(135deg,#4dabf7,#74c0fc); color:#fff; border:none; }
.btn-add:hover { background: linear-gradient(135deg,#339af0,#4dabf7); }
th { color:#fff; font-weight:600; }
.th-id { background:#495057; }
.th-name { background:#4dabf7; }
.th-faculty { background:#63e6be; color:#fff; }
.th-univ { background:#4dabf7; color:#fff; }
.th-action { background:#74c0fc; }
td { color:#000; }
[data-bs-theme='dark'] td,.dark td,.theme-dark td { color:#f1f3c2 !important; }
[data-bs-theme='dark'] tbody tr:hover { background-color: rgba(255,255,255,0.05); }
.table td,.table th { padding:0.55rem; vertical-align:middle; }
</style>
