<template>
  <div class="benchmarking">
    <!-- Header -->
    <div class="page-header mt-3">
      <h1 class="page-title">Research Performance Benchmarking</h1>
      <p class="page-subtitle">Compare research performance across universities, faculties, departments, and researchers.</p>
    </div>

    <div class="alert alert-info small mt-3">
        <i class="bi bi-info-circle"></i> 
        <strong>How filters work:</strong> 
        The <strong>Faculty</strong>, <strong>Department</strong>, and <strong>Researcher</strong> filters restrict data to that specific unit across all selected entities. 
        For example, selecting <strong>Computer Science</strong> will show only Computer Science publications for all universities in the comparison.
    </div>

    <!-- Configuration Panel -->
    <div class="config-panel card p-3 mb-4">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label">Benchmark Level</label>
          <select v-model="level" class="form-select" @change="onLevelChange">
            <option v-for="lvl in availableLevels" :key="lvl" :value="lvl">
              {{ capitalize(lvl) }}
            </option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Primary Entity</label>
          <select v-model="primaryId" class="form-select">
            <option v-for="entity in entityOptions" :key="entity.id" :value="entity.id">
              {{ entity.name }}
            </option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Compare Against</label>
          <Multiselect
            v-model="peerIds"
            :options="entityOptions"
            :multiple="true"
            :searchable="true"
            :close-on-select="false"
            :clear-on-select="false"
            label="name"
            track-by="id"
            placeholder="Select peers..."
            class="multiselect-custom"
          />
        </div>
        <div class="col-md-3">
          <label class="form-label">Year</label>
          <input type="number" v-model="filters.year" class="form-control" placeholder="e.g. 2025" />
        </div>
        <div class="col-md-auto">
          <button class="btn btn-primary me-2" @click="applyBenchmark">Apply</button>
          <button class="btn btn-outline-secondary me-2" @click="resetBenchmark">Reset</button>
          <button class="btn btn-success" @click="exportPdf">Export PDF</button>
        </div>
      </div>
      <!-- Additional filters (reuse existing) -->
      <div class="row g-3 mt-2">
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
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2">Loading benchmark data...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-2" @click="fetchData">Retry</button>
    </div>

    <!-- Data -->
    <template v-if="!loading && data">
      <!-- KPIs for Primary -->
      <div v-if="data.primary" class="kpi-grid mb-4">
        <div class="kpi-card" v-for="(kpi, key) in primaryKpis" :key="key">
          <div class="kpi-label">{{ kpi.label }}</div>
          <div class="kpi-value">{{ kpi.value }}</div>
        </div>
      </div>

      <!-- Comparison Matrix -->
      <div v-if="data.comparison && data.comparison.length" class="card p-3 mb-4">
        <h5 class="card-title">Benchmark Comparison Matrix</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Indicator</th>
                <th>Primary</th>
                <th>Peer Average</th>
                <th>Difference</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data.comparison" :key="item.indicator">
                <td>{{ item.indicator }}</td>
                <td>{{ item.primary_value }}</td>
                <td>{{ item.peer_average }}</td>
                <td>
                  <span :class="{'text-success': item.difference_percent > 0, 'text-danger': item.difference_percent < 0}">
                    {{ item.difference_percent }}%
                  </span>
                </td>
                <td>
                  <span :class="{'badge bg-success': item.status === 'above', 'badge bg-warning': item.status === 'near', 'badge bg-danger': item.status === 'below'}">
                    {{ item.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Ranking -->
      <div v-if="data.ranking && data.ranking.length" class="card p-3 mb-4">
        <h5 class="card-title">Research Performance Benchmark Ranking</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Rank</th>
                <th>Name</th>
                <th>Publications</th>
                <th>Citations</th>
                <th>H-index</th>
                <th>Q1</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data.ranking" :key="item.rank" :class="{'table-primary': item.entity.id == primaryId}">
                <td>{{ item.rank }}</td>
                <td>{{ item.entity.name }}</td>
                <td>{{ item.metrics.publications }}</td>
                <td>{{ item.metrics.citations }}</td>
                <td>{{ item.metrics.h_index }}</td>
                <td>{{ item.metrics.q1 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Internal Benchmark (Faculty/Department/Researcher) -->
      <div v-if="data.internal_benchmark && data.internal_benchmark.length" class="card p-3 mb-4">
        <h5 class="card-title">{{ capitalize(level) }} Performance</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Name</th>
                <th>Publications</th>
                <th>Citations</th>
                <th>Avg</th>
                <th>H-index</th>
                <th>Q1</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in data.internal_benchmark" :key="item.entity.id">
                <td>{{ item.entity.name }}</td>
                <td>{{ item.metrics.publications }}</td>
                <td>{{ item.metrics.citations }}</td>
                <td>{{ item.metrics.avg_citations }}</td>
                <td>{{ item.metrics.h_index }}</td>
                <td>{{ item.metrics.q1 }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Insights -->
      <div v-if="data.insights && data.insights.length" class="card p-3">
        <h5 class="card-title">Benchmark Insights</h5>
        <ul class="insights-list">
          <li v-for="(insight, idx) in data.insights" :key="idx">
            <i class="bi bi-lightbulb-fill text-warning me-2"></i> {{ insight }}
          </li>
        </ul>
      </div>
    </template>

    <!-- Empty -->
    <div v-if="!loading && data && !data.primary" class="alert alert-warning">
      No benchmarking data available. Please adjust your selection.
    </div>
  </div>
</template>

<script>
export default {
  name: 'BenchmarkingPage'
}
</script>

<script setup>
import { ref, computed, onMounted} from 'vue'
import api from '@/services/api'
import { loadAnalyticsFilters } from '@/services/analyticsFilters'
import Multiselect from 'vue-multiselect'

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

const level = ref('university')
const primaryId = ref(null)
const peerIds = ref([])
const data = ref(null)
const loading = ref(false)
const error = ref(null)

// Filter options (same as Citation Analytics)
const facultyOptions = ref([])
const departmentOptions = ref([])
const researcherOptions = ref([])
const gradeOptions = ref([])
const publicationTypeOptions = ref([])
const indexedOptions = ref([])
const languageOptions = ref([])
const entityOptions = ref([])
const availableLevels = ref(['university', 'faculty', 'department', 'researcher'])

// Computed KPIs for primary
const primaryKpis = computed(() => {
  if (!data.value || !data.value.primary) return {}
  const p = data.value.primary
  return {
    publications: { label: 'Publications', value: p.publications },
    citations: { label: 'Citations', value: p.citations.toLocaleString() },
    avgCitations: { label: 'Avg Citations', value: p.avg_citations },
    hIndex: { label: 'H-index', value: p.h_index },
    cited: { label: 'Cited Publications', value: p.cited_publications },
    q1: { label: 'Q1', value: p.q1 },
  }
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

const fetchEntityOptions = async () => {
  try {
    // Map benchmark level to API endpoint
    const endpointMap = {
      university: 'universities',
      faculty: 'faculties',
      department: 'departments',
      researcher: 'lecturers-for-dropdown',
    };
    const endpoint = endpointMap[level.value] || level.value + 's';
    const response = await api.get(`/${endpoint}`);
    // Handle different response structures
    let entities = response.data;
    if (response.data.data && Array.isArray(response.data.data)) {
      entities = response.data.data;
    } else if (!Array.isArray(response.data)) {
      entities = [];
    }
    entityOptions.value = entities.map(e => ({
      id: e.id,
      name: e.name || e.facultyname || e.deptname || e.lecturername || 'Unnamed',
    }));
    if (!primaryId.value && entityOptions.value.length) {
      primaryId.value = entityOptions.value[0].id;
    }
  } catch (err) {
    console.error('Failed to load entities', err);
    // Optionally show a user-friendly message
    error.value = 'Could not load entities. Please try again.';
  }
};

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {
      level: level.value,
      primary_id: primaryId.value,
      peer_ids: peerIds.value.map(p => p.id || p).join(','),
      ...filters.value,
    }
    const response = await api.get('/analytics/benchmarking', { params })
    data.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to load benchmark data.'
  } finally {
    loading.value = false
  }
}

const applyBenchmark = () => {
  fetchData()
}

const resetBenchmark = () => {
  Object.keys(filters.value).forEach(key => {
    if (key === 'faculty' || key === 'department' || key === 'researcher') {
      filters.value[key] = '';
    } else {
      filters.value[key] = '';
    }
  });
  // Or simply reset all to empty string:
  // Object.keys(filters.value).forEach(key => filters.value[key] = '');
  peerIds.value = [];
  level.value = 'university';
  primaryId.value = null;
  fetchEntityOptions();
  fetchData();
};

const onLevelChange = () => {
  primaryId.value = null
  peerIds.value = []
  fetchEntityOptions()
}

const capitalize = (str) => str.charAt(0).toUpperCase() + str.slice(1)

const exportPdf = () => {
  const params = new URLSearchParams();

  // Add benchmarking-specific parameters
  params.append('level', level.value);
  if (primaryId.value) params.append('primary_id', primaryId.value);
  if (peerIds.value.length > 0) {
    const peerIdsString = peerIds.value.map(p => p.id || p).join(',');
    params.append('peer_ids', peerIdsString);
  }

  // Add existing filters
  Object.keys(filters.value).forEach(key => {
    if (filters.value[key]) params.append(key, filters.value[key]);
  });

  const url = `${api.defaults.baseURL}/api/analytics/benchmarking/preview?${params.toString()}`;
  window.open(url, '_blank');
};

onMounted(async () => {
  await fetchFilterOptions()
  await fetchEntityOptions()
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
