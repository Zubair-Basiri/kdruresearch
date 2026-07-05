<template>
  <div class="summary-container">

    <!-- HEADER -->
    <div class="dashboard-header">
      <h2 class="main-title">
        Comprehensive Year-over-Year Statistical Analysis
      </h2>
      <p class="subtitle">
        Faculty & Departmental Performance Metrics
      </p>
    </div>

    <!-- CONTROLS (3 columns) -->
    <div class="control-grid three-col">

      <!-- YEAR RANGE FILTER -->
      <div class="filter-group">
        <label>Year Range (optional)</label>
        <div class="range-wrapper">
          <select v-model="startYear" class="modern-select small">
            <option value="">From</option>
            <option v-for="y in availableYears" :key="'from'+y" :value="y">{{ y }}</option>
          </select>
          <span class="range-sep">–</span>
          <select v-model="endYear" class="modern-select small">
            <option value="">To</option>
            <option v-for="y in availableYears" :key="'to'+y" :value="y">{{ y }}</option>
          </select>
        </div>
        <p class="range-hint" v-if="startYear && endYear && startYear > endYear">
          ⚠️ End year must be ≥ start year
        </p>
      </div>
      
      <!-- YEAR METRIC -->
      <div class="filter-group">
        <label>Metric Selection Based on Year</label>
        <div class="input-wrapper">
          <select v-model="selectedMetric" class="modern-select">
            <option disabled value="">Choose Year Metric...</option>
            <option v-for="m in metrics" :key="m" :value="m">{{ m }}</option>
          </select>
          <button class="btn btn-primary" @click="generateYearTable">
            Generate Summary
          </button>
        </div>
      </div>

      <!-- CATEGORY METRIC -->
      <div class="filter-group">
        <label>Growth Per Base Year Ratio</label>
        <div class="input-wrapper">
          <select v-model="selectedCategory" class="modern-select">
            <option disabled value="">Choose Category...</option>
            <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
          </select>
          <button class="btn btn-primary" @click="generateCategoryTable">
            Generate Table
          </button>
        </div>
      </div>

    </div>

    <!-- MODAL (Teleported to body for full viewport) -->
    <Teleport to="body">
      <Transition name="modal-fade">
        <div
          v-if="showModalYear || showModalCategory"
          class="modal-overlay"
          @click.self="closeAll"
        >
          <div class="modal-window">

            <!-- HEADER -->
            <div class="modal-head">
              <div class="head-info">
                <span class="badge">Live Report</span>
                <h3>
                  {{ showModalYear ? selectedMetric : selectedCategory }}
                  <span v-if="startYear && endYear" class="range-badge">
                    {{ startYear }}–{{ endYear }}
                  </span>
                </h3>
              </div>
              <button class="close-icon" @click="closeAll">✕</button>
            </div>

            <!-- BODY -->
            <div class="modal-body">
              <div v-if="loading" class="loading-state">Loading data...</div>
              <div v-else>
                <div class="table-responsive">
                  <table class="real-table">
                    <thead>
                      <tr>
                        <th class="sticky-col">
                          {{ showModalYear ? selectedMetric : 'Category' }}
                        </th>
                        <th v-for="year in activeYears" :key="year">{{ year }}</th>
                        <template v-if="showModalYear">
                          <th class="summary-col">Total</th>
                          <th class="summary-col">Total Citation</th>
                        </template>
                        <th v-if="showModalCategory" class="avg-col">Average</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in paginatedRows" :key="row.label">
                        <td class="sticky-col font-bold">{{ row.label }}</td>
                        <td v-for="year in activeYears" :key="year" class="data-cell">
                          <div
                            class="cell-content"
                            :style="getMinimalStyle(row.values[year], row)"
                          >
                            {{ row.values[year] }}
                            {{ showModalCategory ? '%' : '' }}
                          </div>
                        </td>
                        <template v-if="showModalYear">
                          <td class="total-cell">{{ row.total }}</td>
                          <td class="citation-cell">{{ row.totalCitation }}</td>
                        </template>
                        <td v-if="showModalCategory" class="avg-cell">
                          {{ row.average.toFixed(1) }}%
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <!-- PAGINATION & PDF BUTTON -->
                <div class="modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
                  <button class="btn-preview" @click="previewPdf">📄 Preview PDF</button>
                  <div class="pagination-footer">
                    <button class="p-btn" @click="changePage(-1)" :disabled="currentPage === 1">← Previous</button>
                    <span class="page-info">Page <b>{{ currentPage }}</b> of <b>{{ totalPages }}</b></span>
                    <button class="p-btn" @click="changePage(1)" :disabled="currentPage === totalPages">Next →</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import api from '@/services/api.js'

