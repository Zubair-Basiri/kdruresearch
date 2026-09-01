<template>
  <div class="dashboard">
    <!-- LEFT FILTER PANEL -->
    <aside class="filter-panel">
      <h3>Filters</h3>
      <div v-if="filtersLoading" class="filter-loading">Loading filters...</div>
      <div v-else-if="filtersError" class="filter-error">{{ filtersError }}</div>
      <template v-else>
        <div class="filter-group">
          <label>Faculty</label>

          <!-- ========== MINISTRY AUTHORITY: Searchable Multiselect ========== -->
          <template v-if="authStore.isMinistryAuthority">
            <Multiselect
              v-model="selectedFaculty"
              :options="facultyOptions"
              :searchable="true"
              :allow-empty="false"
              :show-labels="false"
              :close-on-select="true"
              :clear-on-select="false"
              :preserve-search="true"
              placeholder="All Faculties"
              label="label"
              track-by="id"
              :max-height="200"
              :options-limit="50"
              :internal-search="true"
              class="faculty-multiselect"
              @select="onFacultySelect"
            >
              <template #singleLabel="{ option }">
                <span>{{ option.label }}</span>
              </template>
              <template #option="{ option }">
                <span>{{ option.label }}</span>
              </template>
            </Multiselect>
          </template>

          <!-- ========== ALL OTHER ROLES: Native select ========== -->
          <select v-else v-model="filters.faculty_id" class="compact-select">
            <option value="">All Faculties</option>
            <option
              v-for="f in faculties"
              :key="f.id"
              :value="f.id"
            >
              {{ f.facultyname }}
            </option>
          </select>
        </div>

        <div class="filter-group">
          <label>Year</label>
          <select v-model="filters.year" class="compact-select">
            <option value="">All (last 3 years)</option>
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <button class="btn-primary apply-btn" @click="applyFilter">Apply</button>
      </template>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content" v-if="!loading">
      <!-- KPI ROW -->
      <div class="kpi-row">
        <KpiMiniCard
          v-for="(value, key) in kpis"
          :key="key"
          :title="formatLabel(key)"
          :value="formatValue(key, value)"
          :color="kpiColors[key] || '#6b7280'"
        />
      </div>

      <!-- ALL CHARTS -->
      <div class="charts-grid">
        <ChartCard
          v-for="metric in chartMetrics"
          :key="metric.key"
          :title="metric.label"
        >
          <apexchart
            type="bar"
            height="350"
            :series="getChartSeries(metric.key)"
            :options="getChartOptions(metric.key)"
          />
        </ChartCard>
      </div>
    </main>
    <div v-else class="loading">
      <div class="spinner"></div>
      Loading...
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, shallowRef } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import Multiselect from 'vue-multiselect'
import KpiMiniCard from '@/components/facultyCards/KpiMiniCard.vue'
import ChartCard from '@/components/facultyCards/ChartCard.vue'

const authStore = useAuthStore()

// Simple debounce
const debounce = (fn, delay) => {
  let timeout
  return (...args) => {
    clearTimeout(timeout)
    timeout = setTimeout(() => fn(...args), delay)
  }
}

// ===== STATE =====
const filters = ref({ faculty_id: '', year: '' })
const faculties = ref([])
const years = ref([])
const dashboardData = shallowRef(null)
const loading = ref(false)
const filtersLoading = ref(true)
const filtersError = ref(null)

// ===== MULTISELECT STATE (ministry_authority only) =====
const selectedFaculty = ref(null)
const facultyOptions = ref([])

// ===== ACADEMIC COLOR PALETTE =====
const kpiColors = {
  publications: '#2c3e50',
  funding: '#27ae60',
  researchers: '#8e44ad',
  q1: '#e67e22',
  q2: '#f39c12',
  q3: '#f5b041',
  q4: '#f7dc6f',
  peer_reviewed: '#16a085',
  books_authored: '#6a4e9a',
  books_translated: '#9b59b6',
  non_indexed_national_conferences: '#d35400',
  national_publications: '#2980b9',
  international_indexed_conferences: '#1abc9c',
  international_non_indexed_conferences: '#3498db',
  books_published: '#9b59b6',
  books_published_non_academic: '#bdc3c7',
  h_index: '#34495e',
  total_citation: '#e74c3c',
  first_authors: '#f1c40f',
  second_authors: '#e67e22',
  third_authors: '#e67e22',
  other_authors: '#95a5a6',
  total_case_studies: '#2ecc71',
  total_research_reports: '#3498db'
}

