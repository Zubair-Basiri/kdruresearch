<template>
  <div class="research-forecasting">
    <!-- Header -->
    <div class="page-header mt-3">
      <h1 class="page-title">Research Forecasting</h1>
      <p class="page-subtitle">Project future research output based on historical trends</p>
    </div>

    <!-- Filters -->
    <div class="filter-panel card p-3 mb-4">
      <div class="row g-3 align-items-end">
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
          <label class="form-label">Start Year</label>
          <input type="number" v-model="startYear" class="form-control" placeholder="e.g. 2018" />
        </div>
        <div class="col-md-3">
          <label class="form-label">End Year</label>
          <input type="number" v-model="endYear" class="form-control" placeholder="e.g. 2025" />
        </div>
        <div class="col-md-3">
          <label class="form-label">Forecast Horizon (Years)</label>
          <select v-model="forecastHorizon" class="form-select">
            <option v-for="h in [1,2,3,4,5]" :key="h" :value="h">{{ h }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Target</label>
          <select v-model="target" class="form-select">
            <option value="publications">Publications</option>
            <option value="q1">Q1 Publications</option>
            <option value="q2">Q2 Publications</option>
            <option value="q3">Q3 Publications</option>
            <option value="q4">Q4 Publications</option>
            <option value="books">Books</option>
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
      <p class="mt-2">Generating forecast...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="alert alert-danger">
      {{ error }}
      <button class="btn btn-sm btn-outline-danger ms-2" @click="fetchData">Retry</button>
    </div>

    <!-- Data -->
    <template v-if="!loading && data">
      <!-- KPIs -->
      <div v-if="data.summary" class="kpi-grid mb-4">
        <div class="kpi-card">
          <div class="kpi-label">Current Output</div>
          <div class="kpi-value">{{ data.summary.current_output }}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Next Year Forecast</div>
          <div class="kpi-value">{{ data.summary.next_year_forecast }}</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Expected Growth</div>
          <div class="kpi-value" :class="data.summary.growth_rate >= 0 ? 'text-success' : 'text-danger'">
            {{ data.summary.growth_rate >= 0 ? '+' : '' }}{{ data.summary.growth_rate }}%
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">Forecast Reliability</div>
          <div class="kpi-value">{{ getReliabilityLabel(data.forecast.reliability) }}</div>
        </div>
      </div>

      <!-- Chart -->
      <div class="card p-3 mb-4">
        <h5 class="card-title">Publication Forecast</h5>
        <div v-if="chartData.length">
          <apexchart type="line" height="350" :options="chartOptions" :series="chartSeries" />
        </div>
        <div v-else class="text-center text-muted py-4">No data available for chart</div>
      </div>

      <!-- Forecast Table -->
      <div class="card p-3 mb-4">
        <h5 class="card-title">Forecast Table</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Year</th>
                <th>Type</th>
                <th>Value</th>
                <th v-if="data.intervals && data.intervals.length">Lower Bound</th>
                <th v-if="data.intervals && data.intervals.length">Upper Bound</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, idx) in tableData" :key="idx">
                <td>{{ item.year }}</td>
                <td>{{ item.type }}</td>
                <td>{{ item.value }}</td>
                <td v-if="data.intervals && data.intervals.length">{{ item.lower || '-' }}</td>
                <td v-if="data.intervals && data.intervals.length">{{ item.upper || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Faculty Forecast -->
      <div v-if="data.faculties && data.faculties.length" class="card p-3 mb-4">
        <h5 class="card-title">Faculty Forecast</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Faculty</th>
                <th>Current</th>
                <th>Next Year</th>
                <th>Growth</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="f in data.faculties" :key="f.faculty">
                <td>{{ f.faculty }}</td>
                <td>{{ f.current }}</td>
                <td>{{ f.next_year }}</td>
                <td :class="f.growth >= 0 ? 'text-success' : 'text-danger'">
                  {{ f.growth >= 0 ? '+' : '' }}{{ f.growth }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Research Area Forecast -->
      <div v-if="data.research_areas && data.research_areas.length" class="card p-3 mb-4">
        <h5 class="card-title">Research Area Forecast</h5>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Research Area</th>
                <th>Current</th>
                <th>Next Year</th>
                <th>Growth</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ra in data.research_areas" :key="ra.area">
                <td>{{ ra.area }}</td>
                <td>{{ ra.current }}</td>
                <td>{{ ra.next_year }}</td>
                <td :class="ra.growth >= 0 ? 'text-success' : 'text-danger'">
                  {{ ra.growth >= 0 ? '+' : '' }}{{ ra.growth }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Key Findings -->
      <div v-if="data.key_findings && data.key_findings.length" class="card p-3 mb-4">
        <h5 class="card-title">Key Findings</h5>
        <ul class="insights-list">
          <li v-for="(finding, idx) in data.key_findings" :key="idx">
            <i class="bi bi-lightbulb-fill text-warning me-2"></i> {{ finding }}
          </li>
        </ul>
      </div>

      <!-- Methodology -->
      <div class="card p-3">
        <h5 class="card-title">Methodology & Limitations</h5>
        <div v-if="data.methodology">
          <p><strong>Method:</strong> {{ data.methodology.method }}</p>
          <p><strong>Forecast Horizon:</strong> {{ data.methodology.forecast_horizon }}</p>
          <p><strong>Algorithm:</strong> {{ data.methodology.algorithm }}</p>
          <p><strong>Reliability:</strong> Based on R-squared and historical data length.</p>
          <p><strong>Limitations:</strong> {{ data.methodology.limitations }}</p>
        </div>
        <div class="alert alert-info small mt-2">
          <i class="bi bi-info-circle"></i> 
          Forecasts are estimates based on historical research activity. They are not guaranteed outcomes. 
          Actual future results may differ because of funding, policy, staffing, collaboration, publication delays, 
          research priorities, and other external factors.
        </div>
      </div>
    </template>

    <!-- Empty -->
    <div v-if="!loading && data && (!data.summary || !data.forecast)" class="alert alert-warning">
      No forecasting data available for the selected filters.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'
import { loadAnalyticsFilters } from '@/services/analyticsFilters'
import { useAuthStore } from '@/stores/auth'
import { defineOptions } from 'vue'

defineOptions({ name: 'ResearchForecastingPage' })

const authStore = useAuthStore()

const filters = ref({
  university: '',
  faculty: '',
  department: '',
  researcher: '',
  publication_type: '',
  indexed: '',
  language: '',
  collaboration_type: '',
})

const startYear = ref('')
const endYear = ref('')
const forecastHorizon = ref(3)
const target = ref('publications')

const data = ref(null)
const loading = ref(false)
const error = ref(null)

// Filter options
const universityOptions = ref([])
const facultyOptions = ref([])
const departmentOptions = ref([])
const researcherOptions = ref([])

// Chart data
const chartData = computed(() => {
  if (!data.value) return []
  const historical = data.value.historical || {}
  const forecast = data.value.forecast_values || []
  const result = []
  for (const [year, val] of Object.entries(historical)) {
    result.push({ year: parseInt(year), value: val, type: 'Historical' })
  }
  for (const item of forecast) {
    result.push({ year: item.year, value: item.value, type: 'Forecast' })
  }
  return result
})

const chartSeries = computed(() => {
  if (!chartData.value.length) return []
  const historical = chartData.value.filter(d => d.type === 'Historical')
  const forecast = chartData.value.filter(d => d.type === 'Forecast')
  return [
    {
      name: 'Historical',
      data: historical.map(d => ({ x: d.year, y: d.value })),
    },
    {
      name: 'Forecast',
      data: forecast.map(d => ({ x: d.year, y: d.value })),
    },
  ]
})

const chartOptions = ref({
  chart: { toolbar: { show: false } },
  xaxis: { title: { text: 'Year' } },
  yaxis: { 
    title: { text: 'Publications' },
    labels: {
      formatter: (val) => val !== undefined && val !== null ? Math.round(val) : 0
    }
  },
  colors: ['#3b82f6', '#f59e0b'],
  stroke: { curve: 'smooth', width: 2 },
  markers: { size: 4 },
  tooltip: { 
    y: { 
      formatter: (val) => {
        if (val === undefined || val === null || isNaN(val)) return '0';
        return val.toFixed(0);
      }
    } 
  },
})

const tableData = computed(() => {
  if (!data.value) return []
  const historical = data.value.historical || {}
  const forecast = data.value.forecast_values || []
  const intervals = data.value.intervals || []
  const result = []
  for (const [year, val] of Object.entries(historical)) {
    result.push({ year: parseInt(year), type: 'Historical', value: val })
  }
  for (let i = 0; i < forecast.length; i++) {
    const item = forecast[i]
    const interval = intervals[i] || {}
    result.push({
      year: item.year,
      type: 'Forecast',
      value: item.value,
      lower: interval.lower,
      upper: interval.upper,
    })
  }
  return result
})

// Methods
const fetchFilterOptions = async () => {
  const options = await loadAnalyticsFilters({
    universities: authStore.isMinistryAuthority,
  })
  facultyOptions.value = options.faculties
  departmentOptions.value = options.departments
  researcherOptions.value = options.researchers
  universityOptions.value = options.universities
}

const fetchData = async () => {
  loading.value = true
  error.value = null
  try {
    const params = {
      ...filters.value,
      start_year: startYear.value || undefined,
      end_year: endYear.value || undefined,
      forecast_horizon: forecastHorizon.value,
      target: target.value,
    }
    const response = await api.get('/analytics/research-forecasting', { params })
    data.value = response.data
  } catch (err) {
    error.value = err.response?.data?.message || 'Failed to generate forecast.'
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  Object.keys(filters.value).forEach(key => filters.value[key] = '')
  startYear.value = ''
  endYear.value = ''
  forecastHorizon.value = 3
  target.value = 'publications'
  fetchData()
}

const getReliabilityLabel = (reliability) => {
  const map = {
    'high': 'High',
    'moderate': 'Moderate',
    'low': 'Low',
    'insufficient_data': 'Insufficient Data',
  }
  return map[reliability] || 'Unknown'
}

const exportPdf = () => {
  const params = new URLSearchParams();
  Object.keys(filters.value).forEach(key => {
    if (filters.value[key]) params.append(key, filters.value[key]);
  });
  const url = `${api.defaults.baseURL}/api/analytics/research-forecasting/preview?${params.toString()}`;
  window.open(url, '_blank');
};

onMounted(async () => {
  await fetchFilterOptions()
  await fetchData()
})
</script>

<style scoped>
.research-forecasting { padding: 20px; }
.page-title { font-size: 1.8rem; font-weight: 700; margin-bottom: 0; }
.page-subtitle { color: #6b7280; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; }
.kpi-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: center; }
.kpi-label { font-size: 0.8rem; color: #6b7280; text-transform: uppercase; }
.kpi-value { font-size: 1.6rem; font-weight: 700; color: #111827; }
.insights-list { list-style: none; padding: 0; }
.insights-list li { padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
</style>
