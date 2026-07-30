<template>
  <div class="p-6 mt-5" dir="rtl">
    <!-- Header with title on right, button on left -->
    <div class="d-flex justify-content-between align-items-center" style="flex-direction: row-reverse;">
      <RouterLink to="/dashboard/lecturer-profiles/add" class="btn btn-primary">+ نوی استاد</RouterLink>
      <h2 class="page-title">د استادانو پروفایلونه</h2>
    </div>

    <!-- Filters -->
    <div class="filter-panel">
      <div class="filter-grid">
        <!-- Live search input: triggers on input with debounce -->
        <input v-model="filters.name" placeholder="د نوم، تحصیلي درجه یا کوډ نمبر له مخې لټون..." />
        <select v-model="filters.academic_grade">
          <option value="">ټولې علمي رتبې</option>
          <option v-for="g in academicGrades" :key="g.value" :value="g.value">{{ g.label }}</option>
        </select>
        <select v-model="filters.faculty_id">
          <option value="">ټول پوهنځي</option>
          <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.displayName }}</option>
        </select>
        <select v-model="filters.department_id">
          <option value="">ټول دیپارټمنټونه</option>
          <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.displayName }}</option>
        </select>
        <button @click="applyFilters" class="btn btn-primary btn-sm">فلتر</button>
        <button @click="exportPdf" class="btn btn-outline btn-sm">PDF صادرول</button>
      </div>
    </div>

    <!-- Cards -->
    <div v-if="!loading" class="card-grid">
      <div v-for="(prof, index) in profiles" :key="prof.id" class="profile-card">
        <div class="card-header">
          <span class="badge" :class="prof.status === 'active' ? 'bg-success' : 'bg-danger'">{{ translateStatus(prof.status) }}</span>
          <div class="actions">
            <RouterLink :to="`/dashboard/lecturer-profiles/edit/${prof.id}`" class="btn btn-sm btn-warning me-1">اصلاح</RouterLink>
            <button @click="deleteProfile(prof.id)" class="btn btn-sm btn-danger">ړنګول</button>
          </div>
        </div>
        <div class="card-body">
          <div class="section">
            <h4>پیژندنه</h4>
            <p><strong>نمبر:</strong> {{ toPashtoNumbers((page - 1) * perPage + index + 1) }}</p>
            <p><strong>نوم:</strong> {{ prof.name }}</p>
            <p><strong>د پلار نوم:</strong> {{ prof.father_name || '-' }}</p>
            <p><strong>کوډ نمبر:</strong><span dir="ltr"> {{ prof.code_no || '-' }}</span></p>
            <p><strong>دیپارټمنټ:</strong> {{ getDepartmentDisplay(prof.department_id) }}</p>
            <p><strong>پوهنځی:</strong> {{ getFacultyDisplay(prof.faculty_id) }}</p>
          </div>
          <div class="section">
            <h4>تعلیمي معلومات</h4>
            <p><strong>کورس / رشته:</strong> {{ prof.course || '-' }}</p>
            <p><strong>تحصیلي درجه:</strong> {{ getQualificationDisplay(prof.qualification) }}</p>
            <p><strong>داخلي / بهرني:</strong> {{ prof.domestic_international === 'domestic' ? 'داخلي' : prof.domestic_international === 'international' ? 'بهرني' : '-' }}</p>
          </div>
          <div class="section">
            <h4>علمي معلومات</h4>
            <p><strong>علمي رتبه:</strong> {{ getAcademicGradeDisplay(prof.academic_grade) }}</p>
            <p><strong>د شاملیدو نېټه:</strong> {{ toPashtoDate(prof.academic_grade_entrence_date) }}</p>
            <p><strong>د ترفیع نېټه (وروستۍ):</strong> {{ toPashtoDate(prof.promotion_date) }}</p>
          </div>
          <div class="section">
            <div v-if="prof.promotion_histories && prof.promotion_histories.length">
              <h4>د ترفیع تاریخ</h4>
              <table class="mini-table">
                <thead><tr><th>له</th><th>ته</th><th>نېټه</th></tr></thead>
                <tbody>
                  <tr v-for="h in prof.promotion_histories" :key="h.id">
                    <td>{{ getAcademicGradeDisplay(h.from_grade) }}</td>
                    <td>{{ getAcademicGradeDisplay(h.to_grade) }}</td>
                    <td>{{ toPashtoDate(h.promotion_date) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="text-center py-5">Loading...</div>
    <!-- Pagination -->
    <div v-if="totalPages > 1" class="pagination-wrapper">
      <button @click="prevPage" :disabled="page === 1" class="btn btn-sm btn-secondary">پخوانۍ</button>
      <span class="page-info">مخ {{ toPashtoNumbers(page) }} له {{ toPashtoNumbers(totalPages) }} څخه</span>
      <button @click="nextPage" :disabled="page === totalPages" class="btn btn-sm btn-secondary">راتلونکې</button>
    </div>

    <!-- Summary Boxes -->
    <div v-if="!loading && profiles.length" class="summary-boxes">
      <!-- Education breakdown -->
      <div v-if="educationBreakdown.length" class="summary-section">
        <h4 class="summary-title">د تحصیلي درجې له مخې تفکیک</h4>
        <div class="summary-grid">
          <div v-for="item in educationBreakdown" :key="item.label" class="summary-card">
            <span class="summary-label">{{ item.label }}</span>
            <span class="summary-value">{{ toPashtoNumbers(item.count) }}</span>
            <span class="summary-total">/ {{ toPashtoNumbers(item.total) }} پروفایلونه</span>
          </div>
        </div>
      </div>

      <!-- Academic grade breakdown -->
      <div v-if="academicGradeBreakdown.length" class="summary-section">
        <h4 class="summary-title">د علمي رتبې له مخې تفکیک</h4>
        <div class="summary-grid">
          <div v-for="item in academicGradeBreakdown" :key="item.label" class="summary-card">
            <span class="summary-label">{{ item.label }}</span>
            <span class="summary-value">{{ toPashtoNumbers(item.count) }}</span>
            <span class="summary-total">/ {{ toPashtoNumbers(item.total) }} پروفایلونه</span>
          </div>
        </div>
      </div>

      <!-- Domestic / International breakdown -->
      <div v-if="domesticInternationalBreakdown.length" class="summary-section">
        <h4 class="summary-title">د داخلي / بهرني له مخې تفکیک</h4>
        <div class="summary-grid">
          <div v-for="item in domesticInternationalBreakdown" :key="item.label" class="summary-card">
            <span class="summary-label">{{ item.label }}</span>
            <span class="summary-value">{{ toPashtoNumbers(item.count) }}</span>
            <span class="summary-total">/ {{ toPashtoNumbers(item.total) }} پروفایلونه</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import api from '@/services/api'

// --- Helpers ---

// Convert Western numbers to Pashto
function toPashtoNumbers(value) {
  if (value === undefined || value === null || value === '') return ''
  const str = String(value)
  const western = ['0','1','2','3','4','5','6','7','8','9']
  const pashto  = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹']
  return str.replace(/[0-9]/g, (d) => pashto[western.indexOf(d)])
}

// Convert date string to Pashto digits
function toPashtoDate(dateStr) {
  if (!dateStr) return '-'
  return toPashtoNumbers(dateStr)
}

// Academic Grades (value: English, label: Pashto)
const academicGrades = [
  { value: 'Jr. Teaching Assist.', label: 'نامزد پوهنیار' },
  { value: 'Teaching Assistant', label: 'پوهنیار' },
  { value: 'Sr. Teaching Assistant', label: 'د پوهندوی مرستیال' },
  { value: 'Assist. Prof.', label: 'پوهندوی' },
  { value: 'Assoc. Prof.', label: 'پوهنمل' },
  { value: 'Professor', label: 'پوهاند' }
]

// Qualifications
const qualifications = [
  { value: 'Bachelor', label: 'لیسانس' },
  { value: 'Master', label: 'ماستر' },
  { value: 'PhD', label: 'دوکتورا' },
  { value: 'Post PhD', label: 'پوست دوکتورا' }
]

const facultyTranslationMap = {
  'Computer Science': 'کمپیوټر ساینس',
  'Economics': 'اقتصاد',
  'Education': 'ښووونه او روزنه',
  'Engineering': 'انجینري',
  'Journalism': 'ژورنالیزم',
  'Law & Political Science': 'حقوق او سیاسي علوم',
  'Languages & Literature': 'ژبه او ادبیات',
  'Medicine': 'طب',
  'Pharmacy': 'پارمسي',
  'Public Administration & Policy': 'عامه اداره او پالیسې',
  'Shariah': 'شرعیات',
  'Stomatology': 'ستوماتولوژي'
}

// --- Translation mapping for Departments (English → Pashto) ---
const departmentTranslationMap = {
  "Network": "نیټورک",
  "Database": "ډېټابيس",
  "Software": "سافټویر",
  "Water & Environmental Science": "د اوبو او چاپيریال علوم",
  "Energy": "انرژي",
  "Civil": "سیول",
  "Architecture": "مهندسې",
  "English": "انګلیسي",
  "Sport": "سپورت",
  "Biology": "بیولوژي",
  "Pashto": "پښتو",
  "Pyschology & Pedogogy": "ارواپوهنه او پیداګوژی",
  "History": "تاریخ",
  "Dari": "دری",
  "Math": "ریاضي",
  "Social Science": "ټولنیز علوم",
  "Physics": "فزیک",
  "Chemistry": "کیمیا",
  "Computer Learning": "کمپیوټر زده‌کړه",
  "Geography": "جغرافیه",
  "Islamic Knowledge & Culture": "اسلامي پوهه او فرهنګ",
  "Islamic Teachings": "اسلامي تعلیمات",
  "Fiqh and Qaanon": "فقه او قانون",
  "Islamic Saqafat": "اسلامي ثقافت",
  "Aqidah and Philosophy": "عقیده او فلسفه",
  "BBA": "اداره او منیجمنټ",
  "Econometry": "اقتصاد پوهنه",
  "Entrepreneurship": "تشبث",
  "National Economics": "ملي اقتصاد",
  "Banking & Finance": "بانکداري او ماليې",
  "Radio and TV": "راډیو او تلویزیون",
  "Media": "رسنۍ",
  "Para Clinic": "پیرا کلینیک",
  "Surgery": "جراحي",
  "Pediatrics": "اطفال",
  "Internal Medicine": "داخلي طب",
  "Forensic": "عدلي طب",
  "ENT": "غوږ، پوزه او ستوني",
  "Eye": "سترګه",
  "Dermatology": "د پوستکي درملنه (جلدي طب)",
  "Neuro pyschiatry": "دماغي او رواني درملنه (نیورو سایکاټري)",
  "Radiology": "رادیولوژي",
  "Orthopedic": "د هډوکو درملنه (ارتوپيډي)",
  "Gyncialogy": "د ښځو درملنه (نسايي طب)",
  "Public Administration": "عامه اداره",
  "Public Policy": "عامه پالیسي",
  "Development Management": "پراختیايي مدیریت",
  "Attorney and Justice": "قانون او عدالت",
  "Management and Diplomacy": "مدیریت او ډیپلوماسۍ",
  "Arabic": "عربي",
  "General Pharmacy": "عمومي فارمسي",
  "General Stomatology": "سټوماتولوژي",
  "Political Science and IR": "سیاسي علوم او نړیوالې اړیکې",
  "Journalism": "ژورنالیزم",
  "Public Health": "عامه روغتیا",
  "English Language & Literature": "د انګلیسي ژبه او ادبیات",
  "Pashto Language & Literature": "د پښتو ژبه او ادبیات",
  "Public Relations": "عامه اړیکې",
  "Islamic Studies": "اسلامي تعلیمات",
  "Water & Envirnmental Science": "د اوبو او چاپيریال علوم",
  "Architechure": "مهندسي",
  "Physical Education": "فزیکي زده کړې"
}

// Helper to translate a name using a map
function translateName(name, map) {
  if (!name) return ''
  return map[name] || name
}

// State
const profiles = ref([])
const loading = ref(false)
const faculties = ref([])
const departments = ref([])
const totalPages = ref(1)
const page = ref(1)
const perPage = 9

const filters = ref({ name: '', academic_grade: '', faculty_id: '', department_id: '' })

let searchTimeout = null

watch(() => filters.value.name, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    page.value = 1  // reset to first page on new search
    fetchData()
  }, 400)
})

