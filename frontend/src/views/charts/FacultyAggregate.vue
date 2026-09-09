<template>
  <div class="academic-page">

    <!-- ================= HEADER ================= -->
    <header class="academic-header">
      <h1>Table Summary of Faculty per Various Factors</h1>
      <p>Institutional Research, Publications & Academic Performance</p>
    </header>

    <!-- ================= FACULTY SECTIONS ================= -->
    <template v-for="(f, index) in faculties" :key="f?.name || index">
      <section v-if="f" class="faculty-section">

        <!-- FACULTY TITLE -->
        <div class="faculty-title">
          <h2>{{ f.name }}</h2>
          <span class="subtitle">Academic Profile</span>
        </div>

        <!-- ================= METRICS ================= -->
        <div class="metrics-panel">
          <div class="metric-box">
            <label>Total Publications</label>
            <strong>{{ f.metrics.publications }}</strong>
          </div>
          <div class="metric-box">
            <label>Total Books</label>
            <strong>{{ f.metrics.books }}</strong>
          </div>
          <div class="metric-box">
            <label>Translated Books</label>
            <strong>{{ f.metrics.translations }}</strong>
          </div>
          <div class="metric-box">
            <label>Indexed Papers</label>
            <strong>{{ f.metrics.indexed }}</strong>
          </div>
          <div class="metric-box">
            <label>Non-Indexed Papers</label>
            <strong>{{ f.metrics.nonIndexed }}</strong>
          </div>
          <div class="metric-box accent">
            <label>Total Citations</label>
            <strong>{{ f.metrics.citations }}</strong>
          </div>
          <div class="metric-box">
            <label>H-Index</label>
            <strong>{{ f.metrics.hIndex }}</strong>
          </div>
          <div class="metric-box">
            <label>Q1</label>
            <strong>{{ f.metrics.Q1 }}</strong>
          </div>
          <div class="metric-box">
            <label>Q2</label>
            <strong>{{ f.metrics.Q2 }}</strong>
          </div>
          <div class="metric-box">
            <label>Q3</label>
            <strong>{{ f.metrics.Q3 }}</strong>
          </div>
          <div class="metric-box">
            <label>Q4</label>
            <strong>{{ f.metrics.Q4 }}</strong>
          </div>
          <div class="metric-box">
            <label>Total International Conferences</label>
            <strong>{{ f.metrics.internationalConferences }}</strong>
          </div>
          <div class="metric-box">
            <label>Total National Conferences</label>
            <strong>{{ f.metrics.nationalConferences }}</strong>
          </div>
          <div class="metric-box">
            <label>Peer Reviewed Article Journals</label>
            <strong>{{ f.metrics.peerReviewed }}</strong>
          </div>
          <div class="metric-box">
            <label>Case Studies</label>
            <strong>{{ f.metrics.caseStudies }}</strong>
          </div>
          <div class="metric-box">
            <label>Research Reports</label>
            <strong>{{ f.metrics.researchReports }}</strong>
          </div>
          <div class="metric-box">
            <label>First Authors</label>
            <strong>{{ f.metrics.firstAuthors }}</strong>
          </div>
          <div class="metric-box">
            <label>Second Authors</label>
            <strong>{{ f.metrics.secondAuthors }}</strong>
          </div>
          <div class="metric-box">
            <label>Third Authors</label>
            <strong>{{ f.metrics.thirdAuthors }}</strong>
          </div>
          <div class="metric-box">
            <label>Other Authors</label>
            <strong>{{ f.metrics.otherAuthors }}</strong>
          </div>
          <div class="metric-box">
            <label>Total Researchers</label>
            <strong>{{ f.metrics.totalResearchers }}</strong>
          </div>
        </div>

        <!-- ================= LEADERS ================= -->
        <div class="academic-card">
          <h3>Academic Leadership</h3>
          <div class="leader-list">
            <div><span>Top Researcher</span><b>{{ f.leaders.topResearcher }}</b></div>
            <div><span>Most Books</span><b>{{ f.leaders.mostBooks }}</b></div>
            <div><span>Most Translations</span><b>{{ f.leaders.mostTranslations }}</b></div>
            <div><span>Indexed Journals</span><b>{{ f.leaders.indexedJournals }}</b></div>
            <div><span>Peer-Reviewed Journals</span><b>{{ f.leaders.peerReviewed }}</b></div>
            <div><span>Top Department</span><b>{{ f.leaders.department }}</b></div>
            <div><span>Top Grade</span><b>{{ f.leaders.grade }}</b></div>
          </div>
        </div>

        <!-- ================= YEARLY ================= -->
        <div class="academic-card">
          <h3>Publications by Year</h3>
          <!-- Mobile View: Yearly data as cards -->
          <div class="yearly-mobile-view">
            <div v-for="y in years" :key="y" class="year-item">
              <span class="year-label">{{ y }}</span>
              <span class="year-value">{{ f.yearly[y] }}</span>
            </div>
            <div class="year-item total">
              <span class="year-label">Total</span>
              <span class="year-value">{{ yearTotal(f.yearly) }}</span>
            </div>
          </div>
          
          <!-- Desktop View: Yearly data as table -->
          <div class="yearly-desktop-view">
            <table class="academic-table">
              <thead>
                <tr>
                  <th v-for="y in years" :key="y">{{ y }}</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td v-for="y in years" :key="y">{{ f.yearly[y] }}</td>
                  <td class="total">{{ yearTotal(f.yearly) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================= LANGUAGE ================= -->
        <div class="academic-card">
          <h3>Publication Language Count</h3>
          <!-- Mobile View: Language as cards -->
          <div class="language-mobile-view">
            <div class="language-item">
              <span class="language-label">Pashto</span>
              <span class="language-value">{{ f.languages.pashto }}</span>
            </div>
            <div class="language-item">
              <span class="language-label">Dari</span>
              <span class="language-value">{{ f.languages.dari }}</span>
            </div>
            <div class="language-item">
              <span class="language-label">English</span>
              <span class="language-value">{{ f.languages.english }}</span>
            </div>
          </div>
          
          <!-- Desktop View: Language as table -->
          <div class="language-desktop-view">
            <table class="academic-table">
              <thead>
                <tr>
                  <th>Pashto</th>
                  <th>Dari</th>
                  <th>English</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{{ f.languages.pashto }}</td>
                  <td>{{ f.languages.dari }}</td>
                  <td>{{ f.languages.english }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================= FUNDING ================= -->
        <div class="academic-card">
          <h3>Funding Type</h3>
          <!-- Mobile View: Funding as cards -->
          <div class="funding-mobile-view">
            <div class="funding-item">
              <span class="funding-label">National / Funded</span>
              <span class="funding-value">{{ f.funding.funded }}</span>
            </div>
            <div class="funding-item">
              <span class="funding-label">Self-Funded</span>
              <span class="funding-value">{{ f.funding.self }}</span>
            </div>
          </div>
          
          <!-- Desktop View: Funding as table -->
          <div class="funding-desktop-view">
            <table class="academic-table">
              <thead>
                <tr>
                  <th>National / Funded</th>
                  <th>Self-Funded</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>{{ f.funding.funded }}</td>
                  <td>{{ f.funding.self }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </section>
    </template>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'

const years = ref([])
const faculties = ref([])

onMounted(async () => {
  const res = await api.get('/faculty-aggregation')
  years.value = res.data.years
  faculties.value = res.data.faculties
})

const yearTotal = y =>
  Object.values(y).reduce((a, b) => a + b, 0)
</script>

<style scoped>
/* ================= THEME ================= */
.academic-page {
  position: relative;
  z-index: 2;
  margin-top: -30px;
  background: #f6f7fb;
  padding: 32px;
  font-family: "Inter", "Segoe UI", system-ui, sans-serif;
  color: #0f172a;
  border-radius: 24px;
}

/* ================= HEADER ================= */
.academic-header {
  margin-bottom: 32px;
}
.academic-header h1 {
  font-size: 28px;
  font-weight: 800;
}
.academic-header p {
  color: #64748b;
  margin-top: 6px;
}

/* ================= FACULTY ================= */
.faculty-section {
  background: #ffffff;
  border-radius: 22px;
  padding: 28px;
  margin-bottom: 42px;
  box-shadow: 0 10px 30px rgba(15,23,42,0.06);
}

.faculty-title h2 {
  font-size: 22px;
  font-weight: 800;
  color: #1e1b4b;
}
.subtitle {
  font-size: 12px;
  color: #64748b;
}

/* ================= METRICS ================= */
.metrics-panel {
  margin: 18px 0 26px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 14px;
}

.metric-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 14px 16px;
}

.metric-box label {
  font-size: 10px;
  letter-spacing: 0.06em;
  color: #475569;
  text-transform: uppercase;
  display: block;
  margin-bottom: 4px;
}

.metric-box strong {
  font-size: 22px;
  font-weight: 800;
}

.metric-box.accent {
  background: #eef2ff;
  border-color: #c7d2fe;
}
.metric-box.accent strong {
  color: #1e3a8a;
}

/* ================= CARDS ================= */
.academic-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 18px;
  padding: 20px;
  margin-bottom: 22px;
}

.academic-card h3 {
  font-size: 14px;
  font-weight: 700;
  margin-bottom: 12px;
  color: #334155;
}

/* ================= LEADERS ================= */
.leader-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 10px;
}
.leader-list span {
  font-size: 12px;
  color: #64748b;
}
.leader-list b {
  display: block;
  font-weight: 600;
}

/* ================= TABLE ================= */
.academic-table {
  width: 100%;
  border-collapse: collapse;
  text-align: center;
  font-size: 12px;
}

.academic-table th {
  background: #f1f5f9;
  padding: 8px 6px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  color: #334155;
  border-bottom: 2px solid #cbd5e1;
}

.academic-table td {
  padding: 8px 6px;
  border-top: 1px solid #e5e7eb;
}

.academic-table .total {
  font-weight: 800;
  color: #1e3a8a;
}

/* ================= RESPONSIVE MOBILE VIEWS ================= */

/* Hide mobile views by default, show desktop views */
.yearly-mobile-view,
.language-mobile-view,
.funding-mobile-view {
  display: none;
}

.yearly-desktop-view,
.language-desktop-view,
.funding-desktop-view {
  display: block;
}

/* Mobile Styles */
@media screen and (max-width: 768px) {
  .academic-page {
    padding: 16px;
  }

  .faculty-section {
    padding: 16px;
  }

  .metrics-panel {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }

  .metric-box {
    padding: 10px;
  }

  .metric-box strong {
    font-size: 18px;
  }

  .leader-list {
    grid-template-columns: 1fr;
    gap: 8px;
  }

  /* Hide desktop views on mobile */
  .yearly-desktop-view,
  .language-desktop-view,
  .funding-desktop-view {
    display: none;
  }

  /* Show mobile views */
  .yearly-mobile-view,
  .language-mobile-view,
  .funding-mobile-view {
    display: block;
  }

  /* Yearly Mobile View */
  .yearly-mobile-view {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
  }

  .year-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px;
    text-align: center;
  }

  .year-item.total {
    background: #eef2ff;
    border-color: #c7d2fe;
    grid-column: span 3;
  }

  .year-label {
    font-size: 10px;
    color: #64748b;
    display: block;
    margin-bottom: 4px;
  }

  .year-value {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
  }

  .year-item.total .year-value {
    color: #1e3a8a;
  }

  /* Language Mobile View */
  .language-mobile-view {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
  }

  .language-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px;
    text-align: center;
  }

  .language-label {
    font-size: 10px;
    color: #64748b;
    display: block;
    margin-bottom: 4px;
  }

  .language-value {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
  }

  /* Funding Mobile View */
  .funding-mobile-view {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
  }

  .funding-item {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 8px;
    text-align: center;
  }

  .funding-label {
    font-size: 10px;
    color: #64748b;
    display: block;
    margin-bottom: 4px;
  }

  .funding-value {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
  }
}

