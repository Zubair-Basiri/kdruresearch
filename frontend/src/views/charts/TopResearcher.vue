<template>
  <div class="academic-container">
    <div class="header-section">
      <h2 class="academic-title">Top Researchers</h2>
      <p class="academic-subtitle">Annual Academic Output & Citation Analytics</p>
    </div>

    <!-- Controls -->
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
          <option value="All">View All Metrics (Full Profile)</option>
          <option value="Top in Faculty">Top in Faculty</option>
          <option v-for="f in numericFields" :key="f" :value="f">{{ f }}</option>
        </select>

        <!-- Top in Faculty: number input and faculty dropdown -->
        <div v-if="topField === 'Top in Faculty'" class="faculty-rank-input">
          <label>Top per faculty:</label>
          <input
            type="number"
            v-model.number="topInFacultyCount"
            min="1"
            max="20"
            class="form-control number-input"
          />
        </div>

        <div v-if="topField === 'Top in Faculty'" class="faculty-filter">
          <label>Faculty:</label>
          <select v-model="selectedFaculty" class="form-select faculty-select">
            <option value="All">All Faculties</option>
            <option v-for="faculty in facultyOptions" :key="faculty" :value="faculty">
              {{ faculty }}
            </option>
          </select>
        </div>

        <div class="button-group">
          <button class="btn btn-primary" @click="generateTable">Generate Report</button>
          <button class="btn btn-outline" @click="clearTable">Reset</button>
          <button v-if="!authStore.isGuest" class="btn btn-success" @click="downloadPDF">Export PDF (A4)</button>
        </div>
      </div>

      <!-- Field selection (only for metric‑specific view) -->
      <div v-if="topField !== 'All' && topField !== 'Top in Faculty'" class="field-selection">
        <p class="selection-label">Select columns to display:</p>
        <div class="field-grid">
          <label v-for="field in metricFields" :key="field" class="checkbox-item">
            <input type="checkbox" :value="field" v-model="selectedFields" />
            <span>{{ field }}</span>
          </label>
        </div>
        <div class="field-actions">
          <button class="text-link" @click="selectAllFields">Select All</button>
          <span class="sep">|</span>
          <button class="text-link" @click="clearAllFields">Clear All</button>
        </div>
      </div>
    </div>

    <!-- Cards -->
    <div v-if="paginatedData.length" class="card-grid">
      <div
        v-for="(researcher, index) in paginatedData"
        :key="index"
        class="researcher-card"
      >
        <!-- Rank badges -->
        <div class="rank-badges">
            <div class="rank-badge" :class="getRankClass(researcher.Rank)">
                <span class="rank-number">{{ researcher.Rank }}</span>
                <span class="rank-label">University</span>
            </div>
            <div class="rank-badge" :class="getRankClass(researcher['Faculty Rank'])">
                <span class="rank-number">{{ researcher['Faculty Rank'] }}</span>
                <span class="rank-label">Faculty</span>
            </div>
        </div>

        <!-- Header -->
        <div class="card-header">
          <h3 class="researcher-name">{{ researcher['Researcher Name'] }}</h3>
          <div class="meta-info">
            <span class="meta-item"><strong>Faculty:</strong> {{ researcher['Faculty'] || '-' }}</span>
            <span class="meta-item"><strong>Department:</strong> {{ researcher['Department'] || '-' }}</span>
            <span class="meta-item"><strong>Academic Grade:</strong> {{ researcher['Academic Grade'] || '-' }}</span>
          </div>
        </div>

        <!-- Metrics – show only selected fields (or all if 'All' or 'Top in Faculty') -->
        <div class="metrics-sections">
          <div v-if="shouldShowGroup('indexed')" class="metric-group">
            <h5 class="group-title"><i class="bi bi-journal-richtext"></i> Indexed Papers</h5>
            <div class="metrics-grid">
              <div
                v-for="key in indexedKeys"
                :key="key"
                class="metric-item"
                v-show="isFieldSelected(key)"
              >
                <span class="metric-label">{{ formatKey(key) }}</span>
                <span class="metric-value">{{ researcher[key] ?? 0 }}</span>
              </div>
            </div>
          </div>

          <div v-if="shouldShowGroup('books')" class="metric-group">
            <h5 class="group-title"><i class="bi bi-book"></i> Books</h5>
            <div class="metrics-grid">
              <div
                v-for="key in bookKeys"
                :key="key"
                class="metric-item"
                v-show="isFieldSelected(key)"
              >
                <span class="metric-label">{{ formatKey(key) }}</span>
                <span class="metric-value">{{ researcher[key] ?? 0 }}</span>
              </div>
            </div>
          </div>

          <div v-if="shouldShowGroup('author')" class="metric-group">
            <h5 class="group-title"><i class="bi bi-person-badge"></i> Author Positions</h5>
            <div class="metrics-grid">
              <div
                v-for="key in authorKeys"
                :key="key"
                class="metric-item"
                v-show="isFieldSelected(key)"
              >
                <span class="metric-label">{{ formatKey(key) }}</span>
                <span class="metric-value">{{ researcher[key] ?? 0 }}</span>
              </div>
            </div>
          </div>

          <div v-if="shouldShowGroup('conference')" class="metric-group">
            <h5 class="group-title"><i class="bi bi-mic"></i> Conferences</h5>
            <div class="metrics-grid">
              <div
                v-for="key in conferenceKeys"
                :key="key"
                class="metric-item"
                v-show="isFieldSelected(key)"
              >
                <span class="metric-label">{{ formatKey(key) }}</span>
                <span class="metric-value">{{ researcher[key] ?? 0 }}</span>
              </div>
            </div>
          </div>

          <div v-if="shouldShowGroup('other')" class="metric-group">
            <h5 class="group-title"><i class="bi bi-graph-up"></i> Other Metrics</h5>
            <div class="metrics-grid">
              <div
                v-for="key in otherKeys"
                :key="key"
                class="metric-item"
                v-show="isFieldSelected(key)"
              >
                <span class="metric-label">{{ formatKey(key) }}</span>
                <span class="metric-value">{{ researcher[key] ?? 0 }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Footer -->
        <div class="card-footer">
          <button class="btn btn-outline-primary btn-sm titles-btn" @click="fetchPapers(researcher)">
            <i class="bi bi-journal-text"></i> Titles
          </button>
          <span class="score"><strong>Weighted Score:</strong> {{ researcher['Weighted Score'] || '-' }}</span>
        </div>
      </div>
    </div>

    <div v-else-if="!loading" class="no-data-message">
      No researchers found for the selected metric.
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination-ui">
      <button @click="page--" :disabled="page === 1" class="page-btn">← Prev</button>
      <span class="page-info">Page {{ page }} of {{ totalPages }}</span>
      <button @click="page++" :disabled="page === totalPages" class="page-btn">Next →</button>
    </div>

    <!-- Papers Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <h5>{{ currentResearcherName }} – Papers</h5>
          <button class="close-btn" @click="closeModal">✕</button>
        </div>
        <div class="modal-body">
          <div v-if="loadingPapers" class="text-center py-4">
            <b-spinner variant="primary" label="Loading..."></b-spinner>
          </div>
          <div v-else-if="papers.length">
            <table class="papers-table">
              <thead>
                <tr>
                  <th>Year</th>
                  <th>Title</th>
                  <th>Publication</th>
                  <th>Indexed</th>
                  <th>Citations</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="paper in papers" :key="paper.id">
                  <td>{{ paper.year }}</td>
                  <td>{{ paper.title }}</td>
                  <td>{{ paper.publication }}</td>
                  <td>{{ paper.indexed || '-' }}</td>
                  <td>{{ paper.citation }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-4">No papers found.</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="closeModal">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore();

// ---------- CONFIG ----------
const allFields = [
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
];

// Group keys
const indexedKeys = ['Q1 Indexed', 'Q2 Indexed', 'Q3 Indexed', 'Q4 Indexed', 'Other Recognized Index'];
const bookKeys = ['Books Published (Academic)', 'Books Published (Non-academic)', 'Book Translations', 'Total Book Chapters'];
const authorKeys = ['1st Author Position', '2nd and 3rd Author Position', '4th and 5th Author Position', 'Other Author Position'];
const conferenceKeys = ['Non-indexed National Conference Proceedings', 'Total Indexed Conference Proceedings', 'Non-indexed International Conference Proceedings', 'Total International Conference Poster Presentation'];
const otherKeys = ['National Publications', 'Internationally Peer Reviewed Journals', 'Research Funding Sources', 'H index', 'Total Citations', 'Digital Course Development'];

const numericFields = allFields.slice(4);
const metricFields = allFields.slice(4, allFields.length - 2);

// ---------- STATE ----------
// Initialize with all metric fields selected by default (matching KeyFindings behavior)
const selectedFields = ref([...metricFields]);
const topField = ref('All');
const tableData = ref([]);
const search = ref('');
const page = ref(1);
const perPage = 9;
const topInFacultyCount = ref(3);
const selectedFaculty = ref('All');

const researchers = ref([]);
const loading = ref(false);

// Modal state
const showModal = ref(false);
const currentResearcherName = ref('');
const currentResearcherId = ref(null);
const papers = ref([]);
const loadingPapers = ref(false);

// ---------- COMPUTED ----------
const filteredData = computed(() => {
  const term = search.value.toLowerCase().trim();
  if (!term) return tableData.value;
  return tableData.value.filter(r =>
    r['Researcher Name']?.toLowerCase().includes(term) ||
    r['Faculty']?.toLowerCase().includes(term) ||
    r['Department']?.toLowerCase().includes(term)
  );
});

const totalPages = computed(() => Math.ceil(filteredData.value.length / perPage) || 1);
const paginatedData = computed(() => {
  const start = (page.value - 1) * perPage;
  return filteredData.value.slice(start, start + perPage);
});

const facultyOptions = computed(() => {
  const faculties = new Set();
  researchers.value.forEach(r => {
    const f = r['Faculty'];
    if (f) faculties.add(f);
  });
  return Array.from(faculties).sort();
});

// Check if a field is selected (or all fields are shown)
const isFieldSelected = (key) => {
  if (topField.value === 'All' || topField.value === 'Top in Faculty') return true;
  return selectedFields.value.includes(key);
};

// Helper to decide whether to show a metric group
const shouldShowGroup = (group) => {
  if (topField.value === 'All' || topField.value === 'Top in Faculty') return true;
  const keys = {
    indexed: indexedKeys,
    books: bookKeys,
    author: authorKeys,
    conference: conferenceKeys,
    other: otherKeys,
  };
  return keys[group].some(key => selectedFields.value.includes(key));
};

// ---------- METHODS ----------
function formatKey(key) {
  return key.replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace('Q1 Indexed', 'Q1')
    .replace('Q2 Indexed', 'Q2')
    .replace('Q3 Indexed', 'Q3')
    .replace('Q4 Indexed', 'Q4');
}

function getRankClass(rank) {
  if (rank === 1) return 'rank-gold';
  if (rank === 2) return 'rank-silver';
  if (rank === 3) return 'rank-bronze';
  return 'rank-default';
}

function generateTable() {
  if (topField.value === 'All') {
    tableData.value = [...researchers.value].sort((a, b) => (a.Rank || 999) - (b.Rank || 999));
  } else if (topField.value === 'Top in Faculty') {
    const count = Math.max(1, topInFacultyCount.value || 3);
    let filtered = researchers.value;
    if (selectedFaculty.value !== 'All') {
      filtered = filtered.filter(r => r['Faculty'] === selectedFaculty.value);
    }
    // Group by faculty
    const facultyMap = {};
    filtered.forEach(r => {
      const faculty = r['Faculty'] || 'Unknown';
      if (!facultyMap[faculty]) facultyMap[faculty] = [];
      facultyMap[faculty].push(r);
    });
    let result = [];
    for (const [, members] of Object.entries(facultyMap)) {
      const sorted = [...members].sort((a, b) => {
        const sa = a['Weighted Score'] !== '-' ? parseFloat(a['Weighted Score']) : -Infinity;
        const sb = b['Weighted Score'] !== '-' ? parseFloat(b['Weighted Score']) : -Infinity;
        return sb - sa;
      });
      const top = sorted.slice(0, count);
      top.forEach((item) => {
        result.push({
          ...item,
        });
      });
    }
    // Sort by faculty then rank
    result.sort((a, b) => {
      const fa = a['Faculty'] || '';
      const fb = b['Faculty'] || '';
      if (fa !== fb) return fa.localeCompare(fb);
      return a.Rank - b.Rank;
    });
    tableData.value = result;
  } else {
    // Metric-specific ranking
    const filtered = researchers.value.filter(r => (r[topField.value] || 0) > 0);
    const sorted = [...filtered].sort((a, b) => (b[topField.value] || 0) - (a[topField.value] || 0));
    tableData.value = sorted.map((item, index) => ({
      ...item,
      Rank: index + 1
    }));
  }
  page.value = 1;
}

function clearTable() {
  tableData.value = [];
  selectedFields.value = [...metricFields];
  page.value = 1;
  topField.value = 'All';
  topInFacultyCount.value = 3;
  selectedFaculty.value = 'All';
}

function selectAllFields() {
  selectedFields.value = [...metricFields];
}

function clearAllFields() {
  selectedFields.value = [];
}

async function fetchPapers(researcher) {
  const lecturerId = researcher.id;
  if (!lecturerId) {
    alert('No lecturer ID found for this researcher.');
    return;
  }
  currentResearcherName.value = researcher['Researcher Name'];
  currentResearcherId.value = lecturerId;
  showModal.value = true;
  loadingPapers.value = true;
  papers.value = [];
  try {
    const response = await api.get(`/researcher-papers/${lecturerId}`);
    papers.value = response.data;
  } catch (error) {
    console.error('Failed to fetch papers:', error);
    papers.value = [];
  } finally {
    loadingPapers.value = false;
  }
}

function closeModal() {
  showModal.value = false;
  papers.value = [];
}

// Watch for changes to update table when filters change
watch([topField, selectedFaculty, topInFacultyCount], () => {
  if (topField.value !== 'All' && topField.value !== '') {
    generateTable();
  }
});

// When topField changes to a metric, ensure selectedFields has all columns by default
watch(topField, (newVal) => {
  if (newVal !== 'All' && newVal !== 'Top in Faculty' && selectedFields.value.length === 0) {
    selectedFields.value = [...metricFields];
  }
});

// ---------- DATA FETCH ----------
onMounted(async () => {
  loading.value = true;
  try {
    const response = await api.get('/top-researchers');
    researchers.value = response.data.researchers;
    generateTable();
  } catch (error) {
    console.error('Failed to fetch researchers', error);
  } finally {
    loading.value = false;
  }
});

// ---------- PDF EXPORT ----------
async function downloadPDF() {
  try {
    const params = new URLSearchParams();
    params.append('search', search.value);
    params.append('topField', topField.value);
    if (selectedFields.value.length) {
      params.append('selectedFields', selectedFields.value.join(','));
    }
    if (topField.value === 'Top in Faculty') {
      params.append('topInFacultyCount', String(topInFacultyCount.value));
      params.append('selectedFaculty', selectedFaculty.value);
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
/* -------------------- MAIN CONTAINER -------------------- */
.academic-container {
  position: relative;
  z-index: 2;
  margin-top: -30px;
  font-family: 'Inter', sans-serif;
  padding: 40px;
  background-color: #fcfcfc;
  color: #1e293b;
  min-height: 100vh;
  border-radius: 24px;
}

.header-section {
  text-align: center;
  margin-bottom: 30px;
}
.academic-title {
  font-family: 'Libre Baskerville', serif;
  font-size: 2.2rem;
  color: #0f172a;
  margin-bottom: 5px;
}
.academic-subtitle {
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 2px;
  font-size: 0.85rem;
}

/* -------------------- CONTROL PANEL -------------------- */
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
  flex-wrap: wrap;
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
.btn-sm { padding: 4px 12px; font-size: 0.8rem; }
.btn-outline-primary {
  border: 1px solid #3b82f6;
  color: #3b82f6;
  background: transparent;
}
.btn-outline-primary:hover {
  background: #3b82f6;
  color: white;
}

.button-group { display: flex; gap: 10px; }

.field-selection { margin-top: 15px; }
.field-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 8px;
}
.checkbox-item {
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #475569;
}

/* Top in Faculty filters */
.faculty-rank-input {
  display: flex;
  align-items: center;
  gap: 8px;
}
.faculty-rank-input label {
  font-weight: 500;
  white-space: nowrap;
  color: #475569;
}
.faculty-rank-input .number-input {
  width: 70px;
  padding: 6px 8px;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  text-align: center;
}

.faculty-filter {
  display: flex;
  align-items: center;
  gap: 8px;
}
.faculty-filter label {
  font-weight: 500;
  white-space: nowrap;
  color: #475569;
}
.faculty-select {
  width: 160px;
  padding: 6px 8px;
  border: 1px solid #cbd5e1;
  border-radius: 4px;
  background: white;
}

/* -------------------- CARDS -------------------- */
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
  gap: 24px;
  margin-top: 20px;
}

.researcher-card {
  background: linear-gradient(145deg, #cfdbf3 0%, #f4f7f9 100%);
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 20px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.04);
  position: relative;
  transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.researcher-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 28px rgba(0,0,0,0.10);
}

/* Rank badge */
.rank-badges {
    position: absolute;
    top: -12px;
    right: -12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rank-badge {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.0rem;
    color: white;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    line-height: 1.1;
}

.rank-label {
    font-size: 0.45rem;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    opacity: 0.9;
}
.rank-gold { background: linear-gradient(135deg, #fbbf24, #f59e0b); }
.rank-silver { background: linear-gradient(135deg, #cbd5e1, #94a3b8); }
.rank-bronze { background: linear-gradient(135deg, #f59e0b, #d97706); }
.rank-default { background: linear-gradient(135deg, #e2e8f0, #cbd5e1); color: #1e293b; }

.rank-number {
  line-height: 1;
}

/* Card Header */
.card-header {
  margin-bottom: 12px;
  padding-right: 40px;
  background: linear-gradient(90deg, rgba(10,36,99,0.04) 0%, transparent 100%);
  border-radius: 8px;
  padding: 8px 12px;
  margin-left: -8px;
  margin-right: -8px;
}
.researcher-name {
  font-size: 1.2rem;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #0a2463;
}
.meta-info {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  font-size: 0.85rem;
  color: #475569;
}
.meta-item strong {
  color: #1e293b;
}

/* Metrics Sections */
.metrics-sections {
  margin: 12px 0;
}
.metric-group {
  margin-bottom: 8px;
}
.group-title {
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #3dbed2;
  margin: 4px 0 6px 0;
  border-bottom: 1px solid #e2e8f0;
  padding-bottom: 2px;
}
.group-title i {
  margin-right: 4px;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2px 12px;
}
.metric-item {
  display: flex;
  justify-content: space-between;
  font-size: 0.8rem;
  padding: 2px 0;
}
.metric-label {
  color: #64748b;
}
.metric-value {
  font-weight: 600;
  color: #0f172a;
}

/* Card Footer */
.card-footer {
  border-top: 1px solid #f1f5f9;
  padding-top: 10px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.titles-btn {
  font-size: 0.75rem;
  background: #093080;
  font-weight: bolder;
}
.score {
  font-size: 0.85rem;
  color: #475569;
}
.score strong {
  color: #0f172a;
}

/* -------------------- MODAL -------------------- */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.modal-card {
  background: white;
  border-radius: 16px;
  max-width: 800px;
  width: 95%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h5 {
  margin: 0;
  font-weight: 700;
  color: #0f172a;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #94a3b8;
}
.close-btn:hover { color: #ef4444; }

.modal-body {
  padding: 16px 20px;
  overflow-y: auto;
  flex: 1;
}
.papers-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}
.papers-table th {
  background: #f1f5f9;
  padding: 8px 6px;
  text-align: left;
  font-weight: 600;
  color: #0f172a;
}
.papers-table td {
  padding: 6px;
  border-bottom: 1px solid #e2e8f0;
}
.modal-footer {
  padding: 12px 20px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
}
.btn-secondary {
  background: #e2e8f0;
  border: none;
  padding: 6px 16px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
}
.btn-secondary:hover { background: #cbd5e1; }

/* -------------------- PAGINATION -------------------- */
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
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.page-info {
  font-size: 0.9rem;
  font-weight: 600;
}

.no-data-message {
  text-align: center;
  padding: 60px;
  color: #94a3b8;
  font-size: 1.1rem;
}

.field-actions {
  margin-top: 8px;
  display: flex;
  gap: 6px;
  font-size: 12px;
}
.text-link {
  background: none;
  border: none;
  color: #3b82f6;
  cursor: pointer;
  padding: 0;
  font-weight: 500;
}
.text-link:hover {
  text-decoration: underline;
}
.sep {
  color: #cbd5e1;
}

/* -------------------- RESPONSIVE -------------------- */
@media (max-width: 768px) {
  .academic-container { padding: 20px; }
  .card-grid { grid-template-columns: 1fr; }
  .filter-row { flex-direction: column; }
  .search-input { min-width: 100%; }
  .metrics-grid { grid-template-columns: 1fr; }
}
</style>