// Display helpers
function getAcademicGradeDisplay(value) {
  if (!value) return '-'
  const found = academicGrades.find(g => g.value === value)
  return found ? found.label : value
}
function getQualificationDisplay(value) {
  if (!value) return '-'
  const found = qualifications.find(q => q.value === value)
  return found ? found.label : value
}
function getFacultyDisplay(id) {
  if (!id) return '-'
  const found = faculties.value.find(f => f.id === id)
  return found ? found.displayName : id
}
function getDepartmentDisplay(id) {
  if (!id) return '-'
  const found = departments.value.find(d => d.id === id)
  return found ? found.displayName : id
}
function translateStatus(status) {
  if (status === 'active') return 'فعال'
  if (status === 'inactive') return 'غیرفعال'
  return status || '-'
}

// Data fetch with backend pagination
const fetchFaculties = async () => {
    const res = await api.get('/faculties')

    faculties.value = res.data.map(f => ({
        ...f,
        displayName: translateName(f.facultyname, facultyTranslationMap)
    }))
}

const fetchDepartments = async () => {
    const res = await api.get('/departments')

    departments.value = res.data.map(d => ({
        ...d,
        displayName: translateName(d.deptname, departmentTranslationMap)
    }))
}

const fetchData = async () => {
    loading.value = true

    try {

        const params = {
            ...filters.value,
            page: page.value,
            per_page: perPage
        }

        const res = await api.get('/lecturer-profiles', { params })

        profiles.value = res.data.data
        totalPages.value = res.data.last_page

    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    await Promise.all([
        fetchFaculties(),
        fetchDepartments()
    ])

    fetchData()
})

