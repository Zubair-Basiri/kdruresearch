<template>
  <div class="p-6 mt-5" dir="rtl">
    <h2 style="text-align: center; font-weight: 700;">{{ isEdit ? 'د استاد پروفایل اصلاح' : 'نوی استاد پروفایل' }}</h2>
    <form @submit.prevent="save" class="form-container">
      <!-- Section 1: Introduction -->
      <div class="form-section">
        <h3>پیژندنه</h3>
        <div class="row">
          <!-- <div class="col-md-1"><label>نمبر</label><input v-model="form.id" disabled /></div> -->
          <div class="col-md-3"><label>نوم</label><input v-model="form.name" required /></div>
          <div class="col-md-3"><label>د پلار نوم</label><input v-model="form.father_name" /></div>
          <div class="col-md-2"><label>کوډ نمبر</label><input v-model="form.code_no" dir="ltr" /></div>
          <div class="col-md-2"><label>اوسنې حالت</label><input v-model="form.status" placeholder="اوسنې حالت" /></div>
          <div class="col-md-3"><label>پوهنځی</label>
            <select v-model="form.faculty_id">
              <option value="">غوره کړئ</option>
              <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.displayName }}</option>
            </select>
          </div>
          <div class="col-md-3"><label>دیپارټمنټ</label>
            <select v-model="form.department_id">
              <option value="">غوره کړئ</option>
              <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.displayName }}</option>
            </select>
          </div>
          <div class="col-md-3"><label>علمي رتبه</label>
            <select v-model="form.academic_grade">
              <option value="">غوره کړئ</option>
              <option v-for="g in academicGrades" :key="g.value" :value="g.value">{{ g.label }}</option>
            </select>
          </div>
          <div class="col-md-3"><label>تحصیلي درجه</label>
            <select v-model="form.qualification">
              <option value="">غوره کړئ</option>
              <option v-for="q in qualifications" :key="q.value" :value="q.value">{{ q.label }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Section 2: Educational Info -->
      <div class="form-section">
        <h3>تعلیمي معلومات</h3>
        <div class="row">
          <div class="col-md-4"><label>کورس / رشته</label><input v-model="form.course" /></div>
          <div class="col-md-4"><label>داخلي / بهرني</label>
            <select v-model="form.domestic_international">
              <option value="">غوره کړئ</option>
              <option value="domestic">داخلي</option>
              <option value="international">بهرني</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Section 3: Academic Info -->
      <div class="form-section">
        <h3>علمي معلومات</h3>
        <div class="row">
          <div class="col-md-4"><label>د علمي کادر شاملیدو نېټه</label><input type="date" v-model="form.academic_grade_entrence_date" /></div>
          <div class="col-md-4"><label>د ترفیع نېټه (وروستۍ)</label><input type="date" v-model="form.promotion_date" /></div>
        </div>
        <div class="promotion-section">
          <h4>د ترفیع تاریخ</h4>
          <div class="promotion-table-wrapper">
            <table>
              <thead><tr><th>له</th><th>ته</th><th>نيټه</th><th>یادښتونه</th><th></th></tr></thead>
              <tbody>
                <tr v-for="(hist, idx) in form.promotion_histories" :key="idx">
                  <td><input v-model="hist.from_grade" /></td>
                  <td><input v-model="hist.to_grade" /></td>
                  <td><input type="date" v-model="hist.promotion_date" /></td>
                  <td><input v-model="hist.notes" /></td>
                  <td><button type="button" class="btn btn-sm btn-danger" @click="form.promotion_histories.splice(idx,1)">✕</button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-sm btn-primary mt-2" @click="addPromotion">+ ترفیع ورکړئ</button>
        </div>
      </div>

      <div class="actions">
        <RouterLink to="/dashboard/lecturer-profiles" class="btn btn-secondary">لغوه</RouterLink>
        <button type="submit" class="btn btn-primary" :disabled="saving">خوندي کول</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute(); const router = useRouter()
const isEdit = !!route.params.id
const saving = ref(false)

// Translated Academic Grades (Pashto)
const academicGrades = [
  { value: 'Jr. Teaching Assist.', label: 'نامزد پوهنیار' },
  { value: 'Teaching Assistant', label: 'پوهنیار' },
  { value: 'Sr. Teaching Assistant', label: 'د پوهندوی مرستیال' },
  { value: 'Assist. Prof.', label: 'پوهندوی' },
  { value: 'Assoc. Prof.', label: 'پوهنمل' },
  { value: 'Professor', label: 'پوهاند' }
]

// Translated Qualifications (Pashto)
const qualifications = [
  { value: 'Bachelor', label: 'لیسانس' },
  { value: 'Master', label: 'ماستر' },
  { value: 'PhD', label: 'دوکتورا' },
  { value: 'Post PhD', label: 'پوست دوکتورا' }
]

// --- Translation mapping for Faculties (English → Pashto) ---
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

// Helper function to translate a name using a mapping
function translateName(name, map) {
  if (!name) return ''
  const translated = map[name]
  return translated || name // fallback to original if not found
}

const faculties = ref([])
const departments = ref([])

const form = ref({
  id: null,
  name: '',
  father_name: '',
  code_no: '',
  status: '',
  faculty_id: '',
  department_id: '',
  academic_grade: '',
  qualification: '',
  course: '',
  domestic_international: '',
  academic_grade_entrence_date: '',
  promotion_date: '',
  promotion_histories: []
})

const addPromotion = () => {
  form.value.promotion_histories.push({ from_grade: '', to_grade: '', promotion_date: '', notes: '' })
}

const loadOptions = async () => {
  try {
    const [fRes, dRes] = await Promise.all([
      api.get('/faculties'),
      api.get('/departments')
    ])
    // Transform faculties: add displayName
    faculties.value = fRes.data.map(f => ({
      ...f,
      displayName: translateName(f.facultyname, facultyTranslationMap)
    }))
    // Transform departments
    departments.value = dRes.data.map(d => ({
      ...d,
      displayName: translateName(d.deptname, departmentTranslationMap)
    }))
  } catch (e) { console.error(e) }
}

const save = async () => {
  saving.value = true
  try {
    if (isEdit) await api.put(`/lecturer-profiles/${route.params.id}`, form.value)
    else await api.post('/lecturer-profiles', form.value)
    router.push('/dashboard/lecturer-profiles')
  } catch (e) {
    alert('Error saving')
    console.error(e)
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  await loadOptions()
  if (isEdit) {
    const res = await api.get(`/lecturer-profiles/${route.params.id}`)
    form.value = res.data
    if (!form.value.promotion_histories) form.value.promotion_histories = []
  }
})
</script>

<style scoped>
.form-container { max-width: 1100px;margin: 0 auto; }
.form-section {
  background: #fff; padding: 20px;
  border-radius: 12px; border: 1px solid #e2e8f0;
  margin-bottom: 20px;
}
.form-section h3 {
  font-size: 16px; color: #1F4E79;
  border-bottom: 2px solid #1F4E79;
  padding-bottom: 6px; margin-bottom: 16px;
  font-weight: 700;
}
.row {
  display: flex; flex-wrap: wrap; gap: 12px;
}
.col-md-1 { flex: 0 0 8%; }
.col-md-2 { flex: 0 0 16%; }
.col-md-3 { flex: 0 0 23%; }
.col-md-4 { flex: 0 0 31%; }
label { display: block; font-size: 14px; font-weight: 600; color: #060606; }
input, select { width: 100%; padding: 6px 8px; border: 1px solid #d4c9bc; border-radius: 6px; }
.promotion-section { margin-top: 16px; }
.promotion-table-wrapper { overflow-x: auto; }
.promotion-table-wrapper table { width: 100%; border-collapse: collapse; font-size: 13px; }
.promotion-table-wrapper th, .promotion-table-wrapper td { border: 1px solid #e2e8f0; padding: 6px; }
.actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px; }
</style>