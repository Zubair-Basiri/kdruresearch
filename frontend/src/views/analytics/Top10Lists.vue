<template>
  <div class="top10-lists">
    <!-- Header -->
    <div class="page-header mt-3">
      <h1 class="page-title">Top 10 Lists</h1>
      <p class="page-subtitle">Top-performing researchers, publications, faculties, departments, and research areas</p>
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
          <label class="form-label">Category</label>
          <select v-model="category" class="form-select" @change="onCategoryChange">
            <option v-for="cat in categoryOptions" :key="cat" :value="cat">{{ capitalize(cat) }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Rank By</label>
          <select v-model="metric" class="form-select">
            <option v-for="(label, key) in metricOptions" :key="key" :value="key">{{ label }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Limit</label>
          <select v-model="limit" class="form-select">
            <option v-for="l in [10, 20, 50, 100]" :key="l" :value="l">{{ l }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Minimum Publications</label>
          <input type="number" v-model="minPublications" class="form-control" min="0" placeholder="0" />
        </div>
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
        <div class="col-md-3">
          <label class="form-label">Collaboration Type</label>
          <select v-model="filters.collaboration_type" class="form-select">
            <option value="">All</option>
            <option v-for="ct in collaborationTypeOptions" :key="ct" :value="ct">{{ ct }}</option>
          </select>
        </div>
        <div class="col-md-auto">
          <button class="btn btn-primary me-2" @click="fetchData">Apply</button>
          <button class="btn btn-outline-secondary me-2" @click="resetFilters">Reset</button>
          <button class="btn btn-success" @click="exportPdf">Export PDF</button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2">Loading rankings...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-2" @click="fetchData">Retry</button>
    </div>

    <!-- Data -->
    <template v-if="!loading && data">
      <!-- Podium for top 3 -->
      <div v-if="data.ranking && data.ranking.length" class="podium mb-4">
        <div class="row justify-content-center">
          <div v-for="(item, idx) in data.ranking.slice(0, 3)" :key="idx" class="col-4 col-md-3 text-center">
            <div class="podium-item" :class="'rank-' + (idx+1)">
              <div class="podium-rank">{{ idx+1 }}</div>
              <div class="podium-name">{{ item.name || item.title || item.area }}</div>
              <div class="podium-value">{{ item.metric_value }}</div>
              <div class="podium-extra" v-if="data.category === 'researchers'">
                <span class="badge bg-secondary">{{ item.publications }} pubs</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Ranking Table -->
      <div v-if="data.ranking && data.ranking.length" class="card p-3">
        <h5 class="card-title">{{ getPageTitle() }}</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Rank</th>
                <th v-if="showUniversity">University</th>
                <th>{{ getEntityLabel() }}</th>
                <th v-for="col in displayColumns" :key="col">{{ col }}</th>
                <th>Metric</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data.ranking" :key="item.rank">
                <td>{{ item.rank }}</td>
                <td v-if="showUniversity">{{ item.university }}</td>
                <td>{{ item.name || item.title || item.area }}</td>
                <td v-for="col in displayColumns" :key="col">{{ getColumnValue(item, col) }}</td>
                <td><strong>{{ item.metric_value }}</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Key Findings -->
      <div v-if="data.key_findings && data.key_findings.length" class="card p-3 mt-4">
        <h5 class="card-title">Key Findings</h5>
        <ul class="insights-list">
          <li v-for="(finding, idx) in data.key_findings" :key="idx">
            <i class="bi bi-lightbulb-fill text-warning me-2"></i> {{ finding }}
          </li>
        </ul>
      </div>
    </template>

    <!-- Empty -->
    <div v-if="!loading && data && (!data.ranking || data.ranking.length === 0)" class="alert alert-warning">
      No ranking data available for the selected filters.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { defineOptions } from 'vue'

defineOptions({ name: 'Top10ListsPage' })

const authStore = useAuthStore()

// State
const loading = ref(false)
const error = ref(null)
const data = ref(null)

// Filters
const filters = ref({
  university: '',
  faculty: '',
  department: '',
  researcher: '',
  year: '',
  grade: '',
  publication_type: '',
  indexed: '',
  language: '',
  collaboration_type: '',
})
const category = ref('researchers')
const metric = ref('publications')
const limit = ref(10)
const minPublications = ref(0)

// Options
const universityOptions = ref([])
const facultyOptions = ref([])
const departmentOptions = ref([])
const researcherOptions = ref([])
const gradeOptions = ref([])
const publicationTypeOptions = ref([])
const indexedOptions = ref([])
const languageOptions = ref([])
const collaborationTypeOptions = ref([])

// ----- FIX: Metric options mapping -----
const metricOptionsMap = {
  researchers: {
    publications: 'Total Publications',
    citations: 'Total Citations',
    avg_citations: 'Average Citations per Publication',
    h_index: 'H-index',
    q1: 'Q1 Publications',
    q1_share: 'Q1 Share (%)',
    collaborative: 'Collaborative Publications',
    collaboration_rate: 'Collaboration Rate (%)',
    funded: 'Funded Publications',
    funded_rate: 'Funded Publication Rate (%)',
    cited: 'Cited Publications',
    cited_rate: 'Cited Publication Rate (%)',
  },
  publications: {
    citations: 'Total Citations',
    year: 'Year',
    q1: 'Q1',
    collaboration: 'Collaboration Type',
  },
  faculties: {
    publications: 'Total Publications',
    citations: 'Total Citations',
    avg_citations: 'Average Citations per Publication',
    q1: 'Q1 Publications',
    q1_share: 'Q1 Share (%)',
    researchers: 'Total Researchers',
    collaborative: 'Collaborative Publications',
    collaboration_rate: 'Collaboration Rate (%)',
    funded: 'Funded Publications',
    cited_rate: 'Cited Publication Rate (%)',
  },
  departments: {
    publications: 'Total Publications',
    citations: 'Total Citations',
    avg_citations: 'Average Citations per Publication',
    q1: 'Q1 Publications',
    q1_share: 'Q1 Share (%)',
    researchers: 'Total Researchers',
    collaborative: 'Collaborative Publications',
    collaboration_rate: 'Collaboration Rate (%)',
    funded: 'Funded Publications',
    cited_rate: 'Cited Publication Rate (%)',
  },
  universities: {
    publications: 'Total Publications',
    citations: 'Total Citations',
    avg_citations: 'Average Citations per Publication',
    researchers: 'Total Researchers',
    publications_per_researcher: 'Publications per Researcher',
    citations_per_researcher: 'Citations per Researcher',
    q1: 'Q1 Publications',
    q1_share: 'Q1 Share (%)',
    collaboration_rate: 'Collaboration Rate (%)',
    funded_rate: 'Funded Publication Rate (%)',
  },
  research_areas: {
    publications: 'Total Publications',
    citations: 'Total Citations',
    avg_citations: 'Average Citations per Publication',
    researchers: 'Total Researchers',
    q1: 'Q1 Publications',
    q1_share: 'Q1 Share (%)',
    collaboration_rate: 'Collaboration Rate (%)',
  },
}
// ----- END FIX -----

const categoryOptions = computed(() => {
  const cats = ['researchers', 'publications', 'faculties', 'departments', 'research_areas']
  if (authStore.isMinistryAuthority) cats.push('universities')
  return cats
})

// ----- FIX: metricOptions computed from the mapping -----
const metricOptions = computed(() => metricOptionsMap[category.value] || {})
// ----- END FIX -----

const displayColumns = ref([])

// Computed
const showUniversity = computed(() => authStore.isMinistryAuthority)

// Methods
const fetchFilterOptions = async () => {
  try {
    const [faculties, departments, lecturers, filterData, collab] = await Promise.all([
      api.get('/faculties'),
      api.get('/departments'),
      api.get('/lecturers-for-dropdown'),
      api.get('/key-findings/filters'),
      api.get('/collaboration-types'),
    ])
    facultyOptions.value = faculties.data
    departmentOptions.value = departments.data
    researcherOptions.value = lecturers.data.data || lecturers.data
    const fd = filterData.data
    gradeOptions.value = fd.grades || []
    publicationTypeOptions.value = fd.publication_types || []
    indexedOptions.value = fd.indexes || []
    languageOptions.value = fd.languages || ['Pashto', 'Dari', 'English']
    collaborationTypeOptions.value = collab.data || []

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
    const params = {
      category: category.value,
      metric: metric.value,
      limit: limit.value,
      minimum_publications: minPublications.value,
      ...filters.value,
    }
    const response = await api.get('/analytics/top-10', { params })
    data.value = response.data

    // Update display columns from the first row
    const first = data.value.ranking[0] || {}
    const cols = ['publications', 'citations', 'avg_citations', 'h_index', 'q1', 'q1_share', 'collaboration_rate', 'funded_rate', 'cited_rate', 'researchers', 'year', 'collaboration']
    displayColumns.value = cols.filter(c => first[c] !== undefined)
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load rankings.'
  } finally {
    loading.value = false
  }
}

const onCategoryChange = () => {
  // Reset metric to default for this category
  const defaultMetrics = {
    researchers: 'publications',
    publications: 'citations',
    faculties: 'publications',
    departments: 'publications',
    universities: 'publications',
    research_areas: 'publications',
  }
  metric.value = defaultMetrics[category.value] || 'publications'
  fetchData()
}

const resetFilters = () => {
  Object.keys(filters.value).forEach(key => filters.value[key] = '')
  category.value = 'researchers'
  metric.value = 'publications'
  limit.value = 10
  minPublications.value = 0
  fetchData()
}

const getPageTitle = () => {
  const catLabel = category.value.charAt(0).toUpperCase() + category.value.slice(1)
  const metricLabel = metricOptions.value[metric.value] || metric.value
  return `Top ${limit.value} ${catLabel} by ${metricLabel}`
}

const getEntityLabel = () => {
  const map = {
    researchers: 'Researcher',
    publications: 'Title',
    faculties: 'Faculty',
    departments: 'Department',
    universities: 'University',
    research_areas: 'Research Area',
  }
  return map[category.value] || 'Entity'
}

const getColumnValue = (item, col) => {
  return item[col] !== undefined ? item[col] : '-'
}

const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1)

const exportPdf = () => {
  const params = new URLSearchParams();
  Object.keys(filters.value).forEach(key => {
    if (filters.value[key]) params.append(key, filters.value[key]);
  });
  const url = `${api.defaults.baseURL}/api/analytics/top-10/preview?${params.toString()}`;
  window.open(url, '_blank');
};

onMounted(async () => {
  await fetchFilterOptions()
  await fetchData()
})
</script>

<style scoped>
.top10-lists { padding: 20px; }
.page-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 0; }
.page-subtitle { color: #6b7280; }
.podium { background: #f8fafc; border-radius: 12px; padding: 20px; }
.podium-item { background: #fff; border-radius: 8px; padding: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.podium-item.rank-1 { border: 2px solid #fbbf24; }
.podium-item.rank-2 { border: 2px solid #94a3b8; }
.podium-item.rank-3 { border: 2px solid #f59e0b; }
.podium-rank { font-size: 2rem; font-weight: 700; color: #1e293b; }
.podium-name { font-weight: 600; }
.podium-value { font-size: 1.2rem; font-weight: 700; color: #0f172a; }
.insights-list { list-style: none; padding: 0; }
.insights-list li { padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
</style>