<template>
  <div class="academic-container">
    <div class="header-section">
      <h2 class="academic-title">Top Researchers</h2>
      <p class="academic-subtitle">Annual Academic Output & Citation Analytics</p>
    </div>

    <div class="control-panel">
      <div class="filter-row">
        <input
          v-model="search"
          type="text"
          placeholder="Search by name..."
          class="search-input"
        />

        <select v-model="topField" class="form-select">
          <option value="" disabled>Rank by metric...</option>
          <option value="All">View All Columns (Full Report)</option>
          <option v-for="f in numericFields" :key="f" :value="f">{{ f }}</option>
        </select>

        <div class="button-group">
          <button class="btn btn-primary" @click="generateTable">Generate Report</button>
          <button class="btn btn-outline" @click="clearTable">Reset</button>
          <button class="btn btn-success" @click="downloadPDF">Export PDF (A4)</button>
        </div>
      </div>

      <div v-if="topField !== 'All'" class="field-selection">
        <p class="selection-label">Select columns to display:</p>
        <div class="field-grid">
          <label v-for="field in fields" :key="field" class="checkbox-item">
            <input type="checkbox" :value="field" v-model="selectedFields" />
            <span>{{ field }}</span>
          </label>
        </div>
      </div>
    </div>

    <div v-if="paginatedData.length" class="table-container-a4">
      <table class="academic-table" :class="{ 'mode-all': topField === 'All' }">
        <thead>
          <tr>
            <th 
              v-for="col in tableColumns" 
              :key="col" 
              :class="{
                'rotated-th': topField === 'All',
                'left-align-header': col === 'Researcher Name' || col === 'Faculty' || col === 'Department' || col === 'Academic Grade'
              }"
            >
              <div class="th-content">
                <span>{{ col }}</span>
              </div>
            </th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(row, index) in paginatedData" :key="index" :class="topRowClass(index)">
            <td 
              v-for="col in tableColumns" 
              :key="col"
              :class="{
                'left-align-data': col === 'Researcher Name' || col === 'Faculty' || col === 'Department' || col === 'Academic Grade'
              }"
            >
              {{ row[col] ?? 0 }}
            </td>
          </tr>
        </tbody>
      </table>

      <div class="pagination-ui">
        <button @click="page--" :disabled="page === 1" class="page-btn">← Prev</button>
        <span class="page-info">Page {{ page }} of {{ totalPages }}</span>
        <button @click="page++" :disabled="page === totalPages" class="page-btn">Next →</button>
      </div>
    </div>
    <div v-else class="no-data-message">
      No researchers found for the selected metric.
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/services/api'

/* ================= CONFIG ================= */
const fields = [
  "Researcher Name", "Faculty", "Department", "Academic Grade",
  "Non-indexed National Conference Proceedings",
  "Total Indexed Conference Proceedings",
  "Total Book Chapters",
  "Books Published (Academic)",
  "Books Published (Non-academic)",
  "Non-indexed International Conference Proceedings",
  "Total International Conference Poster Presentation",
  "Book Translations",
  "National Publications",
  "Q1 Indexed",
  "Q2 Indexed",
  "Q3 Indexed",
  "Q4 Indexed",
  "Other Recognized Index",
  "Internationally Peer Reviewed Journals",
  "1st Author Position",
  "2nd and 3rd Author Position",
  "4th and 5th Author Position",
  "Other Author Position",
  "Research Funding Sources",
  "H index",
  "Total Citations",
  "Digital Course Development",
  "Weighted Score",
  "Rank"
]

const numericFields = fields.slice(4) // Numeric fields for sorting

/* ================= STATE ================= */
const selectedFields = ref([])
const topField = ref('All') // Default to All for visual impact
const tableColumns = ref([])
const tableData = ref([])
const search = ref('')
const page = ref(1)
const perPage = 10

const researchers = ref([])

