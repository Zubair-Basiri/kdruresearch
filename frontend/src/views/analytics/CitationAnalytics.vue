<template>
  <div class="citation-analytics">
    <!-- Header -->
    <div class="page-header mt-3">
      <h1 class="page-title">Citation Analytics</h1>
      <p class="page-subtitle">Research Impact & Citation Performance</p>
    </div>

    <!-- Filter Panel -->
    <div class="filter-panel card p-3 mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Year</label>
          <input type="number" v-model="filters.year" class="form-control" placeholder="e.g. 2025" />
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

    <!-- KPIs -->
    <div v-if="!loading && data" class="kpi-grid mb-4">
      <div class="kpi-card" v-for="(kpi, key) in kpis" :key="key">
        <div class="kpi-label">{{ kpi.label }}</div>
        <div class="kpi-value">{{ kpi.value }}</div>
        <div class="kpi-trend" v-if="kpi.trend">
          <i :class="kpi.trendIcon"></i> {{ kpi.trend }}
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="row g-4 mb-4" v-if="!loading && data">
      <div class="col-md-8">
        <div class="card p-3">
          <h5 class="card-title">Citation Trend</h5>
          <div v-if="hasYearlyData">
            <apexchart type="area" height="300" :options="trendOptions" :series="trendSeries" />
          </div>
          <div v-else class="text-center text-muted py-4">No yearly data available</div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card p-3">
            <h5 class="card-title">Citation Distribution</h5>
            <p class="text-muted small">Shows how publications are spread across different citation ranges. Helps identify whether citations are concentrated in a few papers or widely distributed.</p>
            <div v-if="hasDistributionData">
            <apexchart type="bar" height="300" :options="distributionOptions" :series="distributionSeries" />
            </div>
            <div v-else class="text-center text-muted py-4">No distribution data</div>
        </div>
      </div>
    </div>

    <div class="row g-4 mb-4" v-if="!loading && data">
      <div class="col-md-6">
        <div class="card p-3">
          <h5 class="card-title">Citations by Faculty</h5>
          <div v-if="hasFacultyData">
            <apexchart type="bar" height="250" :options="facultyOptionsChart" :series="facultySeries" />
          </div>
          <div v-else class="text-center text-muted py-4">No faculty data</div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card p-3">
            <h5 class="card-title">Citation Impact (Publications vs Citations)</h5>
            <p class="text-muted small">Each bubble represents a faculty, showing its publication volume (X-axis) and total citations (Y-axis). Larger bubbles indicate higher impact relative to output.</p>
            <div v-if="hasFacultyData">
            <apexchart type="scatter" height="250" :options="scatterOptions" :series="scatterSeries" />
            </div>
            <div v-else class="text-center text-muted py-4">No impact data</div>
        </div>
      </div>
    </div>

    <!-- Top Researchers -->
    <div class="card p-3 mb-4" v-if="!loading && data && data.researcher_ranking && data.researcher_ranking.length">
      <h5 class="card-title">Top Researchers by Citations</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Rank</th>
              <th>Researcher</th>
              <th>Faculty</th>
              <th>Department</th>
              <th>Publications</th>
              <th>Citations</th>
              <th>Avg</th>
              <th>H-index</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="r in data.researcher_ranking" :key="r.rank">
              <td>{{ r.rank }}</td>
              <td>{{ r.researcher }}</td>
              <td>{{ r.faculty }}</td>
              <td>{{ r.department }}</td>
              <td>{{ r.publications }}</td>
              <td>{{ r.citations }}</td>
              <td>{{ r.avg }}</td>
              <td>{{ r.h_index }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Most Cited Publications -->
    <div class="card p-3 mb-4" v-if="!loading && data && data.top_publications && data.top_publications.length">
      <h5 class="card-title">Most Cited Publications</h5>
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Rank</th>
              <th>Title</th>
              <th>Author</th>
              <th>Year</th>
              <th>Faculty</th>
              <th>Citations</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in data.top_publications" :key="p.rank">
              <td>{{ p.rank }}</td>
              <td>{{ p.title }}</td>
              <td>{{ p.author }}</td>
              <td>{{ p.year }}</td>
              <td>{{ p.faculty }}</td>
              <td>{{ p.citations }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Key Findings -->
    <div class="card p-3" v-if="!loading && data && data.key_findings && data.key_findings.length">
      <h5 class="card-title">Key Findings</h5>
      <ul class="key-findings-list">
        <li v-for="(finding, idx) in data.key_findings" :key="idx">
          <i class="bi bi-check-circle-fill text-success me-2"></i> {{ finding }}
        </li>
      </ul>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2">Loading citation analytics...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-2" @click="fetchData">Retry</button>
    </div>

    <!-- Empty -->
    <div v-if="!loading && data && data.overview.total_publications === 0" class="alert alert-warning">
      No citation data available for the selected filters.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { loadAnalyticsFilters } from '@/services/analyticsFilters'

// State
const filters = ref({
  year: '',
  faculty: '',
  department: '',
  researcher: '',
  grade: '',
  publication_type: '',
  indexed: '',
  language: '',
})
const data = ref(null)
const loading = ref(false)
const error = ref(null)

// Filter options
const facultyOptions = ref([])
const departmentOptions = ref([])
const researcherOptions = ref([])
const gradeOptions = ref([])
const publicationTypeOptions = ref([])
const indexedOptions = ref([])
const languageOptions = ref([])

// KPIs
const kpis = computed(() => {
  if (!data.value) return {}
  const o = data.value.overview
  return {
    totalCitations: { label: 'Total Citations', value: o.total_citations.toLocaleString() },
    avgCitations: { label: 'Average per Paper', value: o.average_citations },
    citedPublications: { label: 'Cited Publications', value: o.cited_publications.toLocaleString() },
    uncitedPublications: { label: 'Uncited Publications', value: o.uncited_publications.toLocaleString() },
    hIndex: { label: 'H-index', value: o.h_index },
    citedPercent: { label: 'Cited %', value: o.cited_percentage + '%' },
  }
})

// Computed helpers
const hasYearlyData = computed(() => {
  if (!data.value) return false
  const trend = data.value.yearly_trend
  return trend && trend.length > 0 && trend.some(t => t.year !== null)
})
const hasDistributionData = computed(() => data.value && data.value.citation_distribution && data.value.citation_distribution.length > 0)
const hasFacultyData = computed(() => data.value && data.value.faculty_summary && data.value.faculty_summary.length > 0)

// Chart computed with safe fallbacks
const trendSeries = computed(() => {
  if (!hasYearlyData.value) return [{ name: 'Citations', data: [] }]
  // Filter out null years
  const trend = data.value.yearly_trend.filter(t => t.year !== null)
  return [{
    name: 'Citations',
    data: trend.map(t => ({ x: t.year, y: t.citations })),
  }]
})

const distributionSeries = computed(() => {
  if (!hasDistributionData.value) return [{ name: 'Publications', data: [] }]
  const dist = data.value.citation_distribution
  return [{
    name: 'Publications',
    data: dist.map(d => d.count),
  }]
})

const facultySeries = computed(() => {
  if (!hasFacultyData.value) return [{ name: 'Citations', data: [] }]
  const faculty = data.value.faculty_summary
  return [{
    name: 'Citations',
    data: faculty.map(f => ({ x: f.faculty_name, y: f.citations })),
  }]
})

const scatterSeries = computed(() => {
  if (!hasFacultyData.value) return [{ name: 'Faculty', data: [] }]
  const faculty = data.value.faculty_summary
  return [{
    name: 'Faculty',
    data: faculty.map(f => ({ x: f.publications, y: f.citations })),
  }]
})

// Chart options (static)
const trendOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { title: { text: 'Year' } },
  yaxis: { title: { text: 'Citations' } },
  tooltip: { y: { formatter: val => val.toLocaleString() } },
  colors: ['#3b82f6'],
  fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.7, opacityTo: 0.1 } },
})

const distributionOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { title: { text: 'Citation Range' } },
  yaxis: { title: { text: 'Publications' } },
  colors: ['#10b981'],
  plotOptions: { bar: { horizontal: false, columnWidth: '60%' } },
})

const facultyOptionsChart = ref({
  chart: { toolbar: { show: false } },
  xaxis: { title: { text: 'Citations' } },
  yaxis: { title: { text: 'Faculty' } },
  plotOptions: { bar: { horizontal: true } },
  colors: ['#8b5cf6'],
})

const scatterOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { title: { text: 'Publications' } },
  yaxis: { title: { text: 'Citations' } },
})

// Methods
const fetchFilterOptions = async () => {
  const options = await loadAnalyticsFilters()
  facultyOptions.value = options.faculties
  departmentOptions.value = options.departments
  researcherOptions.value = options.researchers
  gradeOptions.value = options.metadata.grades || []
  publicationTypeOptions.value = options.metadata.publication_types || []
  indexedOptions.value = options.metadata.indexes || []
  languageOptions.value = options.metadata.languages || ['Pashto', 'Dari', 'English']
}

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {}
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key]) params[key] = filters.value[key]
    })
    const response = await api.get('/analytics/citations', { params })
    data.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load citation analytics.'
    console.error(err)
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
  const url = `${api.defaults.baseURL}/api/analytics/citation/preview?${params.toString()}`;
  window.open(url, '_blank');
};

onMounted(async () => {
  await fetchFilterOptions()
  await fetchData()
})
</script>

<style scoped>
.citation-analytics {
  padding: 20px;
}
.page-title {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 0;
}
.page-subtitle {
  color: #6b7280;
}
.filter-panel {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 16px;
}
.kpi-card {
  background: #fff;
  border-radius: 12px;
  padding: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  text-align: center;
}
.kpi-label {
  font-size: 0.8rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.kpi-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: #111827;
}
.kpi-trend {
  font-size: 0.8rem;
  color: #10b981;
}
.card-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 16px;
}
.key-findings-list {
  list-style: none;
  padding: 0;
}
.key-findings-list li {
  padding: 6px 0;
  border-bottom: 1px solid #f3f4f6;
}
@media (max-width: 768px) {
  .kpi-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
