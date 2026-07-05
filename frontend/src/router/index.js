// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import UserRegister from '@/views/auth/default/UserRegister.vue'

// Auth Default Routes
const authChildRoutes = (prefix) => [
  {
    path: 'login',
    name: prefix + '.login',
    meta: { requiresAuth: false, name: 'Login' },
    component: () => import('@/views/auth/default/SignIn.vue')
  },

  {
    path: '/user-register',
    name: 'auth.user-register',
    component: UserRegister,
    meta: { guest: true } // no authentication required
  },
  // {
  //   path: 'register',
  //   name: prefix + '.register',
  //   meta: { auth: false, name: 'Register' },
  //   component: () => import('@/views/auth/default/SignUp.vue')
  // },
  {
    path: 'reset-password',
    name: prefix + '.reset-password',
    meta: { auth: false, name: 'Reset Password' },
    component: () => import('@/views/auth/default/ResetPassword.vue')
  },
  {
    path: 'lock-screen',
    name: prefix + '.lock-screen',
    meta: { auth: false, name: 'Lock Screen' },
    component: () => import('@/views/auth/default/LockScreen.vue')
  }
];

// Main app routes (protected)
const defaultChildRoutes = (prefix) => [
  {
    path: '', // This makes it render at /dashboard
    name: prefix + '.dashboard',
    meta: { auth: true, name: 'Dashboard' },
    component: () => import('@/views/dashboards/IndexPage.vue')
  },
  {
    path: 'keyFindings',
    name: prefix + '.keyFindings',
    meta: { auth: true, name: 'Key Findings', isBanner: true },
    component: () => import('@/views/charts/KeyFindings.vue')
  },
  {
    path: 'topResearcher',
    name: prefix + '.topResearcher',
    meta: { auth: true, name: 'Top Researcher', isBanner: true },
    component: () => import('@/views/charts/TopResearcher.vue')
  },
  // {
  //   path: 'facultySummary',
  //   name: prefix + '.facultySummary',
  //   meta: { auth: true, name: 'Faculty Summary', isBanner: true },
  //   component: () => import('@/views/charts/FacultySummary.vue')
  // },
  {
    path: 'facultySummaryTable',
    name: prefix + '.facultySummaryTable',
    meta: { auth: true, name: 'Faculty Summary Table', isBanner: true },
    component: () => import('@/views/charts/FacultySummaryTable.vue')
  },
  {
    path: 'facultyaggregation',
    name: prefix + '.facultyAggregation',
    meta: { auth: true, name: 'Faculty Aggregation', isBanner: true },
    component: () => import('@/views/charts/FacultyAggregate.vue')
  },
  {
    path: 'YearSummary',
    name: prefix + '.YearSummary',
    meta: { auth: true, name: 'Year Summary', isBanner: true },
    component: () => import('@/views/charts/YearSummary.vue')
  },
  {
    path: 'GradeSummary',
    name: prefix + '.GradeSummary',
    meta: { auth: true, name: 'Grade Summary', isBanner: true },
    component: () => import('@/views/charts/GradeSummary.vue')
  },
  {
    path: 'universityList',
    name: 'universityList',
    meta: { auth: true, name: 'University List', isBanner: true },
    component: () => import('@/views/addition/UniversityList.vue'),
  },
  {
    path: 'facultyTable',
    name: 'facultyTable',
    meta: { auth: true, name: 'Faculty Table', isBanner: true },
    component: () => import('@/views/addition/FacultyTable.vue'),
  },
  {
    path: 'departmentTable',
    name: 'departmentTable',
    meta: { auth: true, name: 'Department Table', isBanner: true },
    component: () => import('@/views/addition/DepartmentTable.vue'),
  },
  {
    path: 'teachers',
    name: 'teachers',
    meta: { auth: true, name: 'Teachers', isBanner: true },
    component: () => import('@/views/addition/TeacherList.vue')
  },
  {
    path: 'teachers/form',
    name: 'teachersCreate',
    meta: { auth: true, name: 'Add Lecturer', isBanner: true },
    component: () => import('@/views/addition/TeacherForm.vue')
  },
  {
    path: 'teachers/form/:id',
    name: 'teachersEdit',
    meta: { auth: true, name: 'Edit Lecturer', isBanner: true },
    component: () => import('@/views/addition/TeacherForm.vue')
  },
  {
    path: 'academicJournals',
    name: 'academicJournals',
    meta: { auth: true, name: 'Academic Journals', isBanner: true },
    component: () => import('@/views/addition/AcademicJournalList.vue')
  },
  {
    path: 'academicJournals/form',
    name: 'academicJournalsCreate',
    meta: { auth: true, name: 'Add Academic Journal', isBanner: true },
    component: () => import('@/views/addition/AcademicJournalForm.vue')
  },
  {
    path: 'academicJournals/form/:id',
    name: 'academicJournalsEdit',
    meta: { auth: true, name: 'Edit Academic Journal', isBanner: true },
    component: () => import('@/views/addition/AcademicJournalForm.vue')
  },
  // User management (admin only)
  {
    path: 'user-list',
    name: 'default.user-list',
    meta: { auth: true, role: ['super_admin'], name: 'User List' },
    component: () => import('@/views/user/ListPage.vue')
  },
  {
    path: 'user-add',
    name: 'default.user-add',
    meta: { auth: true, role: ['super_admin'], name: 'User Add', isBanner: true },
    component: () => import('@/views/user/AddPage.vue')
  },
  {
    path: 'user-edit/:id',
    name: 'default.user-edit',
    meta: { auth: true, role: ['super_admin'], name: 'User Edit', isBanner: true },
    component: () => import('@/views/user/AddPage.vue')
  },
  {
    path: 'user-profile',
    name: 'default.user-profile',
    meta: { auth: true, name: 'User Profile', isBanner: true },
    component: () => import('@/views/user/ProfilePage.vue')
  },
  // Super admin only
  {
    path: 'admin-permissions',
    name: 'default.admin-permissions',
    meta: { auth: true, role: 'super_admin', name: 'Admin Permissions' },
    component: () => import('@/views/admin/AdminPage.vue')
  },
  // Privacy Policy
  {
    path: 'privacy-policy',
    name: 'default.privacy-policy',
    meta: { auth: true, name: 'Privacy Policy' },
    component: () => import('@/views/extra/PrivacyPolicy.vue')
  }
];