onMounted(async () => {
  try {
    const response = await api.get('/top-researchers')
    researchers.value = response.data.researchers
    generateTable() // re-run table generation after data loads
  } catch (error) {
    console.error('Failed to fetch researchers', error)
  }
})

// generateTable() now uses researchers.value instead of static array
function generateTable() {
  if (topField.value === 'All') {
    // Full report: show all columns, sort by overall Rank (from API)
    tableColumns.value = [...fields];
    tableData.value = [...researchers.value].sort((a, b) => (a.Rank || 999) - (b.Rank || 999));
  } else {
    // Metric‑specific view: start with selected columns (default: just "Researcher Name")
    let cols = selectedFields.value.length ? [...selectedFields.value] : ["Researcher Name"];
    // Automatically include "Rank" so the user always sees the metric‑based order
    if (!cols.includes('Rank')) {
      cols.push('Rank');
    }
    tableColumns.value = cols;

    // Filter researchers to only those with a value > 0 for the selected metric
    const filtered = researchers.value.filter(r => (r[topField.value] || 0) > 0);

    // Sort the filtered list by the selected metric (descending)
    const sorted = [...filtered].sort((a, b) => (b[topField.value] || 0) - (a[topField.value] || 0));

    // Override the "Rank" field with the metric‑based position (1,2,3...)
    tableData.value = sorted.map((item, index) => ({
      ...item,
      Rank: index + 1
    }));
  }
}

const filteredData = computed(() =>
  tableData.value.filter(r => r["Researcher Name"]?.toLowerCase().includes(search.value.toLowerCase()))
)

const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage))
const paginatedData = computed(() => filteredData.value.slice((page.value - 1) * perPage, page.value * perPage))

const topRowClass = i => i === 0 ? 'gold-row' : i === 1 ? 'silver-row' : i === 2 ? 'bronze-row' : ''

function clearTable() {
  tableData.value = []        // Remove table rows
  tableColumns.value = []     // Remove table headers
  selectedFields.value = []   // Deselect all checkboxes
  page.value = 1              // Reset pagination
  topField.value = ''         // Reset dropdown
}

