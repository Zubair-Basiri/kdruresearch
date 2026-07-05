<template>
  <div class="p-6 faculty-summary">

    <h2 class="title">Faculty Summary Analytics</h2>

    <!-- FILTER AREA -->
    <div class="filter-card">
      <select v-model="selectedMetric" class="form-select">
        <option disabled value="">Select Summary Dimension</option>
        <option v-for="m in metrics" :key="m" :value="m">
          {{ m }}
        </option>
      </select>

      <button class="btn-primary" @click="generateTable">
        Generate Summary
      </button>
    </div>

    <!-- MODAL (Teleported to body) -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="clearTable">
        <div class="modal-card">

          <!-- HEADER -->
          <div class="modal-header">
            <h3>{{ selectedMetric }}</h3>
            <button class="close-btn" @click="clearTable">✕</button>
          </div>

          <!-- TABLE -->
          <div class="modal-body">
            <p>Showing summary of <strong>{{ selectedMetric }}</strong> across faculties.</p>
            <table class="academic-table">
              <thead>
                <tr>
                  <th class="corner-header">{{ selectedMetric }}</th>
                  <th
                    v-for="faculty in faculties"
                    :key="faculty"
                    class="faculty-header"
                  >
                    {{ faculty }}
                  </th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(row) in paginatedRows" :key="row.label">
                  <td class="row-header">{{ row.label }}</td>
                  <td
                    v-for="faculty in faculties"
                    :key="faculty"
                    class="data-cell"
                    :style="heatMapStyle(row.values[faculty])"
                  >
                    {{ row.values[faculty] ?? 0 }}
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- PAGINATION -->
            <div class="pagination">
              <button @click="page--" :disabled="page === 1">Prev</button>
              <span>Page {{ page }} / {{ totalPages }}</span>
              <button @click="page++" :disabled="page === totalPages">Next</button>
            </div>
            <div class="pdf-export-bar">
              <button class="btn-pdf" @click="exportPdf">📄 Export as PDF</button>
            </div>
          </div>

        </div>
      </div>
    </Teleport>

  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import api from '@/services/api'

/* ================= UI STATE ================= */
const showModal = ref(false)
const page = ref(1)
const perPage = ref(8)
const selectedMetric = ref('')

/* ================= STATIC DATA ================= */
const faculties = [
  "Computer Science","Economics","Education","Engineering","Journalism",
  "Law & Political Science","Languages & Literature","Medicine",
  "Pharmacy","Public Administration","Shariah"
]

const metrics = [
  "Publication Type / Faculty",
  "Academic Grade / Faculty",
  "Education / Faculty",
  "Department / Faculty",
  "Indexed / Faculty",
  "Research Area / Faculty",
  "Publication Language / Faculty",
  "Year / Faculty",
  "Researchers / Faculty"
]

/* ================= UI TABLE DATA ================= */
const tableRows = ref([])

/* ================= PAGINATION ================= */
const totalPages = computed(() =>
  Math.ceil(tableRows.value.length / perPage.value)
)

const paginatedRows = computed(() =>
  tableRows.value.slice(
    (page.value - 1) * perPage.value,
    page.value * perPage.value
  )
)

/* ================= TABLE GENERATION ================= */
async function generateTable() {
  if (!selectedMetric.value) return

  try {
    const res = await api.get(`/faculty-summary`, {
      params: { metric: selectedMetric.value }
    })

    tableRows.value = res.data
    page.value = 1
    showModal.value = true

  } catch (err) {
    console.error(err)
    alert('Failed to load faculty summary data')
  }
}

function clearTable() {
  showModal.value = false
  tableRows.value = []
}

/* ================= HEATMAP ================= */
function heatMapStyle(value) {
  if (value == null) return { backgroundColor: "#ffffff" }

  const max = 120
  const intensity = Math.min(value / max, 1)

  return {
    backgroundColor: `rgb(255, ${255 - intensity * 120}, ${255 - intensity * 120})`,
    fontWeight: intensity > 0.8 ? "700" : "500"
  }
}

/* ================= PDF COMPUTED DATA ================= */
// const pdfTableHeaders = computed(() => [
//   'شاخص / پوهنځی',
//   ...faculties
// ])

// const pdfTableRows = computed(() =>
//   tableRows.value.map(row => [
//     row.label,
//     ...faculties.map(f => row.values[f] ?? 0)
//   ])
// )

/* ================= PDF GENERATION ================= */
const exportPdf = () => {
    if (!selectedMetric.value) return;
    const url = `${api.defaults.baseURL}/api/faculty-summary/preview?metric=${encodeURIComponent(selectedMetric.value)}`;
    window.open(url, '_blank');
};

/* ================= KEYBOARD ================= */
window.addEventListener('keydown', e => {
  if (e.key === 'Escape') showModal.value = false
})
</script>

<style scoped>
.faculty-summary{
  position: relative;
  z-index: 2;
  margin-top: -30px;
  background:#c7dbe7;
  border-radius:14px;
  padding:24px;
  font-family:'Segoe UI',sans-serif;
}

