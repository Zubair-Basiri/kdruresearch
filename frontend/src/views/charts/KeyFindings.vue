<template>
  <div class="p-6 key-findings-container">
    <!-- Header -->
    <div class="dashboard-header">
      <h2 class="main-title">Key Research Findings</h2>
      <p class="subtitle">Filter and explore academic output across dimensions</p>
    </div>

    <!-- Filter Panel -->
    <div class="filter-panel">
      <!-- Year Range (text inputs) -->
      <div class="year-range">
        <div class="range-item">
          <label>From Year</label>
          <input
            type="number"
            v-model.number="startYear"
            min="1900"
            max="2100"
            placeholder="e.g. 2015"
            class="year-input"
          />
        </div>
        <div class="range-item">
          <label>To Year</label>
          <input
            type="number"
            v-model.number="endYear"
            min="1900"
            max="2100"
            placeholder="e.g. 2025"
            class="year-input"
          />
        </div>
        <div class="range-hint" v-if="startYear && endYear && startYear > endYear">
          ⚠️ End year must be ≥ start year
        </div>
      </div>

      <!-- Add this inside the filter-panel, after year-range or before the filter-grid -->
      <div class="researcher-name-filter">
        <label>Researcher Name</label>
        <input
          type="text"
          v-model="researcherName"
          placeholder="Type researcher name..."
          class="researcher-input"
        />
      </div>

      <!-- Filter Grid -->
      <div class="filter-grid">
        <div v-for="filter in filterConfig" :key="filter.key" class="filter-item">
          <div class="filter-header">
            <label>{{ filter.label }}</label>
            <div class="filter-actions-small">
              <button type="button" class="text-link" @click="selectAll(filter.key)">All</button>
              <span class="sep">|</span>
              <button type="button" class="text-link" @click="clearFilter(filter.key)">Clear</button>
            </div>
          </div>
          <!-- Inside the v-for for filters -->
        <Multiselect
        v-model="selectedFilters[filter.key]"
        :options="filter.options"
        :multiple="true"
        :searchable="true"
        :close-on-select="false"
        :clear-on-select="false"
        :preserve-search="true"
        placeholder="Select options"
        label="text"
        track-by="value"
        :show-labels="false"
        :max-height="200"
        class="multiselect-custom"
        >
        <template #selection="{ values }">
            <span v-if="values.length === 0" class="multiselect__placeholder">
            {{ filter.label }}
            </span>
            <span v-else-if="values.length === 1">{{ values[0].text }}</span>
            <span v-else-if="values.length === filter.options.length">
            All ({{ values.length }})
            </span>
            <span v-else>{{ values.length }} selected</span>
        </template>
        </Multiselect>
        </div>
      </div>

      <!-- Column Selector -->
      <div class="column-selector">
        <div class="filter-header">
          <label>Display Columns</label>
          <div class="filter-actions-small">
            <button type="button" class="text-link" @click="selectAllColumns">All</button>
            <span class="sep">|</span>
            <button type="button" class="text-link" @click="clearColumns">Clear</button>
          </div>
        </div>
        <Multiselect
          v-model="selectedColumns"
          :options="columnOptions"
          :multiple="true"
          :searchable="true"
          :close-on-select="false"
          :clear-on-select="false"
          :preserve-search="true"
          :multiple-label="getMultipleLabel(columnOptions)"
          placeholder="Choose columns to display"
          label="text"
          track-by="value"
          :show-labels="false"
          :max-height="200"
          class="multiselect-custom"
        />
        <span class="selected-count" v-if="selectedColumns.length">
          {{ selectedColumns.length }} columns selected
        </span>
      </div>

      <!-- Action Buttons -->
      <div class="filter-actions">
        <button class="btn btn-primary" @click="applyFilters">Apply Filters</button>
        <button class="btn btn-outline" @click="resetFilters">Reset All</button>
        <button class="btn btn-outline" @click="exportPdf">Export Report</button>
      </div>
    </div>

    <!-- Results Table -->
    <div v-if="loading" class="loading-state">Loading data...</div>
    <div v-else-if="filteredData.length" class="table-wrapper">
      <p style="color:black">The table below shows the filtered research findings based on your selections.</p>
      <table class="findings-table">
        <thead>
          <tr>
            <th v-for="col in displayColumns" :key="col.key">{{ col.label }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, idx) in paginatedData" :key="idx">
            <td v-for="col in displayColumns" :key="col.key">
              {{ row[col.key] ?? '—' }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination-footer">
        <button class="p-btn" @click="page--" :disabled="page === 1">← Previous</button>
        <span class="p-info">Page {{ page }} of {{ totalPages }}</span>
        <button class="p-btn" @click="page++" :disabled="page === totalPages">Next →</button>
      </div>
    </div>
    <div v-else class="no-data">No records match the selected filters.</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Multiselect from 'vue-multiselect'
import api from '@/services/api'
const researcherName = ref('')

// ---------- Column Definitions ----------
const columns = [
  { key: 'researcher_name', label: 'Researcher Name' },
  { key: 'faculty', label: 'Faculty' },
  { key: 'department', label: 'Department' },
  { key: 'grade', label: 'Grade' },
  { key: 'education', label: 'Education' },
  { key: 'year', label: 'Year' },
  { key: 'publication_type', label: 'Publication Type' },
  { key: 'index', label: 'Index' },
  { key: 'funding', label: 'Funding' },
  { key: 'status', label: 'Status' },
  { key: 'collaboration', label: 'Collaboration' },
  { key: 'author_position', label: 'Author Position' },
  { key: 'research_area', label: 'Research Area' },
]

const columnOptions = columns.map(col => ({ value: col.key, text: col.label }))

// ---------- Filter Keys ----------
const filterKeys = [
  'faculty', 'department', 'grade', 'qualification', 'publication_type',
  'index', 'funding', 'status', 'collaboration', 'author_position', 'research_area'
]

// ---------- Refs ----------
const filterConfig = ref([])
const selectedFilters = ref(Object.fromEntries(filterKeys.map(key => [key, []])))

// Year range (custom input)
const startYear = ref('')
const endYear = ref('')

// Selected columns (default: all)
const selectedColumns = ref([...columnOptions])

// Data
const allData = ref([])
const filteredData = ref([])
const loading = ref(false)
const page = ref(1)
const perPage = 15

// ---------- Computed ----------
const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage) || 1)
const paginatedData = computed(() =>
  filteredData.value.slice((page.value - 1) * perPage, page.value * perPage)
)

