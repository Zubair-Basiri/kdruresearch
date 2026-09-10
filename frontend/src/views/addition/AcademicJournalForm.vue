<template>
  <div class="container-fluid p-4 bg-light">
    <div class="row justify-content-center">
      <div class="col-xl-10 col-lg-11">

        <div class="card shadow-lg journal-form-card">

          <!-- HEADER -->
          <div class="card-header form-header d-flex align-items-center">
            <div class="icon-box me-3">
              <i class="bi bi-journal-text"></i>
            </div>
            <div>
              <h5 class="mb-0 text-white">
                {{ isSubmission ? (isEdit ? 'Edit Submission' : 'New Submission') : (isEdit ? 'Edit Published Paper' : 'New Journal') }}
              </h5>
              <small class="text-white-50">
                {{ isSubmission ? 'Submit for admin approval' : 'Manage published research' }}
              </small>
            </div>
          </div>

          <!-- BODY -->
          <div class="card-body p-4">
            <div v-if="pageLoading" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="mt-3 text-muted">Loading form data...</p>
            </div>

            <form v-else @submit.prevent="save">

              <!-- BASIC INFO -->
              <h6 class="section-title">Basic Information</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-6">
                  <label class="form-label form-title">Title<span class="text-danger">*</span></label>
                  <input v-model="form.title" class="form-control form-field" required />
                  <div v-if="errors.title" class="text-danger small mt-1">{{ errors.title[0] }}</div>
                </div>

                <div class="col-md-6">
                  <label class="form-label form-title">Lecturer<span class="text-danger">*</span></label>
                  <div v-if="lecturersLoading" class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                    <span class="text-muted">Loading lecturers...</span>
                  </div>
                  <select 
                    v-else 
                    v-model="form.lecturer_id" 
                    class="form-select form-field" 
                    :disabled="isLecturerDisabled"
                    required
                  >
                    <option value="">-- Select Lecturer --</option>
                    <option v-for="lecturer in lecturers" :key="lecturer.id" :value="lecturer.id">
                      {{ lecturer.lecturername }}
                      <span v-if="lecturer.faculty">({{ lecturer.faculty.facultyname }})</span>
                      <span v-else>(No Faculty)</span>
                    </option>
                  </select>
                  <div v-if="isLecturerDisabled && form.lecturer_id" class="text-muted small mt-1">
                    This submission is linked to your account. Contact admin to change.
                  </div>
                  <div v-if="errors.lecturer_id" class="text-danger small mt-1">{{ errors.lecturer_id[0] }}</div>
                  <div v-if="!lecturersLoading && lecturers.length === 0" class="alert alert-warning mt-2 py-2">
                    No lecturers found. <RouterLink to="/lecturers/form" class="alert-link">Add a lecturer</RouterLink> first.
                  </div>
                </div>
              </div>

              <!-- PUBLICATION INFO -->
              <h6 class="section-title">Publication Details</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-3">
                  <label class="form-label form-title">Year<span class="text-danger">*</span></label>
                  <input type="number" v-model="form.year" class="form-control form-field" min="1900" :max="new Date().getFullYear()" />
                  <div v-if="errors.year" class="text-danger small mt-1">{{ errors.year[0] }}</div>
                </div>
                <div class="col-md-3">
                  <label class="form-label form-title">Publication Type<span class="text-danger">*</span></label>
                  <select v-model="form.publication" class="form-select form-field">
                    <option value="">Select Type</option>
                    <option value="Peer-Reviewed Journal Article">Peer-Reviewed Journal Article</option>
                    <option value="Review Article">Review Article</option>
                    <option value="Case Study">Case Study</option>
                    <option value="Short Communication / Technical Note">Short Communication / Technical Note</option>
                    <option value="Editorial / Commentary">Editorial / Commentary</option>
                    <option value="Conference Paper (Proceedings)">Conference Paper (Proceedings)</option>
                    <option value="Conference Presentation (Abstract/Paper)">Conference Presentation (Abstract/Paper)</option>
                    <option value="Workshop Paper">Workshop Paper</option>
                    <option value="Book (Authored)">Book (Authored)</option>
                    <option value="Book (Edited Volume)">Book (Edited Volume)</option>
                    <option value="Book Chapter">Book Chapter</option>
                    <option value="Research Report">Research Report</option>
                    <option value="Policy Brief">Policy Brief</option>
                    <option value="Working Paper / Discussion Paper">Working Paper / Discussion Paper</option>
                    <option value="Technical Report">Technical Report</option>
                    <option value="PhD Thesis">PhD Thesis</option>
                    <option value="Master's Dissertation">Master's Dissertation</option>
                    <option value="Undergraduate Monograph">Undergraduate Monograph</option>
                    <option value="Patent">Patent</option>
                    <option value="Software / System Development">Software / System Development</option>
                    <option value="Product / Prototype Development">Product / Prototype Development</option>
                    <option value="Encyclopedia Entry">Encyclopedia Entry</option>
                    <option value="Translation Work">Translation Work</option>
                    <option value="Teaching Material / Module Development">Teaching Material / Module Development</option>
                    <option value="Creative Work (Art, Design, Architecture, Performance)">Creative Work (Art, Design, Architecture, Performance)</option>
                    <option value="Book (Authored) Academic">Book (Authored) Academic</option>
                    <option value="Book (Authored) Non-academic">Book (Authored) Non-academic</option>
                    <!-- Add other options as needed -->
                  </select>
                  <div v-if="errors.publication" class="text-danger small mt-1">{{ errors.publication[0] }}</div>
                </div>
                <div class="col-md-3">
                  <label class="form-label form-title">Indexed</label>
                  <select v-model="form.indexed" class="form-select form-field">
                    <option value="">Select Index</option>
                    <option value="PubMed/MEDLINE">PubMed/MEDLINE</option>
                    <option value="Non-Indexed National (Conference Proceedings)">Non-Indexed National (Conference Proceedings)</option>
                    <option value="ORI">ORI</option>
                    <option value="Non-Indexed (peer reviewed)">Non-Indexed (peer reviewed)</option>
                    <option value="Q1">Q1</option>
                    <option value="Q2">Q2</option>
                    <option value="Q3">Q3</option>
                    <option value="Q4">Q4</option>
                    <option value="N/A">N/A</option>
                    <option value="Non-indexed (Conference Proceedings)">Non-indexed (Conference Proceedings)</option>
                    <option value="Non-indexed (peer reviewed National)">Non-indexed (peer reviewed National)</option>
                    <option value="Indexed (Conference Proceedings)">Indexed (Conference Proceedings)</option>
                  </select>
                  <div v-if="errors.indexed" class="text-danger small mt-1">{{ errors.indexed[0] }}</div>
                </div>
                <div class="col-md-3">
                  <label class="form-label form-title">Citations</label>
                  <input type="number" v-model="form.citation" class="form-control form-field" min="0" />
                  <div v-if="errors.citation" class="text-danger small mt-1">{{ errors.citation[0] }}</div>
                </div>
              </div>

              <!-- EXTRA DETAILS -->
              <h6 class="section-title">Additional Details</h6>
              <div class="row g-3 mb-4">
                <div class="col-md-4">
                  <label class="form-label form-title">Funding Source</label>
                  <select v-model="form.funding" class="form-select form-field">
                    <option value="">Select</option>
                    <option value="Self-Funded">Self-Funded</option>
                    <option value="University (Internal)">University (Internal)</option>
                    <option value="International Donor">International Donor</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label form-title">Collaboration</label>
                  <select v-model="form.collaboration" class="form-select form-field">
                    <option value="">Select</option>
                    <option value="National">National</option>
                    <option value="International">International</option>
                  </select>
                </div>
                <div class="col-md-4" v-if="form.collaboration === 'International'">
                    <label class="form-label form-title">International Paper Link</label>
                    <input v-model="form.paper_link" class="form-control form-field" placeholder="https://doi.org/..." />
                    <small class="text-muted">Provide a link to the international paper.</small>
                    <div v-if="errors.paper_link" class="text-danger small mt-1">{{ errors.paper_link[0] }}</div>
                </div>
                <div class="col-md-4">
                  <label class="form-label form-title">Language</label>
                  <select v-model="form.language" class="form-select form-field">
                    <option value="">Select Language</option>
                    <option value="Pashto">Pashto</option>
                    <option value="Dari">Dari</option>
                    <option value="English">English</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label form-title">Status</label>
                  <select v-model="form.status" class="form-select form-field">
                    <option value="">Select Status</option>
                    <option value="Published">Published</option>
                    <option value="Proposal Stage">Proposal Stage</option>
                    <option value="Ongoing Research">Ongoing Research</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label form-title">Author Position</label>
                  <select v-model="form.author_position" class="form-select form-field">
                    <option value="">Select Position</option>
                    <option value="1">1st</option>
                    <option value="2">2nd</option>
                    <option value="3">3rd</option>
                    <option value="4">4th</option>
                    <option value="5">5th</option>
                    <option value="6">6th</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>

              <!-- ACTIONS -->
              <div class="d-flex justify-content-end border-top pt-3">
                <RouterLink to="/dashboard/academicJournals" class="btn btn-outline-secondary me-2">Cancel</RouterLink>
                <button type="submit" class="btn btn-save" :disabled="saving || lecturers.length === 0">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-2" role="status"></span>
                  {{ isEdit ? 'Update' : 'Save' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'

const route = useRoute()
const router = useRouter()

// Mode detection
const isSubmission = ref(route.query.submission === 'true')
const isEdit = computed(() => !!route.params.id)

// IDs for editing
const submissionId = computed(() => isSubmission.value && isEdit.value ? route.params.id : null)
const publishedId = computed(() => !isSubmission.value && isEdit.value ? route.params.id : null)

// State
const lecturers = ref([])
const lecturersLoading = ref(true)
const pageLoading = ref(true)
const saving = ref(false)
const errors = ref({})

// Current user info
const currentUser = ref(null)
const isAdmin = computed(() => currentUser.value && ['admin', 'super_admin'].includes(currentUser.value.role))

// Form data
const form = reactive({
  title: '',
  lecturer_id: '',
  year: '',
  publication: '',
  indexed: '',
  citation: '',
  funding: '',
  collaboration: '',
  paper_link: '',
  language: '',
  status: '',
  author_position: ''
})

// Fetch current user
const fetchCurrentUser = async () => {
  try {
    const res = await api.get('/user')
    currentUser.value = res.data
  } catch (error) {
    console.error('Failed to fetch user', error)
  }
}

// Fetch lecturers
const fetchLecturersForDropdown = async () => {
  lecturersLoading.value = true
  try {
    const response = await api.get('/lecturers-for-dropdown')
    if (response.data.success && response.data.data) {
      lecturers.value = response.data.data
    } else if (Array.isArray(response.data)) {
      lecturers.value = response.data
    } else if (response.data.data && Array.isArray(response.data.data)) {
      lecturers.value = response.data.data
    } else {
      lecturers.value = []
    }
  } catch (error) {
    console.error('Error fetching lecturers:', error)
    lecturers.value = []
  } finally {
    lecturersLoading.value = false
  }
}

// Fetch data for editing
const fetchData = async () => {
  if (!isEdit.value) {
    // For new submission: if user is lecturer, auto-select their lecturer
    if (isSubmission.value && currentUser.value && currentUser.value.role === 'user') {
      const userLecturerId = currentUser.value.lecturer_id
      if (userLecturerId) {
        form.lecturer_id = userLecturerId
      } else {
        console.warn('Lecturer not linked to this user account')
      }
    }
    pageLoading.value = false
    return
  }

  try {
    let response
    if (isSubmission.value) {
      response = await api.get(`/submitted-papers/${submissionId.value}`)
    } else {
      response = await api.get(`/academic-papers/${publishedId.value}`)
    }
    const data = response.data
    Object.keys(form).forEach(key => {
      if (data[key] !== undefined) form[key] = data[key]
    })
  } catch (error) {
    console.error('Error fetching data:', error)
    alert('Failed to load data. Please try again.')
    router.push('/dashboard/academicJournals')
  } finally {
    pageLoading.value = false
  }
}

// Save
const save = async () => {
  if (!form.lecturer_id) {
    errors.value = { lecturer_id: ['Please select a lecturer'] }
    return
  }

  saving.value = true
  errors.value = {}

  try {
    if (isSubmission.value) {
      if (submissionId.value) {
        await api.put(`/submitted-papers/${submissionId.value}`, form)
        alert('Submission updated successfully')
      } else {
        await api.post('/submitted-papers', form)
        alert('Submission created. Awaiting admin approval.')
      }
    } else {
      if (publishedId.value) {
        await api.put(`/academic-papers/${publishedId.value}`, form)
        alert('Paper updated successfully')
      } else {
        // Lecturer trying to create paper directly – fallback to submission
        await api.post('/submitted-papers', form)
        alert('Submission created. Awaiting admin approval.')
      }
    }
    router.push('/dashboard/academicJournals')
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors
    } else if (error.response?.status === 403) {
      alert('Not authorized')
    } else {
      console.error(error)
      alert('Save failed. Please try again.')
    }
  } finally {
    saving.value = false
  }
}

// Determine if the lecturer dropdown should be disabled
const isLecturerDisabled = computed(() => {
  // For new submission by a lecturer, disable it
  if (isSubmission.value && !isEdit.value && currentUser.value && currentUser.value.role === 'user') {
    return true
  }
  // For editing a submission, if the user is not admin, disable (only admin can change lecturer)
  if (isEdit.value && isSubmission.value && !isAdmin.value) {
    return true
  }
  return false
})

onMounted(async () => {
  await fetchCurrentUser()
  await fetchLecturersForDropdown()
  await fetchData()
})
</script>

<style scoped>
/* Your existing styles remain unchanged */
.journal-form-card { border-radius: 14px; overflow: hidden; }
.form-header { background: linear-gradient(135deg, #5f9cff, #74c0fc); padding: 1.2rem; }
.icon-box { width: 46px; height: 46px; background: rgba(255,255,255,0.15); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.4rem; }
.section-title { font-size: 0.9rem; font-weight: 600; border-left: 4px solid #4dabf7; padding-left: 8px; margin-bottom: 30px; margin-top: 40px; }
.btn-save { background: linear-gradient(135deg, #4dabf7, #74c0fc); color: #fff; border: none; }
.btn-save:hover:not(:disabled) { background: linear-gradient(135deg, #339af0, #4dabf7); }
.form-title { color: rgb(7, 7, 120) !important; }
.form-field { color: #000000 !important; }
[data-bs-theme='dark'] .form-control, [data-bs-theme='dark'] .form-select { background-color: #212529; color: #fff; }
</style>