// ===== LIST OF METRICS =====
const chartMetrics = [
  { key: 'publications', label: 'Publications per Faculty' },
  { key: 'funding', label: 'Funding per Faculty' },
  { key: 'researchers', label: 'Researchers per Faculty' },
  { key: 'q1', label: 'Q1 Indexed Papers' },
  { key: 'q2', label: 'Q2 Indexed Papers' },
  { key: 'q3', label: 'Q3 Indexed Papers' },
  { key: 'q4', label: 'Q4 Indexed Papers' },
  { key: 'peer_reviewed', label: 'Peer Reviewed Articles' },
  { key: 'books_authored', label: 'Books Authored' },
  { key: 'books_translated', label: 'Books Translated' },
  { key: 'non_indexed_national_conferences', label: 'Non‑indexed National Conferences' },
  { key: 'national_publications', label: 'National Publications' },
  { key: 'international_indexed_conferences', label: 'International Indexed Conferences' },
  { key: 'international_non_indexed_conferences', label: 'International Non‑indexed Conferences' },
  { key: 'books_published', label: 'Books Published (Academic)' },
  { key: 'books_published_non_academic', label: 'Books Published (Non‑academic)' },
  { key: 'total_citation', label: 'Total Citations' },
  { key: 'first_authors', label: '1st Authors' },
  { key: 'second_authors', label: '2nd Authors' },
  { key: 'third_authors', label: '3rd Authors' },
  { key: 'other_authors', label: 'Other Authors' },
  { key: 'total_case_studies', label: 'Total Case Studies' },
  { key: 'total_research_reports', label: 'Total Research Reports' }
]

// ===== FETCH FILTERS =====
const fetchFilters = async () => {
  filtersLoading.value = true
  filtersError.value = null
  try {
    const { data } = await api.get('/dashboard/filters')
    faculties.value = data.faculties || []
    years.value = data.years || []

    // Build faculty options for Multiselect (ministry_authority only)
    if (authStore.isMinistryAuthority) {
      const options = [
        { id: '', label: 'All Faculties' },
        ...faculties.value.map(f => ({
          id: f.id,
          label: `${f.facultyname} — ${f.university_name || ''}`
        }))
      ]
      facultyOptions.value = options

      // Set default selection to "All Faculties"
      selectedFaculty.value = options[0]
    }
  } catch (error) {
    console.error('Failed to load filters', error)
    filtersError.value = 'Could not load filters. Please refresh.'
  } finally {
    filtersLoading.value = false
  }
}

// ===== FETCH DASHBOARD DATA =====
const fetchDashboardData = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/dashboard/data', {
      params: {
        faculty_id: filters.value.faculty_id || undefined,
        year: filters.value.year || undefined,
      }
    })
    dashboardData.value = data
  } catch (error) {
    console.error('Failed to load dashboard data', error)
  } finally {
    loading.value = false
  }
}

// ===== DEBOUNCED APPLY =====
const applyFilter = debounce(() => {
  fetchDashboardData()
}, 300)

// ===== MULTISELECT HANDLER (ministry_authority) =====
const onFacultySelect = (faculty) => {
  // Update the filter value without triggering any API call
  filters.value.faculty_id = faculty.id || ''
  // Do NOT call fetchDashboardData() – wait for Apply button
}

// ===== COMPUTED KPIS =====
const kpis = computed(() => dashboardData.value?.kpis || {})

// ===== FORMATTERS =====
const formatLabel = (key) => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())
}

const formatValue = (key, value) => {
  if (value === undefined || value === null) return '0'
  if (key === 'funding') return '$' + value.toLocaleString()
  return value.toLocaleString()
}

// ===== CHART HELPERS =====
const chartData = computed(() => dashboardData.value?.chart_data || {})

const getChartSeries = (metricKey) => {
  return chartData.value[metricKey]?.series || []
}

const getChartCategories = (metricKey) => {
  return chartData.value[metricKey]?.categories || []
}

