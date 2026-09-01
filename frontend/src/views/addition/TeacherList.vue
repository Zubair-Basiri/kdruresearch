<template>
  <div class="container-fluid p-3 bg-light">
    <div class="card shadow-sm">

      <!-- HEADER -->
      <div class="card-header table-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-white fw-semibold">Lecturers</h6>
        <RouterLink 
          v-if="authStore.canManageUniversityData"
          to="/dashboard/teachers/form" 
          class="btn btn-add btn-sm"
        >
          + Add Lecturer
        </RouterLink>
      </div>

      <div class="card-body pt-3">

        <!-- SEARCH + ROWS -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <input v-model="search" class="form-control w-25" placeholder="Search..." />
          <div>
            <label class="me-2 fw-semibold">Rows:</label>
            <select v-model="perPage" class="form-select d-inline-block w-auto">
              <option v-for="n in [5,10,15,20,50,100]" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
        </div>

        <!-- TABLE -->
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle">
            <thead>
              <tr>
                <th class="th-id">#</th>
                <th class="th-name">Lecturer</th>
                <th class="th-univ">University</th>
                <th class="th-faculty">Faculty</th>
                <th class="th-dept">Department</th>
                <th class="th-grade">Grade</th>
                <th class="th-qual">Qualification</th>
                <th class="th-spec">Specialized Area</th>
                <th class="th-action text-center">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(t, i) in paginatedData" :key="t.id">
                <td>{{ startIndex + i + 1 }}</td>
                <td>{{ t.lecturername }}</td>
                <td>{{ t.university_name }}</td>
                <td>{{ t.faculty_name }}</td>
                <td>{{ t.department_name }}</td>
                <td>{{ t.grade }}</td>
                <td>{{ t.qualification }}</td>
                <td>{{ t.specialized_area }}</td>
                <td class="text-center">
                  <template v-if="authStore.canManageUniversityData">
                    <RouterLink :to="`/dashboard/teachers/form/${t.id}`" class="btn btn-sm btn-warning me-2">Edit</RouterLink>
                    <button class="btn btn-sm btn-danger me-2" @click="deleteLecturer(t.id)">Delete</button>
                  </template>
                  <span v-else class="text-muted">—</span>
                  <!-- <button v-if="t.deleted_at" class="btn btn-sm btn-success" @click="restoreLecturer(t.id)">Restore</button> -->
                </td>
              </tr>
              <tr v-if="filteredLecturers.length === 0">
                <td colspan="9" class="text-center text-muted py-3">No results found</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- PAGINATION -->
        <div class="d-flex justify-content-between align-items-center mt-3">
          <span class="fw-semibold">Page {{ currentPage }} of {{ totalPages }}</span>
          <div>
            <button class="btn btn-sm btn-secondary me-2" :disabled="currentPage===1" @click="currentPage--">Prev</button>
            <button class="btn btn-sm btn-secondary" :disabled="currentPage===totalPages" @click="currentPage++">Next</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Data
const lecturers = ref([])
const search = ref('')
const perPage = ref(5)
const currentPage = ref(1)

// Fetch lecturers from API
const fetchLecturers = async () => {
  try {
    const res = await api.get('/lecturers')
    lecturers.value = res.data.map(l => ({
      ...l,
      university_name: l.university?.name || '',
      faculty_name: l.faculty?.facultyname || '',
      department_name: l.department?.deptname || '',
      specialized_area: Array.isArray(l.specialized_area) 
        ? l.specialized_area.map(s => `"${s}"`).join(', ') 
        : l.specialized_area || ''
    }))
  } catch (err) { console.error(err) }
}

// Filtered + Pagination
const filteredLecturers = computed(() =>
  lecturers.value.filter(l =>
    Object.values(l).some(v =>
      v && v.toString().toLowerCase().includes(search.value.toLowerCase())
    )
  )
)

watch(search, () => currentPage.value = 1)

const totalPages = computed(() =>
  Math.ceil(filteredLecturers.value.length / perPage.value) || 1
)
const startIndex = computed(() => (currentPage.value - 1) * perPage.value)
const paginatedData = computed(() =>
  filteredLecturers.value.slice(startIndex.value, startIndex.value + perPage.value)
)

// Delete / Restore
const deleteLecturer = async (id) => {
  if (!confirm('Delete this lecturer?')) return
  try {
    await api.delete(`/lecturers/${id}`)
    await fetchLecturers()
  } catch (err) { console.error(err) }
}

// const restoreLecturer = async (id) => {
//   try {
//     await api.post(`/lecturers/${id}/restore`)
//     await fetchLecturers()
//   } catch (err) { console.error(err) }
// }

// On mounted
onMounted(fetchLecturers)
</script>

<style scoped>
/* =========================
   HEADER (Academic Style)
========================= */
.table-header {
  background: #1f3a5f; /* deep academic navy */
}

.table-header h6 {
  font-size: 0.9rem;
  letter-spacing: 0.3px;
}

.btn-add {
  background: #3b82f6;
  color: #fff;
  font-size: 0.75rem;
  padding: 0.35rem 0.6rem;
  border-radius: 4px;
  border: none;
}

/* =========================
   TABLE BASE
========================= */
.table {
  font-size: 0.78rem; /* smaller academic text */
  table-layout: fixed; /* important for fitting columns */
}

.table th,
.table td {
  padding: 0.45rem 0.5rem;
  vertical-align: middle;
  word-wrap: break-word;
  white-space: normal;
}

/* =========================
   TABLE HEADER
========================= */
thead th {
  background-color: #3ec0e7;
  color: #ffffff;
  font-weight: 600;
  text-align: center;
  border-bottom: 2px solid #c7d8f5;
}

