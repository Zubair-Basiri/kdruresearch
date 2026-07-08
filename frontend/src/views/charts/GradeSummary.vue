<template>
  <div class="p-6 grade-summary-container">
    <h2 class="page-title">Grade Summary Analytics</h2>

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

    <Teleport to="body">
    <div v-if="showModal" class="modal-overlay" @click.self="clearTable">
      <div class="modal-card">
        <div class="modal-top-bar">
          <div class="table-id">
            <span class="table-text">{{ selectedMetric }}</span>
          </div>
          <div class="subtitle">Academic Grade Distribution & Paper Analytics</div>
          <button class="close-icon" @click="clearTable">✕</button>
        </div>

        <div class="modal-body">
          <div class="table-container">
            <table class="premium-table">
              <thead>
                <tr>
                  <th class="row-label-header">
                    {{ selectedMetric.split('/')[0] || 'Classification' }}
                  </th>
                  <th v-for="col in tableColumns" :key="col" class="vertical-header">
                    <div class="vertical-text">{{ col }}</div>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, index) in paginatedRows" :key="index">
                  <td class="row-label">{{ row.label }}</td>
                  <td v-for="col in tableColumns" :key="col" class="data-cell">
                    {{ row.values[col].toLocaleString() }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="modal-footer">
          
            <div class="pagination-controls">
              <button v-if="!authStore.isGuest" class="btn-preview" @click="previewPdf">Preview & Download PDF</button>
              <button :disabled="page === 1" @click="page--">PREVIOUS</button>
              <span class="page-info">PAGE {{ page }} OF {{ totalPages }}</span>
              <button :disabled="page === totalPages" @click="page++">NEXT</button>
            </div>
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
import { useAuthStore } from '@/stores/auth';
const authStore = useAuthStore();

const showModal = ref(false)
const page = ref(1)
const perPage = ref(6) // Matches the image's row count roughly
const selectedMetric = ref('')

const metrics = [
  "Faculty/Grade", "Department/Grade", "Researchers/Grade", 
  "Publication Type/Grade", "Funding Source/Grade", "Indexed/Grade", 
  "Collaboration/Grade", "Status/Grade", "Qualification/Grade", 
  "Language/Grade"
]

const tableColumns = [
  "Total papers", "Jr. Teaching Assist.", "Teaching Assistant", 
  "Sr. Teaching Assistant", "Assist. Prof.", "Assoc. Prof.", 
  "Professor", "Total Citations"
]

const tableRows = ref([])

async function generateTable() {

  if (!selectedMetric.value) return

  try {
    const res = await api.get(`/grade-summary`, {
      params: { metric: selectedMetric.value }
    })

    tableRows.value = res.data
    page.value = 1
    showModal.value = true

  } catch (error) {
    console.error(error)
    alert('Failed to load data')
  }
}

const totalPages = computed(() => Math.ceil(tableRows.value.length / perPage.value))
const paginatedRows = computed(() => {
  const start = (page.value - 1) * perPage.value
  return tableRows.value.slice(start, start + perPage.value)
})


const previewPdf = () => {
  if (!selectedMetric.value) return;
  const url = `${api.defaults.baseURL}/api/grade-summary/preview?metric=${encodeURIComponent(selectedMetric.value)}`;
  window.open(url, '_blank');
};

function clearTable() {
  showModal.value = false
}
</script>

<style scoped>
.grade-summary-container {
  background: rgba(170, 204, 237, 0.8);
  backdrop-filter: blur(8px); /* subtle blur effect */
  font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
  border-radius: 24px;
  padding: 24px;
}

/* TITLE */
.page-title {
  color: #2c3e50;
  font-size: 28px;
  font-weight: 800;
  margin-bottom: 18px;
  text-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* FILTER CARD */
.filter-card {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
}

/* SELECT */
.form-select {
  flex: 1;
  padding: 10px 16px;
  border-radius: 12px;
  border: 1px solid #ccc;
  background: #fff;
  font-weight: 600;
  color: #333;
  transition: all 0.2s ease;
}
.form-select:focus {
  border-color: #4e73df;
  box-shadow: 0 0 0 2px rgba(78,115,223,0.2);
  outline: none;
}

/* BUTTON */
.btn-primary {
  background: #4e73df;
  color: #fff;
  border: none;
  padding: 10px 20px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-primary:hover {
  background: #3751a3;
}

.modal-overlay {
  position: fixed;
  inset: 0;

  background: rgba(255, 255, 255, 0.65);
  backdrop-filter: blur(14px);

  display: flex;
  align-items: center;
  justify-content: center;

  z-index: 9999;
}

.modal-card {
  width: 90vw;
  height: 90vh;

  background: #fff;
  border-radius: 20px;

  display: flex;
  flex-direction: column;
  overflow: hidden;

  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.25);
}

@keyframes popIn {
  from {
    transform: scale(0.96);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}

.modal-body {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: auto;   /* 🔑 INTERNAL SCROLL */
}

/* MODAL TOP BAR */
.modal-top-bar {
  display: flex;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #eaeaea;
  background: #f9f9f9;
}

.table-text {
  font-size: 2rem;
  font-weight: 800;
  color: #34495e;
}
.subtitle {
  margin-left: 20px;
  color: #7f8c8d;
  font-size: 0.85rem;
}
.close-icon {
  background: none;
  border: none;
  font-size: 1.2rem;
  margin-left: auto;
  cursor: pointer;
  color: #999;
  transition: color 0.2s;
}
.close-icon:hover { color: #e74c3c; }

/* TABLE CONTAINER */
.table-container {
  padding: 2rem;
  overflow: auto;
}

/* TABLE STYLING */
.premium-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 5px; /* subtle row spacing for modern look */
  font-size: 0.85rem;
  color: #2c3e50;
}

/* HEADER */
.row-label-header {
  background: #34495e;
  color: #fff;
  text-align: left;
  padding: 1rem;
  font-size: 0.8rem;
  border-top-left-radius: 8px;
  border-bottom-left-radius: 8px;
}

.vertical-header {
  background: #2c3e50;
  color: #fff;
  height: 140px;
  vertical-align: bottom;
  padding-bottom: 15px;
  border-left: 1px solid #fff;
  border-radius: 0 0 8px 8px;
  white-space: normal;
}

.vertical-text {
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  text-transform: uppercase;
  font-weight: 600;
  font-size: 0.75rem;
  color: #fff;
  letter-spacing: 1px;
  margin: 0 auto;
}

/* ROW LABEL */
.row-label {
  background: #ecf0f1;
  font-weight: 600;
  padding: 12px 20px;
  border-left: 4px solid #4e73df;
  border-bottom: none;
  border-radius: 6px 0 0 6px;
}

/* DATA CELL */
.data-cell {
  text-align: center;
  background: #fdfdfd;
  border-bottom: 1px solid #e0e0e0;
  border-right: 1px solid #e0e0e0;
  padding: 12px 8px;
  border-radius: 0 6px 6px 0;
  transition: all 0.2s ease;
}
.data-cell:hover {
  background: #f1f5f9;
  font-weight: 600;
  color: #34495e;
}

/* HIGHLIGHT TOP VALUE */
.data-cell[data-highest="true"] {
  background: #ffec3d;
  color: #2c3e50;
  font-weight: 700;
}

/* FOOTER */
.pdf-actions {
  display: flex;
  gap: 0.5rem;
}
.btn-outline {
  background: transparent;
  border: 1px solid #0d6efd;
  color: #0d6efd;
  padding: 0.375rem 0.75rem;
  border-radius: 0.25rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-outline:hover {
  background: #0d6efd;
  color: #fff;
}
.btn-outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.modal-footer {
  display: flex;
  align-items: center;
  margin-top: 1rem;
}

.pagination-controls button {
  background: #f4f4f4;
  border: 1px solid #ccc;
  padding: 5px 12px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  border-radius: 6px;
  margin: 0 3px;
  transition: all 0.2s ease;
}
.pagination-controls button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.pagination-controls button:hover:not(:disabled) {
  background: #4e73df;
  color: #fff;
}

.page-info {
  margin: 0 10px;
  font-size: 0.75rem;
  font-weight: 600;
  color: #34495e;
}
</style>