// ===== BASE CHART OPTIONS =====
const baseChartOptions = {
  chart: { toolbar: { show: false } },
  colors: ['#3498db', '#2ecc71', '#e74c3c', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22'],
  plotOptions: {
    bar: { horizontal: false, columnWidth: '60%', borderRadius: 4 }
  },
  dataLabels: { enabled: false },
  legend: { position: 'top', horizontalAlign: 'center' },
  tooltip: { y: { formatter: (val) => val.toLocaleString() } },
  xaxis: { title: { text: 'Faculty' } },
  yaxis: { title: { text: 'Count' } }
}

const getChartOptions = (metricKey) => ({
  ...baseChartOptions,
  xaxis: {
    ...baseChartOptions.xaxis,
    categories: getChartCategories(metricKey)
  }
})

// ===== ON MOUNT =====
onMounted(async () => {
  await fetchFilters()

  // For ministry_authority, default to "All Faculties" (empty filter)
  if (authStore.isMinistryAuthority) {
    filters.value.faculty_id = ''
    // selectedFaculty already set in fetchFilters
  }

  await fetchDashboardData()
})
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<style scoped>
.dashboard {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 16px;
  padding: 16px;
  background: #f5f7fa;
  min-height: 100vh;
}

.filter-panel {
  background: #fff;
  padding: 16px;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
  position: sticky;
  top: 20px;
  max-height: 400px;
  overflow-y: auto;
  overflow-x: hidden; /* Prevent horizontal scroll */
}

.filter-panel h3 {
  margin-top: 0;
  margin-bottom: 16px;
  font-size: 1.1rem;
  color: #2c3e50;
  border-bottom: 1px solid #ecf0f1;
  padding-bottom: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  margin-bottom: 12px;
  font-size: 0.9rem;
}

.filter-group label {
  font-weight: 600;
  margin-bottom: 4px;
  color: #34495e;
}

.filter-loading,
.filter-error {
  padding: 10px;
  text-align: center;
  color: #7f8c8d;
}
.filter-error {
  color: #e74c3c;
}

.compact-select {
  height: 36px;
  font-size: 0.9rem;
  border-radius: 8px;
  border: 1px solid #d0d7de;
  padding: 0 8px;
  background: white;
  width: 100%;
  box-sizing: border-box;
}

.apply-btn {
  width: 100%;
  padding: 10px;
  background: #2c3e50;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.apply-btn:hover {
  background: #1e2b37;
}

.content {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.kpi-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 12px;
  background: transparent;
}

.charts-grid {
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
}

.loading {
  grid-column: 2;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  font-size: 1.2rem;
  color: #7f8c8d;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 3px solid #ccc;
  border-top-color: #2c3e50;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ===== MULTISELECT CUSTOMIZATION (matches .compact-select) ===== */
.faculty-multiselect {
  width: 100%;
  min-width: 0;
  position: relative; /* Anchor the dropdown */
  box-sizing: inherit;
  font-size: 12px;
}

.faculty-multiselect .multiselect__tags {
  border-radius: 8px;
  border: 1px solid #d0d7de;
  background: white;
  padding: 4px 30px 0 8px;
  min-height: 36px;
  font-size: 0.9rem;
  width: 100%;
  box-sizing: border-box;
}

.faculty-multiselect .multiselect__input,
.faculty-multiselect .multiselect__single {
  font-size: 0.9rem;
  color: #2c3e50;
}

.faculty-multiselect .multiselect__placeholder {
  color: #7f8c8d;
  font-size: 0.9rem;
  padding-top: 2px;
}

.faculty-multiselect .multiselect__select {
  height: 34px;
}

/* Critical fix: dropdown popup stays inside the container */
.faculty-multiselect .multiselect__content-wrapper {
  border-radius: 0 0 8px 8px;
  border-color: #d0d7de;
  max-height: 200px;
  overflow-y: auto;
  width: 100% !important;
  max-width: 100% !important;
  box-sizing: border-box;
  left: 0 !important;
  right: 0 !important;
}

.faculty-multiselect .multiselect__content {
  max-width: 100%;
}

.faculty-multiselect .multiselect__option--highlight {
  background: #2c3e50;
}

.faculty-multiselect .multiselect__option--selected {
  background: #ecf0f1;
  font-weight: 600;
}

@media (max-width: 1200px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .dashboard {
    grid-template-columns: 1fr;
  }
  .filter-panel {
    position: static;
    max-height: none;
  }
}
</style>