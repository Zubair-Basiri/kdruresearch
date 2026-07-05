<template>
  <div class="dashboard">
    <!-- LEFT FILTER PANEL -->
    <aside class="filter-panel">
      <h3>Filters</h3>
      <div class="filter-group">
        <label>Faculty</label>
        <select v-model="filters.faculty_id" class="compact-select">
          <option value="">All</option>
          <option v-for="f in faculties" :key="f.id" :value="f.id">
            {{ f.facultyname }}
          </option>
        </select>
      </div>
      <div class="filter-group">
        <label>Year</label>
        <select v-model="filters.year" class="compact-select">
          <option value="">All</option>
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
      <button class="btn-primary apply-btn" @click="applyFilter">Apply</button>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="content" v-if="!loading">
      <!-- KPI ROW -->
      <div class="kpi-row">
        <KpiMiniCard title="Publications" :value="kpis.publications" color="blue" />
        <KpiMiniCard title="Funding" :value="'$' + kpis.funding?.toLocaleString()" color="green" />
        <KpiMiniCard title="Researchers" :value="kpis.researchers" color="purple" />
        <KpiMiniCard title="Published" :value="kpis.published" color="orange" />
      </div>

      <!-- ROW 1 -->
      <div class="grid-2">
        <ChartCard title="Publications Over Time">
          <apexchart
            type="line"
            height="180"
            :series="lineSeries"
            :options="lineOptions"
          />
        </ChartCard>
        <ChartCard title="Projects By Faculty">
          <apexchart
            type="bar"
            height="180"
            :series="barSeries"
            :options="barOptions"
          />
        </ChartCard>
      </div>

      <!-- ROW 2 -->
      <div class="grid-2">
        <ChartCard title="Publication Type / Faculty" filter>
          <apexchart
            type="donut"
            height="250"
            :series="donutPublicationSeries"
            :options="donutOptions(publicationTypeLabels)"
          />
        </ChartCard>
        <ChartCard title="Indexed / Faculty" filter>
          <apexchart
            type="donut"
            height="250"
            :series="donutIndexedSeries"
            :options="donutOptions(indexedLabels)"
          />
        </ChartCard>
      </div>

      <!-- ROW 3 -->
      <div class="grid-2">
        <ChartCard title="Research Area / Faculty">
          <apexchart
            type="bar"
            height="180"
            :series="areaSeries"
            :options="areaOptions"
          />
        </ChartCard>
        <ChartCard title="Researchers / Faculty">
          <apexchart
            type="bar"
            height="250"
            :series="researchersBarSeries"
            :options="researchersBarOptions"
          />
        </ChartCard>
      </div>

      <!-- ROW 4 -->
      <div class="grid-2">
        <ChartCard title="Grade / Faculty">
          <apexchart
            type="bar"
            height="180"
            :series="gradeSeries"
            :options="gradeOptions"
          />
        </ChartCard>
        <ChartCard title="Education / Faculty">
          <apexchart
            type="bar"
            height="180"
            :series="educationSeries"
            :options="educationOptions"
          />
        </ChartCard>
      </div>

      <!-- ROW 5 -->
      <div class="grid-2">
        <ChartCard title="Department / Faculty">
          <apexchart
            type="bar"
            height="180"
            :series="departmentSeries"
            :options="departmentOptions"
          />
        </ChartCard>
        <ChartCard title="Publication Language / Faculty">
          <apexchart
            type="bar"
            height="180"
            :series="languageSeries"
            :options="languageOptions"
          />
        </ChartCard>
      </div>
    </main>
    <div v-else class="loading">Loading...</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import KpiMiniCard from '@/components/facultyCards/KpiMiniCard.vue'
import ChartCard from '@/components/facultyCards/ChartCard.vue'

// ===== STATE =====
const filters = ref({ faculty_id: '', year: '' })
const faculties = ref([])
const years = ref([])
const dashboardData = ref(null)
const loading = ref(false)

// ===== FETCH FILTER OPTIONS =====
const fetchFilters = async () => {
  try {
    const { data } = await api.get('/dashboard/filters')
    faculties.value = data.faculties
    years.value = data.years
  } catch (error) {
    console.error('Failed to load filters', error)
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

// Initial load
onMounted(() => {
  fetchFilters()
  fetchDashboardData()
})

// Refetch when filters change (optional – you may keep "Apply" button only)
watch(filters, () => {
  fetchDashboardData()
}, { deep: true })

// ===== APPLY FILTER (if you want to keep button) =====
const applyFilter = () => {
  fetchDashboardData()
}

// ===== COMPUTED PROPERTIES FOR CHARTS =====
const kpis = computed(() => dashboardData.value?.kpis || {
  publications: 0, funding: 0, researchers: 0, published: 0
})

// ---- Line Chart (Publications Over Time) ----
const lineSeries = computed(() => [
  { name: 'Publications', data: dashboardData.value?.publications_over_time?.map(item => item.count) || [] }
])
const lineOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { categories: dashboardData.value?.publications_over_time?.map(item => item.year) || [] },
  colors: ['#3b82f6']
}))