// ----------------------------- CONFIG -----------------------------
const availableYears = ref(Array.from({ length: 26 }, (_, i) => 2000 + i)) // 2000–2025

// ----------------------------- STATE -----------------------------
const selectedMetric = ref('')
const selectedCategory = ref('')
const startYear = ref('')
const endYear = ref('')
const yearTableRows = ref([])
const categoryTableRows = ref([])
const showModalYear = ref(false)
const showModalCategory = ref(false)
const loading = ref(false)
const currentPage = ref(1)
const perPage = 8

// ----------------------------- OPTIONS -----------------------------
const metrics = [
  "Faculty / Year",
  "Department / Year",
  "Academic Grade / Year",
  "Researcher / Year",
  "publication Type / Year",
  "Funding Source / Year",
  "Collaboration / Year",
  "Publication Language / Year",
  "Index / Year",
  "Qualification / Year",
  "Status / Year"
]

const categories = [
  "Faculties",
  "Departments",
  "Academic Grades",
  "Academic Qualification",
  "Publication Types",
  "Funding Sources",
  "Indexed",
  "Collaboration",
  "Status",
  "Publication Language"
]

// ----------------------------- COMPUTED -----------------------------
const activeYears = computed(() => {
  if (showModalYear.value) {
    return yearTableRows.value[0]?.values ? Object.keys(yearTableRows.value[0].values).map(Number).sort() : []
  } else {
    return categoryTableRows.value[0]?.values ? Object.keys(categoryTableRows.value[0].values).map(Number).sort() : []
  }
})

const activeData = computed(() =>
  showModalYear.value ? yearTableRows.value : categoryTableRows.value
)

const totalPages = computed(() =>
  Math.ceil(activeData.value.length / perPage) || 1
)

const paginatedRows = computed(() =>
  activeData.value.slice(
    (currentPage.value - 1) * perPage,
    currentPage.value * perPage
  )
)

const previewPdf = () => {
    let url = `${api.defaults.baseURL}/api/year-summary/preview?`;
    if (showModalYear.value) {
        url += `metric=${encodeURIComponent(selectedMetric.value)}`;
    } else if (showModalCategory.value) {
        url += `category=${encodeURIComponent(selectedCategory.value)}`;
    } else {
        return;
    }
    if (startYear.value) url += `&start_year=${startYear.value}`;
    if (endYear.value) url += `&end_year=${endYear.value}`;
    window.open(url, '_blank');
};

// ----------------------------- METHODS -----------------------------
const closeAll = () => {
  showModalYear.value = false
  showModalCategory.value = false
  currentPage.value = 1
}

const changePage = (dir) => {
  currentPage.value += dir
}

// ----------------------------- FETCH YEAR SUMMARY -----------------------------
async function generateYearTable() {
  if (!selectedMetric.value) return
  if (startYear.value && endYear.value && startYear.value > endYear.value) {
    alert('End year must be greater than or equal to start year.')
    return
  }

  loading.value = true
  currentPage.value = 1

  try {
    const params = { metric: selectedMetric.value }
    if (startYear.value) params.start_year = startYear.value
    if (endYear.value) params.end_year = endYear.value

    const res = await api.get(`/year-summary`, { params })
    yearTableRows.value = res.data
    showModalYear.value = true
    showModalCategory.value = false
  } catch (error) {
    console.error(error)
    alert('Failed to load data.')
  } finally {
    loading.value = false
  }
}

// ----------------------------- FETCH CATEGORY SUMMARY -----------------------------
async function generateCategoryTable() {
  if (!selectedCategory.value) return
  if (startYear.value && endYear.value && startYear.value > endYear.value) {
    alert('End year must be greater than or equal to start year.')
    return
  }

  loading.value = true
  currentPage.value = 1

  try {
    const params = { category: selectedCategory.value }
    if (startYear.value) params.start_year = startYear.value
    if (endYear.value) params.end_year = endYear.value

    const res = await api.get(`/category-summary`, { params })
    categoryTableRows.value = res.data
    showModalCategory.value = true
    showModalYear.value = false
  } catch (error) {
    console.error(error)
    alert('Failed to load data.')
  } finally {
    loading.value = false
  }
}