tbody td {
  background-color: #f7fbff;
}

/* =========================
   COLUMN WIDTH CONTROL
========================= */
.th-id       { width: 40px; }
.th-name     { width: 140px; }
.th-univ     { width: 120px; }
.th-faculty  { width: 120px; }
.th-dept     { width: 120px; }
.th-grade    { width: 110px; }
.th-action   { width: 110px; }

/* =========================
   ROW HOVER (Subtle)
========================= */
.table-hover tbody tr:hover {
  background-color: #f8fafc;
}

/* =========================
   ACTION BUTTONS
========================= */
.btn-warning,
.btn-danger {
  font-size: 0.7rem;
  padding: 0.25rem 0.45rem;
}

/* =========================
   SEARCH + SELECT
========================= */
.form-control,
.form-select {
  padding-right: 2rem !important; /* space for arrow */
  background-position: right 0.6rem center;
  background-size: 12px;
}
.table-responsive {
  overflow-x: auto;
}
/* =========================
   PAGINATION
========================= */
.btn-secondary {
  font-size: 0.7rem;
  padding: 0.3rem 0.55rem;
}

/* =========================
   DARK MODE – CARD & BACKGROUND
========================= */
[data-bs-theme='dark'] .card {
  background-color: #83a1e6; /* deep slate */
  border-color: #1e293b;
}

[data-bs-theme='dark'] .card-body {
  background-color: #0f172a;
}

/* =========================
   DARK MODE – HEADER
========================= */
[data-bs-theme='dark'] .table-header {
  background: #1e3a8a; /* academic navy */
}

[data-bs-theme='dark'] .table-header h6 {
  color: #e0e7ff;
}

/* =========================
   DARK MODE – TABLE HEADER
========================= */
[data-bs-theme='dark'] thead th {
  background-color: #1e293b !important; /* slate blue */
  color: #e5edff !important;
  border-bottom: 2px solid #334155;
}

/* =========================
   DARK MODE – TABLE BODY
========================= */
[data-bs-theme='dark'] tbody td {
  background-color: #0b1220 !important; /* soft dark blue */
  color: #e5e7eb !important;
  border-color: #1e293b;
}

/* Row hover */
[data-bs-theme='dark'] .table-hover tbody tr:hover td {
  background-color: #111827;
}

/* =========================
   DARK MODE – INPUTS & SELECT
========================= */
[data-bs-theme='dark'] .form-control,
[data-bs-theme='dark'] .form-select {
  background-color: #020617;
  color: #e5e7eb;
  border-color: #334155;
}

[data-bs-theme='dark'] .form-control::placeholder {
  color: #94a3b8;
}

/* Fix select arrow visibility */
[data-bs-theme='dark'] .form-select {
  background-position: right 0.6rem center;
}

/* =========================
   DARK MODE – BUTTONS
========================= */
[data-bs-theme='dark'] .btn-secondary {
  background-color: #1e293b;
  border-color: #334155;
}

[data-bs-theme='dark'] .btn-warning {
  background-color: #facc15;
  color: #1e293b;
}

[data-bs-theme='dark'] .btn-danger {
  background-color: #dc2626;
}

/* =========================
   DARK MODE – PAGINATION TEXT
========================= */
[data-bs-theme='dark'] span {
  color: #c7d2fe;
}
/* =========================
   RESPONSIVE ADJUSTMENTS
========================= */
@media (max-width: 1200px) {
  .th-name, .th-univ, .th-faculty, .th-dept, .th-grade, .th-qual, .th-spec, .th-action {
    font-size: 0.7rem;
    padding: 0.35rem 0.4rem;
  }

  .form-control, .form-select {
    font-size: 0.7rem;
    padding: 0.3rem 0.5rem;
  }

  .btn-add {
    font-size: 0.65rem;
    padding: 0.3rem 0.5rem;
  }

  .btn-warning, .btn-danger {
    font-size: 0.65rem;
    padding: 0.2rem 0.35rem;
  }
}

@media (max-width: 992px) {
  .table th, .table td {
    font-size: 0.68rem;
    padding: 0.3rem 0.35rem;
  }

  .th-name { min-width: 100px; }
  .th-univ { min-width: 80px; }
  .th-faculty { min-width: 80px; }
  .th-dept { min-width: 80px; }
  .th-grade, .th-qual, .th-spec, .th-action { min-width: 60px; }
}

@media (max-width: 768px) {
  .table-responsive {
    overflow-x: auto;
  }

  .table th, .table td {
    white-space: nowrap;
  }

  .table-header h6 {
    font-size: 0.8rem;
  }

  .form-control, .form-select {
    font-size: 0.65rem;
  }

  .btn-add {
    font-size: 0.6rem;
    padding: 0.25rem 0.45rem;
  }

  .btn-warning, .btn-danger {
    font-size: 0.6rem;
    padding: 0.2rem 0.3rem;
  }
}

@media (max-width: 576px) {
  .th-name { min-width: 90px; }
  .th-univ, .th-faculty, .th-dept { min-width: 70px; }
  .th-grade, .th-qual, .th-spec, .th-action { min-width: 50px; }

  .btn-add {
    padding: 0.2rem 0.35rem;
  }

  .btn-warning, .btn-danger {
    padding: 0.15rem 0.25rem;
  }

  input.form-control.w-25 {
    width: 100% !important;
    margin-bottom: 0.5rem;
  }

  select.form-select.d-inline-block.w-auto {
    width: 100% !important;
    margin-top: 0.5rem;
  }

  .d-flex.justify-content-between.align-items-center.mb-3 {
    flex-direction: column;
    align-items: stretch;
  }
}

</style>
