<template>
  <div class="container-fluid">
    <div class="card shadow-lg teacher-form">

      <!-- HEADER -->
      <div class="card-header form-header d-flex align-items-center" :class="isEdit ? 'edit' : 'add'">
        <i class="bi bi-person-badge fs-4 me-2"></i>
        <h6 class="mb-0 fw-semibold">
          {{ isEdit ? 'Edit Lecturer Profile' : 'Add New Lecturer' }}
        </h6>
      </div>

      <div class="card-body">
        <form @submit.prevent="submit">

          <!-- BASIC INFO -->
          <div class="section-card">
            <div class="section-title bg-name">
              <i class="bi bi-person"></i> Basic Information
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label">Lecturer Name</label>
                <input v-model="form.lecturername" class="form-control form-control-lg"
                  placeholder="Enter lecturer name" required />
              </div>

              <div class="col-md-6">
                <label class="form-label">Academic Grade</label>
                <select v-model="form.grade" class="form-select" required>
                  <option value="">Select Grade</option>
                  <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- ORGANIZATION -->
          <div class="section-card">
            <div class="section-title bg-org">
              <i class="bi bi-building"></i> Organization
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-4">
                <label class="form-label">University</label>
                <select v-model="form.university_id" class="form-select" required>
                  <option value="">Select University</option>
                  <option v-for="u in universities" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Faculty</label>
                <select v-model="form.faculty_id" class="form-select" required>
                  <option value="">Select Faculty</option>
                  <option v-for="f in faculties" :key="f.id" :value="f.id">{{ f.facultyname }}</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Department</label>
                <select v-model="form.department_id" class="form-select" required>
                  <option value="">Select Department</option>
                  <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.deptname }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- QUALIFICATION -->
          <div class="section-card">
            <div class="section-title bg-edu">
              <i class="bi bi-mortarboard"></i> Education & Qualification
            </div>
            <div class="row g-3 mt-1">
              <div class="col-md-6">
                <label class="form-label">Qualification</label>
                <select v-model="form.qualification" class="form-select" required>
                  <option value="">Select Education</option>
                  <option v-for="q in qualification" :key="q" :value="q">{{ q }}</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label">Specialized Areas</label>
                <input v-model="specializationInput" @keydown.enter.prevent="addSpecialization" class="form-control"
                  placeholder="Type specialization and press Enter" />
                <div class="mt-2 d-flex flex-wrap gap-1">
                  <span v-for="(area, index) in form.specialized_area" :key="index" class="spec-chip">
                    {{ area }}
                    <span class="remove-chip" @click="removeSpecialization(index)">×</span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- ACTIONS -->
          <div class="form-actions text-end mt-4">
            <button type="button" class="btn btn-warning me-2" @click="goBack">Cancel</button>
            <button type="submit" class="btn btn-primary btn-lg px-4">
              {{ isEdit ? 'Update Lecturer' : 'Save Lecturer' }}
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api.js'

const router = useRouter()
const route = useRoute()

const isEdit = computed(() => !!route.params.id)
const specializationInput = ref('')

// Reactive form
const form = reactive({
  lecturername: '',
  university_id: '',
  faculty_id: '',
  department_id: '',
  grade: '',
  qualification: '',
  specialized_area: []
})

// Static lists (you can fetch from API if dynamic)
const universities = ref([])
const faculties = ref([])
const departments = ref([])
const grades = ['Jr. Teaching Assist.','Teaching Assistant', 'Sr. Teaching Assistant', 'Assoc. Prof.', 'Assist. Prof.', 'Professor']
const qualification = ['Bachelor','Master','PhD','Post PhD']

// Fetch select options
const fetchOptions = async () => {
  try {
    const [uRes, fRes, dRes] = await Promise.all([
      api.get('/universities'),
      api.get('/faculties'),
      api.get('/departments')
    ])
    universities.value = uRes.data
    faculties.value = fRes.data
    departments.value = dRes.data
  } catch (err) { console.error(err) }
}

// Fetch lecturer data if editing
const fetchLecturer = async () => {
  if (!isEdit.value) return
  try {
    const res = await api.get(`/lecturers/${route.params.id}`)
    const l = res.data
    form.lecturername = l.lecturername
    form.university_id = l.university_id
    form.faculty_id = l.faculty_id
    form.department_id = l.department_id
    form.grade = l.grade
    form.qualification = l.qualification
    form.specialized_area = l.specialized_area || []
  } catch (err) { console.error(err) }
}

// Specialization handlers
const addSpecialization = () => {
  const value = specializationInput.value.trim()
  if (!value || form.specialized_area.includes(value)) return
  form.specialized_area.push(value)
  specializationInput.value = ''
}

const removeSpecialization = (index) => form.specialized_area.splice(index, 1)