// ---- Bar Chart (Projects By Faculty) ----
const barSeries = computed(() => [
  { name: 'Publications', data: dashboardData.value?.projects_by_faculty?.map(item => item.count) || [] }
])
const barOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { categories: dashboardData.value?.projects_by_faculty?.map(item => item.faculty) || [] },
  colors: ['#10b981']
}))

// ---- Donut: Publication Type ----
const publicationTypeLabels = computed(() =>
  dashboardData.value?.publication_type_distribution?.map(item => item.type) || []
)
const donutPublicationSeries = computed(() =>
  dashboardData.value?.publication_type_distribution?.map(item => item.count) || []
)

// ---- Donut: Indexed ----
const indexedLabels = computed(() =>
  dashboardData.value?.indexed_distribution?.map(item => item.indexed) || []
)
const donutIndexedSeries = computed(() =>
  dashboardData.value?.indexed_distribution?.map(item => item.count) || []
)

// ---- Bar: Research Area ----
const areaSeries = computed(() => [
  { name: 'Publications', data: dashboardData.value?.research_area_distribution?.map(item => item.count) || [] }
])
const areaOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { categories: dashboardData.value?.research_area_distribution?.map(item => item.area) || [] },
  colors: ['#f59e0b']
}))

// ---- Table: Researchers by Faculty ----
const researchersBarSeries = computed(() => [
  { 
    name: 'Researchers', 
    data: dashboardData.value?.researchers_by_faculty?.map(item => item.count) || [] 
  }
])

const researchersBarOptions = computed(() => ({
  chart: {
    type: 'bar',
    toolbar: { show: false }
  },
  plotOptions: {
    bar: {
      horizontal: true,
      barHeight: '50%',
      distributed: true
    }
  },
  dataLabels: {
    enabled: true,
    formatter: (val) => val,
    offsetX: 20
  },
  xaxis: {
    categories: dashboardData.value?.researchers_by_faculty?.map(item => item.faculty) || []
  },
  colors: ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b', '#ef4444'],
  legend: { show: false }
}))

// ---- Bar: Grade ----
const gradeSeries = computed(() => [
  { name: 'Publications', data: dashboardData.value?.grade_distribution?.map(item => item.count) || [] }
])
const gradeOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { categories: dashboardData.value?.grade_distribution?.map(item => item.grade) || [] },
  colors: ['#3b82f6']
}))

// ---- Bar: Education ----
const educationSeries = computed(() => [
  { name: 'Publications', data: dashboardData.value?.education_distribution?.map(item => item.count) || [] }
])
const educationOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { categories: dashboardData.value?.education_distribution?.map(item => item.education) || [] },
  colors: ['#10b981']
}))

// ---- Bar: Department ----
const departmentSeries = computed(() => [
  { name: 'Publications', data: dashboardData.value?.department_distribution?.map(item => item.count) || [] }
])
const departmentOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { categories: dashboardData.value?.department_distribution?.map(item => item.department) || [] },
  colors: ['#f59e0b']
}))

// ---- Bar: Language ----
const languageSeries = computed(() => [
  { name: 'Publications', data: dashboardData.value?.language_distribution?.map(item => item.count) || [] }
])
const languageOptions = computed(() => ({
  chart: { toolbar: { show: false } },
  xaxis: { categories: dashboardData.value?.language_distribution?.map(item => item.language) || [] },
  colors: ['#8b5cf6']
}))

// ---- Donut helper ----
const donutOptions = (labels, chartId = 'donut') => ({
  labels,
  chart: {
    id: chartId,
    toolbar: { show: false }
  },
  legend: {
    position: 'bottom',
    fontSize: '12px',
    markers: { width: 10, height: 10 }
  },
  dataLabels: {
    enabled: true,
    formatter: function (val, opts) {
      return opts.w.globals.series[opts.seriesIndex] + ' (' + val.toFixed(1) + '%)'
    }
  },
  plotOptions: {
    pie: {
      donut: {
        labels: {
          show: true,
          total: { show: true, label: 'Total', fontSize: '14px' }
        }
      }
    }
  },
  responsive: [{
    breakpoint: 480,
    options: {
      chart: { width: '100%' },
      legend: { position: 'bottom' }
    }
  }]
})
</script>

<style scoped>
  .dashboard {
    position: relative;
    z-index: 2;
    margin-top: -30px;
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 16px;
  }

  .filter-panel {
    background: #fff;
    padding: 10px;
    border-radius: 14px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, .08);
    display: flex;
    flex-direction: column;
    gap: 8px;
    /* Sticky and small height */
    position: sticky;
    top: 20px;
    max-height: 300px;
    overflow-y: auto;
  }

  .filter-group {
    display: flex;
    flex-direction: column;
    font-size: 13px;
  }

  .compact-select {
    height: 28px;
    font-size: 13px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    padding: 2px 6px;
  }

  .apply-btn {
    margin-top: 8px;
    padding: 6px 10px;
    border-radius: 6px;
    font-size: 13px;
  }

  .content {
    display: flex;
    flex-direction: column;
    gap: 16px
  }

  .kpi-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
  }

  .grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
  }

  .mini-table {
    width: 100%;
    font-size: 13px;
  }

  .mini-table td {
    padding: 6px;
    border-bottom: 1px solid #eee;
  }
</style>