.title{
  font-size:26px;
  font-weight:800;
  color:#750f54;
  margin-bottom:18px;
}

/* Filter */
.filter-card{
  display:flex;
  gap:12px;
  margin-bottom:18px;
}

.form-select{
  flex: 1;
  padding:8px 14px;
  border-radius:24px;
  border:1px solid #cbd5e1;
  background:#fff;
  color: #000;
  font-weight:600;
}

/* Buttons */
.btn-primary{
  background:#2563eb;
  color:#fff;
  border:none;
  padding:10px 18px;
  border-radius:10px;
  font-weight:700;
  cursor:pointer;
}

/* Modal */
.modal-overlay {
  position: fixed !important;
  inset: 0 !important;
  background: rgba(255, 255, 255, 0.65) !important;
  backdrop-filter: blur(14px) !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  z-index: 100000 !important;
}

.modal-card {
  background: #ffffff;
  width: 95%;
  max-width: 1500px;
  max-height: 85vh;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 60px rgba(0,0,0,0.25);
  overflow: hidden;
}

.modal-header{
  display:flex;
  justify-content:space-between;
  padding:10px 20px;
  background:#77b8e4;
  color:#fff;
  font-weight:800;
  border-radius:18px 18px 0 0;
}

.close-btn{
  background:none;
  border:none;
  color:#fff;
  font-size:20px;
  cursor:pointer;
}

/* Table */

.academic-table {
  background: #ffffff;    /* pure white table */
}

.summary-table{
  width:100%;
  border-collapse:collapse;
  font-size:13px;
}

.corner-header{
  background:#1e3a8a;
  color:#fff;
  padding:10px;
  width:220px;
}

.faculty-header{
  width:60px;
  background:#e5edff;
}

.vertical-text{
  writing-mode:vertical-rl;
  transform:rotate(180deg);
  font-weight:700;
}

.row-header {
  background: #edf6fa;    /* subtle cyan like image */
  font-weight: 700;
  color: #0b3954;
}

.data-cell{
  text-align:center;
  padding:8px;
  border:1px solid #e5e7eb;
}

.row-even td{ background:#ffffff }
.row-odd td{ background:#f8fafc }

.modal-body{
  padding:16px;
  overflow:auto;
}

/* Pagination */
.pagination{
  display:flex;
  justify-content:center;
  gap:12px;
  margin-top:14px;
}
.pagination button{
  padding:6px 14px;
  border-radius:8px;
  border:none;
  background:#2563eb;
  color:#fff;
}
.pagination button:disabled{
  opacity:.4;
}

/* ===== TABLE CONTAINER ===== */
.academic-table {
  width: 100%;
  border-collapse: collapse;
  font-family: "Segoe UI", Arial, sans-serif;
  font-size: 13px;
  background: #e9f6fb;              /* image background */
  border: 1px solid #b6d7e2;
}

/* ===== HEADER (LIGHT ACADEMIC STYLE) ===== */
.academic-table thead th {
  background: #0f4c75;              /* light cyan (image-like) */
  color: #ffffff;                   /* dark teal text */
  padding: 10px 8px;
  font-weight: 700;
  text-align: center;
  border-right: 1px solid #b6d7e2;
  border-bottom: 2px solid #b6d7e2;
}

/* Top-left corner header */
.corner-header {
  background: #cfe9f3;              /* slightly stronger but still light */
  color: #0b3954;
  text-align: left;
  padding-left: 12px;
  min-width: 220px;
}

/* ===== ROW HEADER (left column) ===== */
.row-header {
  background: #d9eef7;
  font-weight: 700;
  color: #0b3954;
  padding: 8px 12px;
  border-right: 1px solid #b6d7e2;
  white-space: nowrap;
}

/* ===== DATA CELLS ===== */
.data-cell {
  padding: 8px;
  text-align: center;
  color: #0f172a;
  border-right: 1px solid #d1e5ee;
  border-bottom: 1px solid #d1e5ee;
}

/* ===== ZEBRA STRIPES ===== */
.academic-table tbody tr:nth-child(even) {
  background: #f3fbfe;
}

.academic-table tbody tr:nth-child(odd) {
  background: #ffffff;
}

/* ===== HOVER (very subtle) ===== */
.academic-table tbody tr:hover {
  background: #e2f2f9;
}

.table-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-bottom: 12px;
  flex-wrap: nowrap;  /* ensure buttons stay on same line */
}

.btn {
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid #cbd5e1;
  background: #f8fafc;
  color: #1e293b;
}

.btn:hover {
  background: #eef2f7;
}

.pdf-export-bar {
  text-align: left;
  margin-bottom: 20px;
}
.btn-pdf {
  background: #dc2626;
  color: white;
  border: none;
  padding: 8px 20px;
  border-radius: 30px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-pdf:hover {
  background: #b91c1c;
}

</style>
