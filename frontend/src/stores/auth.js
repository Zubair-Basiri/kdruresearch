// src/stores/auth.js
import { defineStore } from 'pinia';
import api from '@/services/api';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        loading: false,
        error: null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.user,
        
        // Role-specific getters
        isMinistryAuthority: (state) => state.user?.role === 'ministry_authority',
        isSuperAdmin: (state) => state.user?.role === 'super_admin',
        isAdmin: (state) => state.user?.role === 'admin',
        isAdminAdmin: (state) => state.user?.role === 'admin_admin',
        isLecturerProfileAdmin: (state) => state.user?.role === 'lecturer_profile_admin',
        isUser: (state) => state.user?.role === 'user',
        isGuest: (state) => state.user?.email === 'guest@example.com',

        // Permission getters
        canManageUniversityData: (state) => {
            const role = state.user?.role;
            return ['ministry_authority', 'super_admin', 'admin'].includes(role);
        },
        canManageUniversities: (state) => state.user?.role === 'ministry_authority',
        canManageUsers: (state) => {
            const role = state.user?.role;
            return ['ministry_authority', 'super_admin', 'admin'].includes(role);
        },

        hasRole: (state) => (roles) => {
            if (!state.user) return false;
            if (Array.isArray(roles)) {
                return roles.includes(state.user.role);
            }
            return state.user.role === roles;
        }
    },
    actions: {
        async fetchUser() {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.get('/api/user');
                this.user = response.data;
            } catch (error) {
                this.user = null;
                if (error.response?.status !== 401) {
                    this.error = error.response?.data?.message || 'Failed to fetch user';
                }
            } finally {
                this.loading = false;
            }
        },

        async login(credentials) {
            this.loading = true;
            this.error = null;
            try {
                // First get CSRF cookie
                await api.get('/sanctum/csrf-cookie');
                
                // Small delay to ensure cookie is set
                await new Promise(resolve => setTimeout(resolve, 100));
                
                // Then attempt login
                const response = await api.post('/api/login', credentials);
                this.user = response.data.user;
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Login failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async register(userData) {
            this.loading = true;
            this.error = null;

            try {
                await api.get('/sanctum/csrf-cookie');
                const response = await api.post('/api/register', userData);

                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Registration failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async logout() {
            this.loading = true;
            this.error = null;
            try {
                await api.post('/api/logout');
                this.user = null;
                // Redirect to login
                window.location.href = '/auth/login';
            } catch (error) {
                this.error = error.response?.data?.message || 'Logout failed';
                console.error('Logout error:', error);
                this.user = null;
                window.location.href = '/auth/login';
            } finally {
                this.loading = false;
            }
        },

        async guestLogin() {
            this.loading = true;
            this.error = null;
            try {
                // Get CSRF cookie (if using Sanctum)
                await api.get('/sanctum/csrf-cookie');
                // Small delay for cookie to set
                await new Promise(resolve => setTimeout(resolve, 100));
                const response = await api.post('/api/guest-login');
                this.user = response.data.user;
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Guest login failed';
                throw error;
            } finally {
                this.loading = false;
            }
        },
        async updateUniversity(universityId) {
            this.loading = true;
            this.error = null;
            try {
                const response = await api.put('/api/user/university', { university_id: universityId });
                this.user = response.data.user;
                return response;
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to update university';
                throw error;
            } finally {
                this.loading = false;
            }
        },
    }
});