/* ================= PDF EXPORT ================= */
async function downloadPDF() {
    try {
        const params = new URLSearchParams();
        params.append('search', search.value);
        params.append('topField', topField.value);
        if (selectedFields.value.length) {
            params.append('selectedFields', selectedFields.value.join(','));
        }
        const url = `${api.defaults.baseURL}/api/top-researchers/preview?${params.toString()}`;
        window.open(url, '_blank');
    } catch (error) {
        console.error('PDF generation failed', error);
        alert('Could not generate PDF. Please try again.');
    }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Libre+Baskerville:wght@700&display=swap');

.academic-container {
  position: relative;
  z-index: 2;
  margin-top: -30px;;
  font-family: 'Inter', sans-serif;
  padding: 40px;
  background-color: #fcfcfc;
  color: #1e293b;
  min-height: 100vh;
  border-radius: 24px;
}

/* TYPOGRAPHY */
.academic-title {
  font-family: 'Libre Baskerville', serif;
  font-size: 2.2rem;
  color: #0f172a;
  margin-bottom: 5px;
  text-align: center;
}
.academic-subtitle {
  text-align: center;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 2px;
  font-size: 0.85rem;
  margin-bottom: 40px;
}

/* CONTROLS */
.control-panel {
  background: white;
  padding: 24px;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  margin-bottom: 30px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.filter-row {
  display: flex;
  gap: 15px;
  flex-wrap: wrap; /* keep for small screens */
  align-items: center;
  margin-bottom: 20px;
}

.search-input, .form-select {
  padding: 10px 15px;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  font-size: 0.9rem;
  outline: none;
  color: #000000;
}
.search-input { flex: 1; min-width: 250px; }

/* BUTTONS */
.btn {
  padding: 10px 20px;
  font-weight: 600;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
  border: none;
}
.btn-primary { background: #0f172a; color: white; }
.btn-outline { background: transparent; border: 1px solid #cbd5e1; color: #475569; }
.btn-success { background: #166534; color: white; }
.btn:hover { opacity: 0.9; transform: translateY(-1px); }

.button-group {
  display: flex;
  gap: 10px;
}

/* FIELD SELECTION */
.field-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 8px;
  margin-top: 10px;
}
.checkbox-item {
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #475569;
}

/* TABLE STYLING (The A4 Landscape logic) */
.table-container-a4 {
  max-width: 1120px;              /* A4 Landscape width */
  width: 100%;
  margin: 0 auto;
  overflow-x: auto;               /* Horizontal scroll on small screens */
  background: white;
  padding: 20px;
  border: 1px solid #e2e8f0;
  box-sizing: border-box;
}

.academic-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

/* .academic-table th:first-child,
.academic-table td:first-child {
  position: sticky;
  left: 0;
  background: #f1f5f9;
  z-index: 2;
  font-weight: 600;
} */

.academic-table th, .academic-table td {
  border: 1px solid #e2e8f0;
  padding: 12px 8px;
  text-align: center;
}

.academic-table td {
  border: 1px solid #e2e8f0;
  padding: 8px 4px;
  text-align: center;
  vertical-align: middle; /* Keeps numbers centered under the rotated text */
}

.academic-table thead th {
  background: #8cb5f2;
  color: #000000;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
}

/* ROTATION LOGIC */
/* IMPROVED ROTATION LOGIC */
.mode-all th.rotated-th {
  height: 260px; /* Increased height to accommodate 2-line titles */
  width: 20px;   /* Slightly wider for better legibility */
  padding: 10px 0;
  position: relative;
  vertical-align: bottom;
  overflow: visible;
}

.rotated-th .th-content {
  position: absolute;
  bottom: 122px;   /* Lifted slightly from the bottom */
  left: 45%;
  transform: translateX(-50%) rotate(90deg);
  transform-origin: center;
  /* This width becomes the "height" of the text block once rotated */
  width: 245px;  
  display: flex;
  align-items: center;
  justify-content: flex-start;
}

.rotated-th .th-content span {
  display: block;
  width: 100%;
  white-space: normal;      /* Allows natural wrapping */
  word-break: keep-all;     /* Prevents breaking a single word */
  overflow-wrap: break-word; /* Allows wrap between words */
  line-height: 1.2;
  font-size: 8px;        /* Slightly smaller to fit better */
}

/* Header styling for the four specific columns */
.academic-table thead th.left-align-header {
  font-size: 20px;
}

/* Data cell left alignment for those columns */
.left-align-data {
  text-align: left !important;
}

.no-data-message {
  text-align: center;
  color: #64748b;
  font-size: 1.1rem;
  margin-top: 40px;
}

/* CONDENSED MODE FOR ALL */
.mode-all td {
  font-size: 0.75rem;
  padding: 6px 2px;
}

/* RANKING VISUALS */
.gold-row { background-color: #fffbeb !important; }
.silver-row { background-color: #f8fafc !important; }
.bronze-row { background-color: #fff7ed !important; }

.medal { font-size: 1.2rem; }
.rank-text { color: #94a3b8; font-weight: bold; }

/* PAGINATION */
.pagination-ui {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 30px;
}
.page-btn {
  background: none;
  border: 1px solid #e2e8f0;
  padding: 5px 15px;
  cursor: pointer;
  border-radius: 4px;
}
.page-info { font-size: 0.9rem; font-weight: 600; }

/* Mobile adjustments */
@media (max-width: 1024px) {
  .academic-table {
    font-size: 0.75rem;
  }

  .mode-all th.rotated-th {
    height: 240px;
    width: 45px;
  }

  .rotated-th .th-content {
    width: 245px;
  }
}

/* Very small screens */
@media (max-width: 768px) {
  .academic-container {
    padding: 20px;
  }

  .control-panel {
    padding: 16px;
  }

  .academic-table {
    min-width: 1000px;
  }

  .mode-all td {
    font-size: 0.7rem;
  }
}
</style>