const displayColumns = computed(() =>
  columns.filter(col => selectedColumns.value.some(sc => sc.value === col.key))
)

// ---------- Helper: multiple-label function ----------
function getMultipleLabel(allOptions) {
  return (selected) => {
    if (!selected.length) return 'Select options'
    if (selected.length === allOptions.length) return `All (${selected.length})`
    if (selected.length > 2) return `${selected.length} selected`
    return selected.map(v => v.text).join(', ')
  }
}

// ---------- Load Filter Options ----------
onMounted(async () => {
  try {
    const res = await api.get('/key-findings/filters')
    const data = res.data
    filterConfig.value = [
      { key: 'faculty', label: 'Faculty', options: mapOptions(data.faculties) },
      { key: 'department', label: 'Department', options: mapOptions(data.departments) },
      { key: 'grade', label: 'Grade', options: mapSimple(data.grades) },
      { key: 'qualification', label: 'Education', options: mapSimple(data.qualifications) },
      { key: 'publication_type', label: 'Publication Type', options: mapSimple(data.publication_types) },
      { key: 'index', label: 'Index', options: mapSimple(data.indexes) },
      { key: 'funding', label: 'Funding', options: mapSimple(data.funding_sources) },
      { key: 'status', label: 'Status', options: mapSimple(data.statuses) },
      { key: 'collaboration', label: 'Collaboration', options: mapSimple(data.collaborations) },
      { key: 'author_position', label: 'Author Position', options: mapSimple(data.author_positions) },
      { key: 'research_area', label: 'Research Area', options: mapSimple(data.research_areas) },
    ]
  } catch (error) {
    console.error('Failed to load filters', error)
  }
})

function mapOptions(obj) {
  return Object.entries(obj).map(([value, text]) => ({ value, text }))
}
function mapSimple(arr) {
  return arr.map(v => ({ value: v, text: v }))
}

// ---------- Filter Helpers ----------
function selectAll(key) {
  const filter = filterConfig.value.find(f => f.key === key)
  if (filter) {
    selectedFilters.value[key] = [...filter.options]
  }
}

