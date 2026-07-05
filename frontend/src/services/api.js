// src/services/api.js
import axios from 'axios';

// Base URL should NOT include /api
const baseURL = process.env.VUE_APP_API_URL || 'http://localhost:8000';

const api = axios.create({
  baseURL: baseURL,
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

// Helper to get CSRF token from cookie
const getCsrfToken = () => {
  const cookies = document.cookie.split('; ');
  for (let cookie of cookies) {
    const [name, value] = cookie.split('=');
    if (name === 'XSRF-TOKEN') {
      return decodeURIComponent(value);
    }
  }
  return null;
};

// Request interceptor
api.interceptors.request.use(config => {
  // Ensure correct URL paths
  if (!config.url.startsWith('http')) {
    if (config.url === '/sanctum/csrf-cookie') {
      config.url = '/sanctum/csrf-cookie';
    } else if (!config.url.startsWith('/api')) {
      config.url = `/api${config.url}`;
    }
  }

  // Manually set X-XSRF-TOKEN header for non-GET requests
  if (config.method !== 'get') {
    const token = getCsrfToken();
    if (token) {
      config.headers['X-XSRF-TOKEN'] = token;
      console.log('CSRF token added to headers');
    } else {
      console.warn('No CSRF token found for non-GET request');
    }
  }

  console.log('Request:', config.method.toUpperCase(), config.url);
  console.log('Headers:', config.headers);
  return config;
});

// Response interceptor for debugging
api.interceptors.response.use(
  response => {
    console.log('Response:', response.status, response.config.url);
    return response;
  },
  error => {
    console.error('Error:', error.response?.status, error.config?.url, error.response?.data);
    return Promise.reject(error);
  }
);

export default api;