const routes = [
  // Redirect root to login
  {
    path: '/',
    redirect: { name: 'auth.login' }
  },
  // Auth routes (login, register, etc.)
  {
    path: '/auth',
    name: 'auth',
    component: () => import('@/layouts/guest/BlankLayout.vue'),
    children: authChildRoutes('auth')
  },
  // Main dashboard layout (protected)
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/layouts/DefaultLayout.vue'),
    children: defaultChildRoutes('default')
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    meta: { auth: true },
    component: () => import('@/views/errors/Error404Page.vue')
  }
];

const router = createRouter({
  linkActiveClass: 'active',
  linkExactActiveClass: 'exact-active',
  history: createWebHistory(process.env.BASE_URL),
  routes
});

// Navigation guard with role check
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();

  // Fetch user if not loaded
  if (authStore.user === null && !authStore.loading) {
    await authStore.fetchUser();
  }

  // Check if route requires authentication
  const requiresAuth = to.matched.some(record => record.meta.auth || record.meta.requiresAuth);
  
  // Check if route requires specific role
  const requiredRole = to.matched.find(record => record.meta.role)?.meta.role;
  
  // List of auth routes (no authentication required)
  const authRoutes = ['auth.login', 'auth.reset-password', 'auth.lock-screen'];

  // If route requires auth and user is not authenticated
  if (requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'auth.login' });
  } 
  // If user is authenticated and trying to access auth routes
  else if (!requiresAuth && authStore.isAuthenticated && authRoutes.includes(to.name)) {
    next({ name: 'default.dashboard' });
  } 
  // If route requires specific role
  else if (requiresAuth && authStore.isAuthenticated && requiredRole) {
    if (authStore.hasRole(requiredRole)) {
      next();
    } else {
      // Redirect to dashboard if user doesn't have required role
      next({ name: 'default.dashboard' });
    }
  } 
  else {
    next();
  }
});

export default router;