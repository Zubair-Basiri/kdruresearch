<template>
  <div class="research-area-analytics">
    <!-- Header -->
    <div class="page-header mt-5">
      <h1 class="page-title">Research Area Analytics</h1>
      <p class="page-subtitle">Analyze research areas, output, impact, and trends</p>
    </div>

    <!-- Filters -->
    <div class="filter-panel card p-3 mb-4">
      <div class="row g-3 align-items-end">
        <!-- University (only for ministry_authority) -->
        <div v-if="authStore.isMinistryAuthority" class="col-md-3">
          <label class="form-label">University</label>
          <select v-model="filters.university" class="form-select">
            <option value="">All Universities</option>
            <option v-for="u in universityOptions" :key="u.id" :value="u.id">{{ u.name }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Faculty</label>
          <select v-model="filters.faculty" class="form-select">
            <option value="">All Faculties</option>
            <option v-for="f in facultyOptions" :key="f.id" :value="f.id">{{ f.facultyname }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Department</label>
          <select v-model="filters.department" class="form-select">
            <option value="">All Departments</option>
            <option v-for="d in departmentOptions" :key="d.id" :value="d.id">{{ d.deptname }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Researcher</label>
          <select v-model="filters.researcher" class="form-select">
            <option value="">All Researchers</option>
            <option v-for="r in researcherOptions" :key="r.id" :value="r.id">{{ r.lecturername }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Research Area</label>
          <select v-model="filters.research_area" class="form-select">
            <option value="">All Areas</option>
            <option v-for="area in areaOptions" :key="area" :value="area">{{ area }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Year</label>
          <input type="number" v-model="filters.year" class="form-control" placeholder="e.g. 2025" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Grade</label>
          <select v-model="filters.grade" class="form-select">
            <option value="">All Grades</option>
            <option v-for="g in gradeOptions" :key="g" :value="g">{{ g }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Publication Type</label>
          <select v-model="filters.publication_type" class="form-select">
            <option value="">All Types</option>
            <option v-for="pt in publicationTypeOptions" :key="pt" :value="pt">{{ pt }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Indexed</label>
          <select v-model="filters.indexed" class="form-select">
            <option value="">All</option>
            <option v-for="idx in indexedOptions" :key="idx" :value="idx">{{ idx }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Language</label>
          <select v-model="filters.language" class="form-select">
            <option value="">All Languages</option>
            <option v-for="lang in languageOptions" :key="lang" :value="lang">{{ lang }}</option>
          </select>
        </div>
        <div class="col-md-auto">
          <button class="btn btn-primary me-2" @click="applyFilters">Apply</button>
          <button class="btn btn-outline-secondary me-2" @click="resetFilters">Reset</button>
          <button class="btn btn-success" @click="exportPdf">Export PDF</button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2">Loading research area analytics...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-2" @click="fetchData">Retry</button>
    </div>

    <!-- Data -->
    <template v-if="!loading && data">
      <!-- KPIs -->
      <div class="kpi-grid mb-4">
        <div v-for="(kpi, key) in kpis" :key="key" class="kpi-card">
          <div class="kpi-label">{{ kpi.label }}</div>
          <div class="kpi-value">{{ kpi.value }}</div>
        </div>
      </div>

      <!-- Area Overview Table -->
      <div class="card p-3 mb-4">
        <h5 class="card-title">Research Area Overview</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Research Area</th>
                <th>Publications</th>
                <th>Researchers</th>
                <th>Citations</th>
                <th>Avg Citations</th>
                <th>Q1</th>
                <th>Q1 %</th>
                <th>Share %</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data.area_overview" :key="item.area">
                <td>{{ item.area }}</td>
                <td>{{ item.publications }}</td>
                <td>{{ item.researchers }}</td>
                <td>{{ item.citations }}</td>
                <td>{{ item.avg_citations }}</td>
                <td>{{ item.q1 }}</td>
                <td>{{ item.q1_percent }}%</td>
                <td>{{ item.share_percent }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Charts: Distribution, Top Areas, Trend, Faculty Breakdown -->
      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Research Area Distribution</h5>
            <apexchart type="pie" height="300" :options="distributionOptions" :series="distributionSeries" />
          </div>
        </div>
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Top Research Areas by Publications</h5>
            <apexchart type="bar" height="300" :options="topPubOptions" :series="topPubSeries" />
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Top Research Areas by Citations</h5>
            <apexchart type="bar" height="300" :options="topCitOptions" :series="topCitSeries" />
          </div>
        </div>
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Research Area Trend</h5>
            <apexchart type="line" height="300" :options="trendOptions" :series="trendSeries" />
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Research Areas by Faculty (Heatmap)</h5>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead>
                  <tr>
                    <th>Faculty</th>
                    <th v-for="area in facultyBreakdownAreas" :key="area">{{ area }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(fac, name) in data.faculty_breakdown" :key="name">
                    <td>{{ name }}</td>
                    <td v-for="area in facultyBreakdownAreas" :key="area">
                      {{ fac[area] || 0 }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Key Findings</h5>
            <ul class="insights-list">
              <li v-for="(insight, idx) in data.insights" :key="idx">
                <i class="bi bi-lightbulb-fill text-warning me-2"></i> {{ insight }}
              </li>
            </ul>
          </div>
        </div>
      </div>
    </template>

    <!-- Empty -->
    <div v-if="!loading && data && data.kpis.total_publications === 0" class="alert alert-warning">
      No research area data available for the selected filters.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { loadAnalyticsFilters } from '@/services/analyticsFilters'
import { useAuthStore } from '@/stores/auth'
import { defineOptions } from 'vue'

defineOptions({ name: 'ResearchAreaAnalyticsPage' })

const authStore = useAuthStore()

// Filters
const filters = ref({
  university: '',
  faculty: '',
  department: '',
  researcher: '',
  research_area: '',
  year: '',
  grade: '',
  publication_type: '',
  indexed: '',
  language: '',
})

const data = ref(null)
const loading = ref(false)
const error = ref(null)

// Options for dropdowns
const universityOptions = ref([])
const facultyOptions = ref([])
const departmentOptions = ref([])
const researcherOptions = ref([])
const areaOptions = ref([])
const gradeOptions = ref([])
const publicationTypeOptions = ref([])
const indexedOptions = ref([])
const languageOptions = ref([])

// KPIs
const kpis = computed(() => {
  if (!data.value) return {}
  const k = data.value.kpis
  return {
    totalAreas: { label: 'Total Research Areas', value: k.total_areas },
    publications: { label: 'Publications', value: k.total_publications },
    researchers: { label: 'Researchers', value: k.total_researchers },
    citations: { label: 'Citations', value: k.total_citations.toLocaleString() },
    avgCitations: { label: 'Avg Citations', value: k.avg_citations },
    q1: { label: 'Q1 Publications', value: k.q1_publications },
    citedPercent: { label: 'Cited %', value: k.cited_percent + '%' },
  }
})

// Chart computed
const distributionSeries = computed(() => {
  if (!data.value) return []
  return data.value.distribution.map(d => d.publications)
})

const distributionOptions = ref({
  chart: { toolbar: { show: false } },
  labels: [],
  legend: { position: 'bottom' },
  tooltip: { y: { formatter: val => val + ' publications' } },
})

const topPubSeries = computed(() => {
  if (!data.value) return []
  const top = data.value.top_by_publications
  return [{
    name: 'Publications',
    data: top.map(d => d.value),
  }]
})

const topPubOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { categories: [] },
  plotOptions: { bar: { horizontal: false } },
})

const topCitSeries = computed(() => {
  if (!data.value) return []
  const top = data.value.top_by_citations
  return [{
    name: 'Citations',
    data: top.map(d => d.value),
  }]
})

const topCitOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { categories: [] },
  plotOptions: { bar: { horizontal: false } },
})

const trendSeries = computed(() => {
  if (!data.value) return []
  const trend = data.value.trend
  const areas = Object.keys(trend[0] || {}).filter(k => k !== 'year')
  return areas.map(area => ({
    name: area,
    data: trend.map(row => row[area] || 0),
  }))
})

const trendOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { categories: [] },
  stroke: { curve: 'smooth' },
})

const facultyBreakdownAreas = computed(() => {
  if (!data.value) return []
  const fac = data.value.faculty_breakdown
  const areas = new Set()
  Object.values(fac).forEach(f => Object.keys(f).forEach(a => areas.add(a)))
  return Array.from(areas)
})

// Methods
const fetchFilterOptions = async () => {
  const options = await loadAnalyticsFilters({
    researchAreas: true,
    universities: authStore.isMinistryAuthority,
  })
  facultyOptions.value = options.faculties
  departmentOptions.value = options.departments
  researcherOptions.value = options.researchers
  gradeOptions.value = options.metadata.grades || []
  publicationTypeOptions.value = options.metadata.publication_types || []
  indexedOptions.value = options.metadata.indexes || []
  languageOptions.value = options.metadata.languages || ['Pashto', 'Dari', 'English']
  universityOptions.value = options.universities
  areaOptions.value = options.researchAreas
}

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {}
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key]) params[key] = filters.value[key]
    })
    const response = await api.get('/analytics/research-areas', { params })
    data.value = response.data

    // Update chart options with dynamic categories/labels
    if (data.value) {
      distributionOptions.value.labels = data.value.distribution.map(d => d.area)
      topPubOptions.value.xaxis.categories = data.value.top_by_publications.map(d => d.area)
      topCitOptions.value.xaxis.categories = data.value.top_by_citations.map(d => d.area)
      trendOptions.value.xaxis.categories = data.value.trend.map(d => d.year)
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load research area analytics.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => fetchData()
const resetFilters = () => {
  Object.keys(filters.value).forEach(key => filters.value[key] = '')
  fetchData()
}

const exportPdf = () => {
  const params = new URLSearchParams();
  Object.keys(filters.value).forEach(key => {
    if (filters.value[key]) params.append(key, filters.value[key]);
  });
  const url = `${api.defaults.baseURL}/api/analytics/research-areas/preview?${params.toString()}`;
  window.open(url, '_blank');
};

onMounted(async () => {
  await fetchFilterOptions()
  await fetchData()
})
</script>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>
<style scoped>
.benchmarking { padding: 20px; }
.page-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 0; }
.page-subtitle { color: #6b7280; }
.config-panel { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; }
.kpi-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center; }
.kpi-label { font-size: 0.8rem; color: #6b7280; text-transform: uppercase; }
.kpi-value { font-size: 1.6rem; font-weight: 700; color: #111827; }
.insights-list { list-style: none; padding: 0; }
.insights-list li { padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
.multiselect-custom .multiselect__tags { border-radius: 8px; border-color: #d0d7de; min-height: 36px; }
@media (max-width: 768px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
</style>
