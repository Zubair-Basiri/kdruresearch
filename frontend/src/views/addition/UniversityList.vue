<template>
  <div class="container-fluid p-3 bg-light">
    <div class="card shadow-sm">

      <!-- Header -->
      <div class="card-header table-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-white fw-semibold">University Table</h6>
        <button class="btn btn-add btn-sm" @click="addUniversity">
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
            placeholder="Search university..."
          />

          <div>
            <label class="me-2 fw-semibold">Rows:</label>
            <select v-model="perPage" class="form-select d-inline-block w-auto">
              <option v-for="n in [5,10,15,20]" :key="n" :value="n">
                {{ n }}
              </option>
            </select>
          </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead>
              <tr>
                <th class="th-id">#</th>
                <th class="th-name">University</th>
                <th class="th-action text-center">Actions</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(uni, i) in paginatedData" :key="i">
                <td>{{ startIndex + i + 1 }}</td>
                <td>{{ uni.name }}</td>
                <td class="text-center">
                  <button class="btn btn-sm btn-warning me-2" @click="editUniversity(startIndex + i)">
                    Edit
                  </button>
                  <button class="btn btn-sm btn-danger" @click="deleteUniversity(startIndex + i)">
                    Delete
                  </button>
                </td>
              </tr>

              <tr v-if="filteredUniversities.length === 0">
                <td colspan="3" class="text-center text-muted py-3">
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
            <button
              class="btn btn-sm btn-secondary me-2"
              :disabled="currentPage === 1"
              @click="currentPage--"
            >
              Prev
            </button>
            <button
              class="btn btn-sm btn-secondary"
              :disabled="currentPage === totalPages"
              @click="currentPage++"
            >
              Next
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import api from '@/services/api'

const universities = ref([])
const search = ref('')
const perPage = ref(5)
const currentPage = ref(1)

/* ================= FETCH ================= */
const fetchUniversities = async () => {
  try {
    const response = await api.get('/universities')
    universities.value = response.data
  } catch (error) {
    console.error(error)
  }
}

onMounted(() => {
  fetchUniversities()
})

/* ================= SEARCH ================= */
const filteredUniversities = computed(() =>
  universities.value.filter(u =>
    u.name.toLowerCase().includes(search.value.toLowerCase())
  )
)

watch(search, () => (currentPage.value = 1))

const totalPages = computed(() =>
  Math.ceil(filteredUniversities.value.length / perPage.value)
)

const startIndex = computed(() =>
  (currentPage.value - 1) * perPage.value
)

const paginatedData = computed(() =>
  filteredUniversities.value.slice(
    startIndex.value,
    startIndex.value + perPage.value
  )
)

/* ================= ADD ================= */
const addUniversity = async () => {
  const name = prompt('Enter university name')
  if (!name) return

  try {
    const response = await api.post('/universities', { name })

    console.log('Response:', response) // debug

    universities.value.unshift(response.data)
  } catch (error) {
    console.error('Full error:', error)

    if (error.response) {
      console.error('Server response:', error.response.data)
      alert(JSON.stringify(error.response.data))
    } else {
      alert('Something went wrong')
    }
  }
}

/* ================= EDIT ================= */
const editUniversity = async (index) => {
  const uni = paginatedData.value[index - startIndex.value]
  const name = prompt('Edit university name', uni.name)
  if (!name) return

  try {
    await api.put(`/universities/${uni.id}`, { name })
    uni.name = name
  } catch (error) {
    console.error(error.response.data)
  }
}

/* ================= DELETE ================= */
const deleteUniversity = async (index) => {
  const uni = paginatedData.value[index - startIndex.value]
  if (!confirm('Delete this university?')) return

  try {
    await api.delete(`/universities/${uni.id}`)
    universities.value = universities.value.filter(u => u.id !== uni.id)
  } catch (error) {
    console.error(error.response.data)
  }
}
</script>
<style scoped>
/* ===== HEADER (Light Water Color) ===== */
.table-header {
  background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
  padding: 0.55rem 1rem;
}

/* ===== ADD BUTTON (NO GRAY) ===== */
.btn-add {
  background: linear-gradient(135deg, #4dabf7, #74c0fc);
  color: #fff;
  border: none;
}

.btn-add:hover {
  background: linear-gradient(135deg, #339af0, #4dabf7);
  color: #fff;
}

/* ===== TABLE HEADER ===== */
th {
  color: #fff;
  font-weight: 600;
}

.th-id {
  background: #495057;
}

.th-name {
  background: #4dabf7;
}

.th-action {
  background: #74c0fc;
}

/* ===== LIGHT MODE ROW TEXT ===== */
td {
  color: #000;
}

/* ===== DARK MODE SUPPORT ===== */
/* Hope UI / Bootstrap dark mode */
[data-bs-theme='dark'] td,
.dark td,
.theme-dark td {
  color: #f1f3c2 !important; /* lemon-white */
}

/* Optional subtle row background in dark */
[data-bs-theme='dark'] tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.05);
}

/* ===== COMPACT TABLE ===== */
.table td,
.table th {
  padding: 0.55rem;
  vertical-align: middle;
}
</style>