const applyFilters = () => { page.value = 1; fetchData() }

// Education breakdown (by qualification)
const educationBreakdown = computed(() => {
  if (!profiles.value.length) return []
  const counts = {}
  profiles.value.forEach(p => {
    const q = p.qualification
    if (q) {
      const label = qualifications.find(qq => qq.value === q)?.label || q
      counts[label] = (counts[label] || 0) + 1
    }
  })
  return Object.entries(counts).map(([label, count]) => ({
    label,
    count,
    total: profiles.value.length
  }))
})

// Academic grade breakdown
const academicGradeBreakdown = computed(() => {
  if (!profiles.value.length) return []
  const counts = {}
  profiles.value.forEach(p => {
    const g = p.academic_grade
    if (g) {
      const label = academicGrades.find(ag => ag.value === g)?.label || g
      counts[label] = (counts[label] || 0) + 1
    }
  })
  return Object.entries(counts).map(([label, count]) => ({
    label,
    count,
    total: profiles.value.length
  }))
})

// Domestic / International breakdown
const domesticInternationalBreakdown = computed(() => {
  if (!profiles.value.length) return []
  const counts = {}
  profiles.value.forEach(p => {
    const di = p.domestic_international
    if (di) {
      const label = di === 'domestic' ? 'داخلي' : 'بهرني'
      counts[label] = (counts[label] || 0) + 1
    }
  })
  return Object.entries(counts).map(([label, count]) => ({
    label,
    count,
    total: profiles.value.length
  }))
})

