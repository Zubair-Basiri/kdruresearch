<template>
  <div class="container-fluid p-3 bg-light">
    <div class="card shadow-sm">

      <!-- HEADER -->
      <div class="card-header table-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0 text-white fw-semibold">Academic Journals & Submissions</h6>
        <RouterLink to="/dashboard/academicJournals/form?submission=true" class="btn btn-add btn-sm">
          + New Submission
        </RouterLink>
      </div>

      <div class="card-body pt-3">
        <!-- TABS -->
        <ul class="nav nav-tabs mb-3">
          <li class="nav-item">
            <a class="nav-link" :class="{ active: activeTab === 'published' }" @click.prevent="activeTab = 'published'">
              Published Papers
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" :class="{ active: activeTab === 'submissions' }" @click.prevent="activeTab = 'submissions'">
              {{ isAdmin ? 'All Submissions' : 'My Submissions' }}
            </a>
          </li>
        </ul>

        <!-- ======================== PUBLISHED PAPERS TABLE ======================== -->
        <div v-if="activeTab === 'published'">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <input v-model="pubSearch" class="form-control w-25" placeholder="Search published papers..." />
            <div>
              <label class="me-2 fw-semibold">Rows:</label>
              <select v-model="pubPerPage" class="form-select d-inline-block w-auto">
                <option v-for="n in [5,10,15,20]" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead>
                <tr>
                  <th class="th-id">#</th><th class="th-title">Title</th><th class="th-area">Lecturer</th>
                  <th class="th-year">Year</th><th class="th-type">Publication</th><th class="th-index">Indexed</th>
                  <th class="th-cite">Citations</th><th class="th-fund">Funding</th><th class="th-collab">Collaboration</th>
                  <th class="th-lang">Language</th><th class="th-status">Status</th><th class="th-author">Author Pos.</th>
                  <th class="th-action">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(paper, idx) in paginatedPublished" :key="paper.id">
                  <td>{{ (pubCurrentPage-1)*pubPerPage + idx + 1 }}</td>
                  <td class="text-start">{{ paper.title }}</td><td>{{ paper.lecturerName }}</td>
                  <td>{{ paper.year }}</td><td>{{ paper.publication }}</td><td>{{ paper.indexed }}</td>
                  <td>{{ paper.citation }}</td><td>{{ paper.funding }}</td><td>{{ paper.collaboration }}</td>
                  <td>{{ paper.language }}</td><td>{{ paper.status }}</td><td>{{ paper.author_position }}</td>
                  <td class="text-center">
                    <!-- Only show edit/delete for admin -->
                    <template v-if="isAdmin">
                      <RouterLink :to="`/dashboard/academicJournals/form/${paper.id}`" class="btn btn-sm btn-warning me-2">Edit</RouterLink>
                      <button class="btn btn-sm btn-danger" @click="deletePublished(paper.id)">Delete</button>
                    </template>
                    <span v-else class="text-muted">—</span>
                   </td>
                </tr>
                <tr v-if="filteredPublished.length === 0"><td colspan="13" class="text-center">No published papers found</td></tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <span>Page {{ pubCurrentPage }} of {{ pubTotalPages }}</span>
            <div><button class="btn btn-sm btn-secondary me-2" :disabled="pubCurrentPage===1" @click="pubCurrentPage--">Prev</button>
            <button class="btn btn-sm btn-secondary" :disabled="pubCurrentPage===pubTotalPages" @click="pubCurrentPage++">Next</button></div>
          </div>
        </div>

        <!-- ======================== SUBMISSIONS TABLE ======================== -->
        <div v-if="activeTab === 'submissions'">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
              <input v-model="subSearch" class="form-control w-auto" placeholder="Search submissions..." />
              <select v-model="subApprovalFilter" class="form-select w-auto">
                <option value="">All Approval Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            <div>
              <label class="me-2">Rows:</label>
              <select v-model="subPerPage" class="form-select d-inline-block w-auto">
                <option v-for="n in [5,10,15,20]" :key="n" :value="n">{{ n }}</option>
              </select>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
              <thead>
                <tr>
                  <th class="th-sub-id">#</th><th class="th-sub-title">Title</th><th class="th-sub-lecturer">Lecturer</th>
                  <th class="th-sub-year">Year</th><th class="th-sub-publication">Publication</th>
                  <th class="th-sub-approval">Approval</th><th class="th-sub-comment">Admin Comment</th>
                  <th class="th-sub-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(sub, idx) in paginatedSubmissions" :key="sub.id">
                  <td>{{ (subCurrentPage-1)*subPerPage + idx + 1 }}</td>
                  <td class="text-start">{{ sub.title }}</td>
                  <td>{{ sub.lecturer?.lecturername || 'N/A' }}</td>
                  <td>{{ sub.year }}</td>
                  <td>{{ sub.publication }}</td>
                  <td>
                    <span :class="['badge', sub.approval_status==='pending'?'bg-warning': sub.approval_status==='approved'?'bg-success':'bg-danger']">
                      {{ sub.approval_status }}
                    </span>
                  </td>
                  <td class="text-start">{{ sub.admin_comment || '—' }}</td>
                  <td class="text-center">
                    <!-- Lecturer actions: only when pending -->
                    <template v-if="!isAdmin && sub.approval_status === 'pending'">
                      <RouterLink :to="`/dashboard/academicJournals/form/${sub.id}?submission=true`" class="btn btn-sm btn-warning me-2">Edit</RouterLink>
                      <!-- Delete button removed as requested -->
                    </template>
                    <!-- Admin actions: only when pending -->
                    <template v-if="isAdmin && sub.approval_status === 'pending'">
                      <button class="btn btn-sm btn-outline-secondary me-2" @click="openCommentModal(sub)">✏️ Comment</button>
                      <button class="btn btn-sm btn-success me-2" @click="openApproveModal(sub)">Approve</button>
                      <button class="btn btn-sm btn-danger" @click="openRejectModal(sub)">Reject</button>
                    </template>
                    <!-- For approved/rejected, no actions -->
                    <span v-if="isAdmin && sub.approval_status !== 'pending'" class="text-muted">—</span>
                  </td>
                </tr>
                <tr v-if="filteredSubmissions.length === 0"><td colspan="8" class="text-center">No submissions found</td></tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-3">
            <span>Page {{ subCurrentPage }} of {{ subTotalPages }}</span>
            <div><button class="btn btn-sm btn-secondary me-2" :disabled="subCurrentPage===1" @click="subCurrentPage--">Prev</button>
            <button class="btn btn-sm btn-secondary" :disabled="subCurrentPage===subTotalPages" @click="subCurrentPage++">Next</button></div>
          </div>
        </div>

      </div>
    </div>

    <!-- Edit Comment Modal -->
    <div v-if="commentModalVisible" class="modal-overlay" @click.self="commentModalVisible = false">
      <div class="modal-card">
        <div class="modal-header">
          <h5>Edit Admin Comment</h5>
          <button @click="commentModalVisible = false">✕</button>
        </div>
        <div class="modal-body">
          <textarea v-model="tempComment" class="form-control" rows="3" placeholder="Enter comment..."></textarea>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="commentModalVisible = false">Cancel</button>
          <button class="btn btn-primary" @click="saveComment">Save Comment</button>
        </div>
      </div>
    </div>

    <!-- Approve/Reject Modal -->
    <div v-if="modalVisible" class="modal-overlay" @click.self="modalVisible = false">
      <div class="modal-card">
        <div class="modal-header">
          <h5>{{ modalAction === 'approve' ? 'Approve Submission' : 'Reject Submission' }}</h5>
          <button @click="modalVisible = false">✕</button>
        </div>
        <div class="modal-body">
          <textarea v-model="modalComment" class="form-control" rows="3" placeholder="Enter comment..."></textarea>
          <small v-if="modalAction === 'reject'" class="text-danger">Comment is required for rejection.</small>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" @click="modalVisible = false">Cancel</button>
          <button class="btn btn-primary" @click="submitModalAction">Confirm</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const isAdmin = computed(() => authStore.hasRole(['admin', 'super_admin']))

