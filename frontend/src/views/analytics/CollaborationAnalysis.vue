<template>
  <div class="collaboration-analysis">
    <!-- Header -->
    <div class="page-header mt-3">
      <h1 class="page-title">Collaboration Analysis</h1>
      <p class="page-subtitle">Research collaboration patterns, trends, and impact</p>
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
          <label class="form-label">Year</label>
          <input type="number" v-model="filters.year" class="form-control" placeholder="e.g. 2025" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Collaboration Type</label>
          <select v-model="filters.collaboration_type" class="form-select">
            <option value="">All</option>
            <option v-for="type in collaborationTypeOptions" :key="type" :value="type">{{ type }}</option>
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

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2">Loading collaboration analytics...</p>
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
        <div class="kpi-card" v-for="(kpi, key) in kpis" :key="key">
          <div class="kpi-label">{{ kpi.label }}</div>
          <div class="kpi-value">{{ kpi.value }}</div>
        </div>
      </div>

      <!-- Collaboration Overview -->
      <div class="card p-3 mb-4">
        <h5 class="card-title">Collaboration Overview</h5>
        <div class="row">
          <div class="col-md-3">
            <span class="text-muted">Collaborative Publications</span>
            <h4>{{ data.summary.collaborative_publications }}</h4>
          </div>
          <div class="col-md-3">
            <span class="text-muted">Non-Collaborative</span>
            <h4>{{ data.summary.non_collaborative_publications }}</h4>
          </div>
          <div class="col-md-3">
            <span class="text-muted">Collaboration Rate</span>
            <h4>{{ data.summary.collaboration_rate }}%</h4>
          </div>
          <div class="col-md-3">
            <span class="text-muted">Avg Citations (Collaborative)</span>
            <h4>{{ data.summary.average_collaborative_citations }}</h4>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="row g-4 mb-4">
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Collaboration Trend</h5>
            <apexchart type="line" height="300" :options="trendOptions" :series="trendSeries" />
          </div>
        </div>
        <div class="col-md-6">
          <div class="card p-3">
            <h5 class="card-title">Collaboration Types</h5>
            <apexchart type="donut" height="300" :options="donutOptions" :series="donutSeries" />
          </div>
        </div>
      </div>

      <!-- University Comparison (ministry only) -->
      <div v-if="authStore.isMinistryAuthority && data.university_comparison && data.university_comparison.length" class="card p-3 mb-4">
        <h5 class="card-title">University Comparison</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>University</th>
                <th>Publications</th>
                <th>Collaborative</th>
                <th>Rate</th>
                <th>Citations</th>
                <th>Avg Citations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="u in data.university_comparison" :key="u.university_id">
                <td>{{ u.university_name }}</td>
                <td>{{ u.publications }}</td>
                <td>{{ u.collaborative }}</td>
                <td>{{ u.rate }}%</td>
                <td>{{ u.citations }}</td>
                <td>{{ u.avg_citations }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Faculty Collaboration -->
      <div v-if="data.faculty_collaboration && data.faculty_collaboration.length" class="card p-3 mb-4">
        <h5 class="card-title">Faculty Collaboration</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Faculty</th>
                <th>Publications</th>
                <th>Collaborative</th>
                <th>Rate</th>
                <th>Researchers</th>
                <th>Citations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in data.faculty_collaboration" :key="f.entity_id">
                <td>{{ f.entity_name }}</td>
                <td>{{ f.publications }}</td>
                <td>{{ f.collaborative }}</td>
                <td>{{ f.rate }}%</td>
                <td>{{ f.researchers }}</td>
                <td>{{ f.citations }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Researcher Collaboration -->
      <div v-if="data.researcher_collaboration && data.researcher_collaboration.length" class="card p-3 mb-4">
        <h5 class="card-title">Researcher Collaboration</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Researcher</th>
                <th>Publications</th>
                <th>Collaborative</th>
                <th>Rate</th>
                <th>Citations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in data.researcher_collaboration" :key="r.researcher_id">
                <td>{{ r.researcher_name }}</td>
                <td>{{ r.publications }}</td>
                <td>{{ r.collaborative }}</td>
                <td>{{ r.rate }}%</td>
                <td>{{ r.citations }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Research Area Collaboration -->
      <div v-if="data.research_area_collaboration && data.research_area_collaboration.length" class="card p-3 mb-4">
        <h5 class="card-title">Research Area Collaboration</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Research Area</th>
                <th>Publications</th>
                <th>Collaborative</th>
                <th>Rate</th>
                <th>Citations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="area in data.research_area_collaboration" :key="area.area">
                <td>{{ area.area }}</td>
                <td>{{ area.publications }}</td>
                <td>{{ area.collaborative }}</td>
                <td>{{ area.rate }}%</td>
                <td>{{ area.citations }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Top Collaborative Publications -->
      <div v-if="data.top_publications && data.top_publications.length" class="card p-3 mb-4">
        <h5 class="card-title">Top Collaborative Publications</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Title</th>
                <th>Year</th>
                <th>Faculty</th>
                <th>Researcher</th>
                <th>Collaboration Type</th>
                <th>Citations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in data.top_publications" :key="p.id">
                <td>{{ p.title }}</td>
                <td>{{ p.year }}</td>
                <td>{{ p.faculty }}</td>
                <td>{{ p.researcher }}</td>
                <td>{{ p.collaboration_type }}</td>
                <td>{{ p.citations }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Key Findings -->
      <div v-if="data.key_findings && data.key_findings.length" class="card p-3">
        <h5 class="card-title">Key Findings</h5>
        <ul class="insights-list">
          <li v-for="(insight, idx) in data.key_findings" :key="idx">
            <i class="bi bi-lightbulb-fill text-warning me-2"></i> {{ insight }}
          </li>
        </ul>
      </div>
    </template>

    <!-- Empty -->
    <div v-if="!loading && data && data.summary.total_publications === 0" class="alert alert-warning">
      No collaboration data available for the selected filters.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { defineOptions } from 'vue'

defineOptions({ name: 'CollaborationAnalysisPage' })

const authStore = useAuthStore()

const filters = ref({
  university: '',
  faculty: '',
  department: '',
  researcher: '',
  year: '',
  collaboration_type: '',
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
const collaborationTypeOptions = ref([])
const gradeOptions = ref([])
const publicationTypeOptions = ref([])
const indexedOptions = ref([])
const languageOptions = ref([])

// KPIs
const kpis = computed(() => {
  if (!data.value) return {}
  const s = data.value.summary
  return {
    totalPubs: { label: 'Total Publications', value: s.total_publications },
    collabPubs: { label: 'Collaborative Publications', value: s.collaborative_publications },
    rate: { label: 'Collaboration Rate', value: s.collaboration_rate + '%' },
    totalCitations: { label: 'Total Citations', value: s.total_citations.toLocaleString() },
    avgCollab: { label: 'Avg Citations (Collab)', value: s.average_collaborative_citations },
    collabResearchers: { label: 'Collaborating Researchers', value: s.collaborative_researchers },
  }
})

// Chart data
const trendSeries = computed(() => {
  if (!data.value) return []
  const trend = data.value.trend
  return [
    {
      name: 'Collaborative Publications',
      data: trend.map(t => ({ x: t.year, y: t.collaborative })),
    },
    {
      name: 'Total Publications',
      data: trend.map(t => ({ x: t.year, y: t.total })),
    },
  ]
})

const trendOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { title: { text: 'Year' } },
  yaxis: { title: { text: 'Publications' } },
  colors: ['#10b981', '#3b82f6'],
  stroke: { curve: 'smooth' },
})

const donutSeries = computed(() => {
  if (!data.value) return []
  return data.value.collaboration_types.map(t => t.count)
})

const donutOptions = ref({
  chart: { toolbar: { show: false } },
  labels: [],
  legend: { position: 'bottom' },
  tooltip: { y: { formatter: val => val + ' publications' } },
})

// Methods
const fetchFilterOptions = async () => {
  try {
    const [faculties, departments, lecturers, filterData] = await Promise.all([
      api.get('/faculties'),
      api.get('/departments'),
      api.get('/lecturers-for-dropdown'),
      api.get('/key-findings/filters'),
    ])
    facultyOptions.value = faculties.data
    departmentOptions.value = departments.data
    researcherOptions.value = lecturers.data.data || lecturers.data
    const fd = filterData.data
    gradeOptions.value = fd.grades || []
    publicationTypeOptions.value = fd.publication_types || []
    indexedOptions.value = fd.indexes || []
    languageOptions.value = fd.languages || ['Pashto', 'Dari', 'English']

    // Fetch collaboration types from the existing collaboration field
    const collabRes = await api.get('/collaboration-types') // We'll add this endpoint
    collaborationTypeOptions.value = collabRes.data || []

    if (authStore.isMinistryAuthority) {
      const uniRes = await api.get('/universities')
      universityOptions.value = uniRes.data
    }
  } catch (err) {
    console.error('Failed to load filter options', err)
  }
}

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {}
    Object.keys(filters.value).forEach(key => {
      if (filters.value[key]) params[key] = filters.value[key]
    })
    const response = await api.get('/analytics/collaboration', { params })
    data.value = response.data

    // Update chart labels
    if (data.value) {
      donutOptions.value.labels = data.value.collaboration_types.map(t => t.type)
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load collaboration analytics.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => fetchData()
const resetFilters = () => {
  Object.keys(filters.value).forEach(key => filters.value[key] = '')
  fetchData()
}

// Additional endpoint for collaboration types
// const fetchCollaborationTypes = async () => {
//   try {
//     const res = await api.get('/collaboration-types')
//     collaborationTypeOptions.value = res.data
//   } catch (err) {
//     console.error('Failed to load collaboration types', err)
//   }
// }

const exportPdf = () => {
  const params = new URLSearchParams();
  Object.keys(filters.value).forEach(key => {
    if (filters.value[key]) params.append(key, filters.value[key]);
  });
  const url = `${api.defaults.baseURL}/api/analytics/collaboration/preview?${params.toString()}`;
  window.open(url, '_blank');
};

onMounted(async () => {
  await fetchFilterOptions()
  await fetchData()
})
</script>

<style scoped>
.collaboration-analysis { padding: 20px; }
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