function clearFilter(key) {
  selectedFilters.value[key] = []
}

function selectAllColumns() {
  selectedColumns.value = [...columnOptions]
}

function clearColumns() {
  selectedColumns.value = []
}

// ---------- Apply Filters ----------
async function applyFilters() {
  // Validate year range
  if (startYear.value && endYear.value && startYear.value > endYear.value) {
    alert('End year must be greater than or equal to start year.')
    return
  }

  loading.value = true
  page.value = 1
  try {
    const params = {}
    if (startYear.value) params.start_year = startYear.value
    if (endYear.value) params.end_year = endYear.value
    if (researcherName.value) params.researcher_name = researcherName.value

    for (const [key, values] of Object.entries(selectedFilters.value)) {
      if (values && values.length > 0) {
        params[key] = values.map(v => v.value)
      }
    }
    const res = await api.get('/key-findings', { params })
    allData.value = res.data
    filteredData.value = allData.value
  } catch (error) {
    console.error('Failed to fetch data', error)
  } finally {
    loading.value = false
  }
}

// ---------- Reset ----------
function resetFilters() {
  filterKeys.forEach(key => {
    selectedFilters.value[key] = []
  })
  startYear.value = ''
  endYear.value = ''
  researcherName.value = ''
  selectedColumns.value = [...columnOptions]
  filteredData.value = []
  page.value = 1
}

//Export method
async function exportPdf() {
  try {
    const params = new URLSearchParams()
    if (startYear.value) params.append('start_year', startYear.value)
    if (endYear.value) params.append('end_year', endYear.value)
    if (researcherName.value) params.append('researcher_name', researcherName.value)
    
    //All multi‑select filters
    for (const [key, values] of Object.entries(selectedFilters.value)) {
      if (values.length) {
        params.append(key, values.map(v => v.value).join(','))
      }
    }
    //Selected columns
    if (selectedColumns.value.length) {
      params.append('selected_columns', selectedColumns.value.map(c => c.value).join(','))
    }
    
    const url = `${api.defaults.baseURL}/api/key-findings/preview?${params.toString()}`
    window.open(url, '_blank')
  } catch (error) {
    console.error('PDF export failed', error)
    alert('Could not generate PDF. Please try again.')
  }
}
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style scoped>

/* Academic aesthetic – refined blue/gold palette */
.key-findings-container {
  position: relative;
  z-index: 2;
  max-width: 1400px;
  margin-top: -30px;
  padding: 30px 20px;
  font-family: 'Inter', system-ui, sans-serif;
  background: #f9f7f3; /* warm off‑white */
  min-height: 100vh;
  border-radius: 24px;
}

.dashboard-header {
  margin-bottom: 30px;
}
.main-title {
  font-size: 32px;
  font-weight: 700;
  color: #2c3e4f; /* deep slate */
  letter-spacing: -0.5px;
  border-left: 6px solid #c49a6c; /* warm gold */
  padding-left: 20px;
}
.subtitle {
  color: #5d707f;
  font-size: 15px;
  margin-top: 6px;
  padding-left: 26px;
}

/* Filter panel */
.filter-panel {
  background: #ffffff;
  border-radius: 16px;
  padding: 40px;
  margin-bottom: 18px;
  box-shadow: 0 4px 12px rgba(0, 20, 30, 0.08);
  border: 1px solid #e8dccc;
}
.filter-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 20px;
}

.filter-item label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #5d707f;
  text-transform: uppercase;
  margin-bottom: 6px;
  letter-spacing: 0.3px;
}