// ==================== Published Papers ====================
const publishedPapers = ref([])
const pubSearch = ref('')
const pubPerPage = ref(5)
const pubCurrentPage = ref(1)
const activeTab = ref('published')

const fetchPublishedPapers = async () => {
  try {
    const res = await api.get('/academic-papers')
    let papers = Array.isArray(res.data) ? res.data : (res.data.data || [])
    publishedPapers.value = papers.map(p => ({
      ...p,
      lecturerName: p.lecturer?.lecturername || 'N/A'
    }))
  } catch (err) { 
    console.error(err)
    alert('Failed to load published papers.')
  }
}

const filteredPublished = computed(() => {
  let list = publishedPapers.value
  if (pubSearch.value) {
    const term = pubSearch.value.toLowerCase()
    list = list.filter(p => p.title.toLowerCase().includes(term) || p.lecturerName.toLowerCase().includes(term))
  }
  return list
})
const pubTotalPages = computed(() => Math.ceil(filteredPublished.value.length / pubPerPage.value) || 1)
const paginatedPublished = computed(() => {
  const start = (pubCurrentPage.value - 1) * pubPerPage.value
  return filteredPublished.value.slice(start, start + pubPerPage.value)
})

const deletePublished = async (id) => {
  if (!confirm('Delete this paper permanently?')) return
  try {
    await api.delete(`/academic-papers/${id}`)
    await fetchPublishedPapers()
  } catch (err) { alert('Delete failed') }
}