// Submit form
const submit = async () => {
  try {
    if (isEdit.value) {
      await api.put(`/lecturers/${route.params.id}`, form)
      alert('Lecturer updated successfully!')
    } else {
      await api.post('/lecturers', form)
      alert('Lecturer added successfully!')
    }
    router.push('/dashboard/teachers')
  } catch (err) {
    console.error(err)
    alert('Error saving lecturer. Check console.')
  }
}

const goBack = () => router.push('/dashboard/teachers')

onMounted(async () => {
  await fetchOptions()
  await fetchLecturer()
})
</script>

<style scoped>
/* FORM CARD */
.teacher-form {
  border-radius: 14px;
  overflow: hidden;
}

/* HEADER */
.form-header {
  padding: 1rem 1.25rem;
}

.form-header.add {
  background: linear-gradient(135deg, #4dabf7, #74c0fc);
  color: #fff;
}

.form-header.edit {
  background: linear-gradient(135deg, #ffd43b, #ffa94d);
  color: #000;
}

/* SECTIONS */
.section-card {
  border: 1px solid #e9ecef;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: #fff;
}

/* SECTION TITLE */
.section-title {
  display: inline-block;
  padding: 0.35rem 0.9rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
  margin-bottom: 0.75rem;
}

.bg-name {
  background: linear-gradient(135deg, #5f9cff, #74c0fc);
}

.bg-org {
  background: linear-gradient(135deg, #69db7c, #b2f2bb);
}

.bg-edu {
  background: linear-gradient(135deg, #9775fa, #b197fc);
}

/* INPUTS */
.form-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: #334155;
}

.form-control,
.form-select {
  font-size: 0.8rem;
  padding: 0.45rem 0.6rem;
  border-radius: 8px;
}

/* ACTION BAR */
.form-actions {
  border-top: 1px dashed #dee2e6;
  padding-top: 1rem;
}

/* SPECIALIZATION TAGS */
.spec-chip {
  background-color: #eaf2fb;
  color: #1e3a8a;
  font-size: 0.72rem;
  padding: 0.35rem 0.6rem;
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Remove button */
.remove-chip {
  cursor: pointer;
  font-weight: 700;
  font-size: 0.85rem;
  line-height: 1;
  opacity: 0.7;
  user-select: none;
}

.remove-chip:hover {
  opacity: 1;
  color: #dc2626;
}

.section-card {
  background: #fbfdff;
}

/* ===================================================== PREMIUM DARK MODE – BASE ===================================================== */
[data-bs-theme='dark'] .teacher-form {
  background-color: #020617;
  /* deep navy */
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
}

[data-bs-theme='dark'] .card-body {
  background-color: #020617;
}

/* ===================================================== HEADER ===================================================== */
[data-bs-theme='dark'] .form-header.add {
  background: linear-gradient(135deg, #1e3a8a, #2563eb);
  color: #e0e7ff;
}

[data-bs-theme='dark'] .form-header.edit {
  background: linear-gradient(135deg, #a16207, #facc15);
  color: #020617;
}

/* ===================================================== SECTIONS ===================================================== */
[data-bs-theme='dark'] .section-card {
  background: #0b1220;
  border: 1px solid #1e293b;
}

[data-bs-theme='dark'] .section-title {
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.4);
}

/* ===================================================== LABELS ===================================================== */
[data-bs-theme='dark'] .form-label {
  color: #c7d2fe;
}

/* ===================================================== INPUTS & SELECTS ===================================================== */
[data-bs-theme='dark'] .form-control,
[data-bs-theme='dark'] .form-select {
  background-color: #020617;
  color: #e5e7eb;
  border-color: #334155;
}

[data-bs-theme='dark'] .form-control::placeholder {
  color: #94a3b8;
}

/* Focus glow */
[data-bs-theme='dark'] .form-control:focus,
[data-bs-theme='dark'] .form-select:focus {
  border-color: #60a5fa;
  box-shadow: 0 0 0 0.15rem rgba(96, 165, 250, 0.35);
}

/* ===================================================== SPECIALIZATION TAGS ===================================================== */
[data-bs-theme='dark'] .spec-chip {
  background-color: #1e293b;
  color: #c7d2fe;
}

[data-bs-theme='dark'] .remove-chip:hover {
  color: #f87171;
}

/* ===================================================== ACTION BUTTONS ===================================================== */
[data-bs-theme='dark'] .btn-primary {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  border: none;
}

[data-bs-theme='dark'] .btn-warning {
  background: linear-gradient(135deg, #facc15, #fde047);
  color: #020617;
}

[data-bs-theme='dark'] .btn-warning:hover {
  filter: brightness(0.95);
}

/* ===================================================== DIVIDER ===================================================== */
[data-bs-theme='dark'] .form-actions {
  border-top: 1px dashed #334155;
}
</style>