/* Customise vue-multiselect to match academic palette */
.multiselect-custom {
  min-height: 40px;
  border: 1px solid #d4c9bc;
  border-radius: 8px;
  background: #fefcf9;
}
.multiselect-custom .multiselect__tags {
  border: none;
  background: transparent;
  padding: 6px 30px 0 8px;
  min-height: 38px;
}
.multiselect-custom .multiselect__input,
.multiselect-custom .multiselect__single {
  background: transparent;
  font-size: 13px;
  color: #2c3e4f;
}
.multiselect-custom .multiselect__placeholder {
  color: #9aa9b5;
  font-size: 13px;
  padding-top: 2px;
}
.multiselect-custom .multiselect__tag {
  background: #c49a6c;
  color: white;
  font-size: 11px;
  font-weight: 500;
}
.multiselect-custom .multiselect__tag-icon:after {
  color: white;
}
.multiselect-custom .multiselect__option--selected {
  background: #f1ede8;
  font-weight: 500;
}
.multiselect-custom .multiselect__option--highlight {
  background: #c49a6c;
  color: white;
}
.multiselect-custom .multiselect__option--highlight:after {
  background: #c49a6c;
}
.multiselect-custom .multiselect__content-wrapper {
  border-color: #d4c9bc;
  border-radius: 0 0 8px 8px;
}
.selected-count {
  display: inline-block;
  margin-top: 5px;
  font-size: 11px;
  color: #c49a6c;
  font-weight: 500;
}

.year-range {
  display: flex;
  gap: 20px;
  align-items: flex-end;
  margin-bottom: 25px;
  background: #faf7f2;
  padding: 15px 20px;
  border-radius: 12px;
  border: 1px solid #e8dccc;
}
.range-item {
  flex: 1;
}
.range-item label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: #5d707f;
  text-transform: uppercase;
  margin-bottom: 6px;
}
.year-select {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d4c9bc;
  border-radius: 8px;
  background: #fefcf9;
  font-size: 14px;
  color: #2c3e4f;
}
.range-hint {
  font-size: 11px;
  color: #dc2626;
  margin-left: 10px;
  align-self: center;
}

.filter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}
.filter-header label {
  font-size: 12px;
  font-weight: 600;
  color: #5d707f;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.filter-actions-small {
  display: flex;
  gap: 6px;
  font-size: 11px;
}
.text-link {
  background: none;
  border: none;
  color: #c49a6c;
  cursor: pointer;
  padding: 0;
  font-weight: 500;
}
.text-link:hover {
  text-decoration: underline;
}
.sep {
  color: #d4c9bc;
}

.column-selector {
  margin-top: 25px;
  padding-top: 20px;
  border-top: 1px dashed #e8dccc;
}

.btn {
  padding: 10px 22px;
  border-radius: 30px;
  font-weight: 600;
  cursor: pointer;
  border: none;
  font-size: 14px;
  transition: 0.2s;
}
.btn-primary {
  background: #2c3e4f;
  color: white;
}
.btn-primary:hover {
  background: #1f2e3a;
}
.btn-outline {
  background: transparent;
  border: 1px solid #c49a6c;
  color: #c49a6c;
}
.btn-outline:hover {
  background: #f9f0e3;
}

/* Table styling – academic, clean */
.table-wrapper {
  background: white;
  border-radius: 16px;
  padding: 10px;
  box-shadow: 0 4px 12px rgba(0, 20, 30, 0.08);
  border: 1px solid #e8dccc;
  overflow-x: auto;
}
.findings-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.findings-table thead th {
  background: #f1ede8; /* soft beige */
  color: #2c3e4f;
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
  padding: 14px 8px;
  border-bottom: 2px solid #c49a6c;
  text-align: left;
}
.findings-table tbody td {
  padding: 12px 8px;
  border-bottom: 1px solid #e8dccc;
  color: #3a4e5e;
  vertical-align: top;
}
.findings-table tbody tr:hover {
  background: #faf7f2;
}

/* Pagination */
.pagination-footer {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 25px;
  padding-top: 15px;
  border-top: 1px solid #e8dccc;
}
.p-btn {
  background: white;
  border: 1px solid #c49a6c;
  color: #c49a6c;
  padding: 6px 16px;
  border-radius: 20px;
  font-weight: 500;
  cursor: pointer;
}
.p-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.p-info {
  font-size: 14px;
  color: #5d707f;
}
.loading-state, .no-data {
  text-align: center;
  padding: 60px;
  color: #7a8b99;
  font-style: italic;
  background: white;
  border-radius: 16px;
  border: 1px solid #e8dccc;
}

.researcher-name-filter {
  margin-bottom: 20px;
}
.researcher-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d4c9bc;
  border-radius: 8px;
  background: #fefcf9;
  font-size: 14px;
  color: #2c3e4f;
}
.researcher-input:focus {
  outline: none;
  border-color: #c49a6c;
}
</style>