/* Small Mobile Styles */
@media screen and (max-width: 480px) {
  .academic-page {
    padding: 12px;
  }

  .faculty-section {
    padding: 12px;
  }

  .metrics-panel {
    grid-template-columns: 1fr;
  }

  .yearly-mobile-view {
    grid-template-columns: repeat(2, 1fr);
  }

  .year-item.total {
    grid-column: span 2;
  }

  .language-mobile-view {
    grid-template-columns: 1fr;
  }
}

/* ================= PRINT / PDF ================= */
@media print {
  @page {
    size: A4 portrait;
    margin: 18mm 16mm 20mm 16mm;
  }

  body {
    background: white !important;
  }

  .academic-page {
    padding: 0;
    background: white;
  }

  .faculty-section {
    page-break-before: always;
    box-shadow: none;
    border: none;
    border-radius: 0;
    padding: 0;
    margin-bottom: 0;
  }

  .faculty-section:first-child {
    page-break-before: auto;
  }

  .metrics-panel,
  .academic-card,
  table,
  tr {
    page-break-inside: avoid;
  }

  thead {
    display: table-header-group;
  }

  .metric-box,
  .academic-card {
    background: transparent !important;
    border-color: #94a3b8 !important;
  }

  .metric-box.accent {
    border: 2px solid #1e3a8a !important;
  }

  * {
    color: #000 !important;
  }

  /* Show desktop views in print */
  .yearly-desktop-view,
  .language-desktop-view,
  .funding-desktop-view {
    display: block !important;
  }

  .yearly-mobile-view,
  .language-mobile-view,
  .funding-mobile-view {
    display: none !important;
  }
}
</style>