// ==================== Submissions ====================
const submissions = ref([])
const subSearch = ref('')
const subApprovalFilter = ref('')
const subPerPage = ref(5)
const subCurrentPage = ref(1)

const fetchSubmissions = async () => {
  try {
    const params = {}
    if (subApprovalFilter.value) params.approval_status = subApprovalFilter.value
    const res = await api.get('/submitted-papers', { params })
    submissions.value = res.data
  } catch (err) { console.error(err) }
}

const filteredSubmissions = computed(() => {
  let list = submissions.value
  if (subSearch.value) {
    const term = subSearch.value.toLowerCase()
    list = list.filter(s => s.title.toLowerCase().includes(term))
  }
  // Approval filter is already sent to backend, but also filter locally to be safe
  if (subApprovalFilter.value) {
    list = list.filter(s => s.approval_status === subApprovalFilter.value)
  }
  return list
})
const subTotalPages = computed(() => Math.ceil(filteredSubmissions.value.length / subPerPage.value) || 1)
const paginatedSubmissions = computed(() => {
  const start = (subCurrentPage.value - 1) * subPerPage.value
  return filteredSubmissions.value.slice(start, start + subPerPage.value)
})

// const deleteSubmission = async (id) => {
//   if (!confirm('Delete this submission?')) return
//   try {
//     await api.delete(`/submitted-papers/${id}`)
//     await fetchSubmissions()
//   } catch (err) { alert('Delete failed') }
// }

// ==================== Admin Approval / Rejection ====================
const modalVisible = ref(false)
const modalAction = ref('')
const modalComment = ref('')
const currentSubmission = ref(null)

const openApproveModal = (sub) => {
  currentSubmission.value = sub
  modalAction.value = 'approve'
  modalComment.value = ''
  modalVisible.value = true
}
const openRejectModal = (sub) => {
  currentSubmission.value = sub
  modalAction.value = 'reject'
  modalComment.value = ''
  modalVisible.value = true
}
const submitModalAction = async () => {
  if (!currentSubmission.value) return;

  // For rejection, comment is required
  if (modalAction.value === 'reject' && !modalComment.value.trim()) {
    alert('Please provide a comment for rejection.');
    return;
  }

  try {
    if (modalAction.value === 'approve') {
      await api.put(`/submitted-papers/${currentSubmission.value.id}/approve`, { admin_comment: modalComment.value })
    } else {
      await api.put(`/submitted-papers/${currentSubmission.value.id}/reject`, { admin_comment: modalComment.value })
    }
    modalVisible.value = false
    await fetchSubmissions()
    await fetchPublishedPapers()
  } catch (err) {
    alert(err.response?.data?.message || 'Action failed')
  }
}

// Comment edit modal
const commentModalVisible = ref(false)
const tempComment = ref('')
const currentCommentSubmission = ref(null)

const openCommentModal = (sub) => {
  currentCommentSubmission.value = sub
  tempComment.value = sub.admin_comment || ''
  commentModalVisible.value = true
}

const saveComment = async () => {
  if (!currentCommentSubmission.value) return
  try {
    await api.put(`/submitted-papers/${currentCommentSubmission.value.id}/comment`, { admin_comment: tempComment.value })
    commentModalVisible.value = false
    await fetchSubmissions()
  } catch (err) {
    alert(err.response?.data?.message || 'Failed to update comment')
  }
}