const exportPdf = () => {
    const params = new URLSearchParams(filters.value).toString();
    const url = `${api.defaults.baseURL}/api/lecturer-profiles/export-pdf?${params}`;
    window.open(url, '_blank');
};
const deleteProfile = async (id) => {
  if (!confirm('آیا تاسو ډاډه یاست؟')) return
  await api.delete(`/lecturer-profiles/${id}`)
  fetchData()
}

const prevPage = () => { if (page.value > 1) { page.value--; fetchData() } }
const nextPage = () => { if (page.value < totalPages.value) { page.value++; fetchData() } }

onMounted(fetchData)
</script>

<style scoped>
/* existing styles */
.card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-top: 20px; }
.profile-card { background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
.card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.section { margin-bottom: 14px; color: black;}
.section h4 { font-size: 16px; font-weight: 700; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px; color: #1F4E79; }
.section p { margin: 2px 0; font-size: 14px; text-align: right; }
.section p strong { margin-left: 4px; }
.mini-table { width: 100%; font-size: 13px; border-collapse: collapse; }
.mini-table th, .mini-table td { border: 1px solid #e2e8f0; padding: 3px 6px; text-align: right; }
.mini-table th { background: #f1f5f9; }
.filter-panel { margin: 15px 0; }
.filter-grid { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.filter-grid input, .filter-grid select { padding: 6px 10px; border: 1px solid #d4c9bc; border-radius: 6px; }
.btn { padding: 6px 14px; border-radius: 6px; }
.btn-primary { background: #1F4E79; color: white; border: none; }
.btn-outline { background: transparent; border: 1px solid #1F4E79; color: #1F4E79; }
.btn-sm { font-size: 12px; padding: 4px 10px; }
.pagination-wrapper { display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 20px; }
.page-info { font-size: 14px; }
.summary-boxes {
  margin-top: 20px;
  padding: 16px;
  background: #ffffff;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}
.summary-section {
  margin-bottom: 20px;
}
.summary-section:last-child {
  margin-bottom: 0;
}
.summary-title {
  font-size: 20px;
  font-weight: 700;
  color: #1F4E79;
  margin: 0 0 10px 0;
  border-left: 3px solid #1F4E79;
  padding-left: 10px;
}
.summary-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 24px;
}
.summary-card {
  background: #e2ebf5;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 8px 14px;
  min-width: 150px;
  text-align: center;
  flex: 0 0 auto;
}
.summary-label {
  font-size: 15px;
  text-transform: uppercase;
  color: #4a5568;
  display: block;
  font-weight: 600;
}
.summary-value {
  font-size: 24px;
  font-weight: 700;
  color: #1F4E79;
  display: block;
}
.summary-total {
  font-size: 14;
  color: #718096;
}
</style>