// ----------------------------- CELL STYLE -----------------------------
function getMinimalStyle(val, row) {
  const vals = Object.values(row.values)
  const max = Math.max(...vals)
  const intensity = val / (max || 1)
  return {
    backgroundColor: intensity > 0.8 ? '#e0f2fe' : 'transparent',
    color: intensity > 0.8 ? '#0369a1' : '#475569',
    fontWeight: intensity > 0.8 ? '700' : '400',
    borderRadius: '4px'
  }
}
</script>

<style scoped>
/* ===== EXISTING STYLES ===== */
.control-grid.three-col {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 20px;
  padding: 0px 25px;
}

.range-wrapper {
  display: flex;
  align-items: center;
  gap: 8px;
}

.modern-select.small {
  flex: 1;
  min-width: 0;
}

.range-sep {
  color: #94a3b8;
  font-weight: 600;
}

.range-hint {
  font-size: 11px;
  color: #dc2626;
  margin-top: 6px;
}

.range-badge {
  background: #e0f2fe;
  color: #0369a1;
  font-size: 11px;
  padding: 2px 8px;
  border-radius: 12px;
  margin-left: 10px;
}

.summary-container {
  position: relative;
  z-index: 2;
  margin-top: -40px;
  max-width: 1400px;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
  color: #1e293b;
  background: #e9ef99;
  min-height: 280px;
  border-radius: 20px;
}

.dashboard-header { margin-bottom: 30px; }
.main-title { font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; padding-top: 30px; padding-left: 10px; }
.main-title span { color: #6a6262;}
.subtitle { color: #000000; font-size: 14px; margin-top: 4px; padding-left: 10px; }

.control-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 0px 25px; }
.filter-group {
  background: #ffffff;
  padding: 20px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}
.filter-group label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 10px; }
.input-wrapper { display: flex; gap: 10px; }

.modern-select {
  flex: 1;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  font-size: 14px;
}

.btn { padding: 10px 18px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; transition: 0.2s; }
.btn-primary { background: #0ea5e9; color: white; }
.btn-primary:hover { background: #0284c7; }
.btn-outline { background: white; border: 1px solid #e2e8f0; color: #475569; }
.btn-outline:hover { background: #f8fafc; }

/* ===== MODAL OVERLAY – FULL VIEWPORT ===== */
.modal-overlay {
  position: fixed !important;
  inset: 0 !important;
  background: rgba(255, 255, 255, 0.65) !important;
  backdrop-filter: blur(10px) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  z-index: 100000 !important; /* above sidebar */
}

.modal-window {
  width: 95%;
  max-width: 1150px;
  max-height: 90vh;
  background: #ffffff;
  border-radius: 20px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  color: #1e293b;
}

.modal-head {
  padding: 16px 24px;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f9f9f9;
}
.badge { background: #e0f2fe; color: #0369a1; font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 4px; }
.close-icon { background: none; border: none; font-size: 18px; cursor: pointer; color: #94a3b8; }

.table-responsive { padding: 20px 24px; max-height: 60vh; overflow-x: auto; }
.real-table { width: 100%; border-collapse: separate; border-spacing: 0 4px; }

.real-table th { 
  padding: 14px 12px; 
  font-size: 11px; 
  color: #0369a1; 
  text-transform: uppercase; 
  font-weight: 700; 
  text-align: center;
  background-color: #f0f9ff; 
  border-bottom: 2px solid #e0f2fe;
}

.real-table th.sticky-col { 
  text-align: left; 
  position: sticky; 
  left: 0; 
  background-color: #f0f9ff; 
  z-index: 10; 
}

.real-table td.sticky-col { 
  text-align: left; 
  font-weight: 600; 
  color: #0f172a; 
  position: sticky; 
  left: 0; 
  background: #f8fafc; 
  z-index: 5; 
  border-left: 1px solid #f1f5f9; 
  border-radius: 8px 0 0 8px; 
}

.real-table td { padding: 12px; background: #ffffff; border-top: 1px solid #f8fafc; border-bottom: 1px solid #f8fafc; font-size: 13px; text-align: center; }

.total-cell { font-weight: 700; color: #0f172a; background: #f1f5f9 !important; }
.citation-cell { font-weight: 700; color: #0ea5e9; background: #f0f9ff !important; border-radius: 0 8px 8px 0; }
.avg-cell { background: #f0fdf4 !important; font-weight: 700; color: #16a34a; border-radius: 0 8px 8px 0; }

.pagination-footer { padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; background: #f8fafc; }
.p-btn { padding: 6px 12px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; cursor: pointer; font-size: 12px; }
.p-btn:disabled { opacity: 0.5; }
.btn-preview {
    background: #6b21a5;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}
.btn-preview:hover {
    background: #4c1d95;
}
</style>