watch([pubSearch, subSearch, subApprovalFilter], () => {
  pubCurrentPage.value = 1
  subCurrentPage.value = 1
})

onMounted(async () => {
  await fetchPublishedPapers()
  await fetchSubmissions()
})
</script>

<style scoped>
/* HEADER & BUTTON */
.table-header {
  background: linear-gradient(135deg, #4dabf7, #74c0fc);
}
.btn-add {
  background: linear-gradient(135deg, #1971c2, #4dabf7);
  color: #fff;
  border: none;
  font-size: 0.85rem;
  padding: 0.35rem 0.75rem;
  border-radius: 8px;
}

/* TABLE STYLES */
.table {
  border-radius: 12px;
  overflow: hidden;
  font-size: 0.75rem;  /* increased from 0.6rem */
}
th, td {
  padding: 0.5rem 0.6rem;
  vertical-align: middle;
}
th {
  color: #fff;
  font-weight: 600;
  text-align: center;
  white-space: nowrap;
}
td {
  text-align: center;
  white-space: nowrap;
}
.table-hover tbody tr:hover {
  background-color: rgba(96, 165, 250, 0.15);
}
.table-responsive {
  overflow-x: auto;
}

/* Published table header colors */
.th-id { background: #495057; }
.th-title { background: #5f9cff; }
.th-area { background: #69db7c; }
.th-year { background: #ffd43b; }
.th-type { background: #63e6be; }
.th-index { background: #9775fa; }
.th-cite { background: #ffa94d; }
.th-fund { background: #ff8787; }
.th-collab { background: #748ffc; }
.th-lang { background: #20c997; }
.th-status { background: #adb5bd; }
.th-author { background: #fab005; }
.th-action { background: #868e96; }

/* Submissions table header colors */
.th-sub-id { background: #495057; }
.th-sub-title { background: #5f9cff; }
.th-sub-lecturer { background: #69db7c; }
.th-sub-year { background: #ffd43b; }
.th-sub-publication { background: #63e6be; }
.th-sub-approval { background: #adb5bd; }
.th-sub-comment { background: #748ffc; }
.th-sub-actions { background: #868e96; }

/* Pagination */
.btn-secondary {
  font-size: 0.78rem;
  padding: 0.3rem 0.7rem;
  border-radius: 6px;
}

/* DARK MODE SUPPORT */
[data-bs-theme='dark'] {
  --bs-body-color: #e5e7eb;
  --bs-body-bg: #121212;
}
[data-bs-theme='dark'] .card {
  background-color: #1e1e2f;
  color: #e5e7eb;
}
[data-bs-theme='dark'] .table {
  color: #f1f3c2;
  border-color: #2a2a3c;
}
[data-bs-theme='dark'] th.th-id,
[data-bs-theme='dark'] th.th-sub-id { background: #343a40; }
[data-bs-theme='dark'] th.th-title,
[data-bs-theme='dark'] th.th-sub-title { background: #2563eb; }
[data-bs-theme='dark'] th.th-area,
[data-bs-theme='dark'] th.th-sub-lecturer { background: #16a34a; }
[data-bs-theme='dark'] th.th-year,
[data-bs-theme='dark'] th.th-sub-year { background: #f59e0b; }
[data-bs-theme='dark'] th.th-type,
[data-bs-theme='dark'] th.th-sub-publication { background: #14b8a6; }
[data-bs-theme='dark'] th.th-index { background: #7c3aed; }
[data-bs-theme='dark'] th.th-cite { background: #f97316; }
[data-bs-theme='dark'] th.th-fund { background: #ef4444; }
[data-bs-theme='dark'] th.th-collab,
[data-bs-theme='dark'] th.th-sub-comment { background: #4f46e5; }
[data-bs-theme='dark'] th.th-lang { background: #059669; }
[data-bs-theme='dark'] th.th-status,
[data-bs-theme='dark'] th.th-sub-approval { background: #6c757d; }
[data-bs-theme='dark'] th.th-author { background: #f59e0b; }
[data-bs-theme='dark'] th.th-action,
[data-bs-theme='dark'] th.th-sub-actions { background: #495057; }
[data-bs-theme='dark'] tbody tr:hover {
  background-color: rgba(96, 165, 250, 0.2);
}
</style>