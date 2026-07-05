<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-card">
        <!-- Logo and Welcome -->
        <div class="login-header">
          <div class="logo">
            <svg viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M20 5L5 12.5V27.5L20 35L35 27.5V12.5L20 5Z" stroke="#4F46E5" stroke-width="2" fill="#E0E7FF"/>
              <circle cx="20" cy="20" r="5" fill="#4F46E5"/>
              <path d="M20 8V15M20 25V32M32 20H25M15 20H8" stroke="#4F46E5" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <h1>Welcome Back</h1>
          <p class="subtitle">Sign in to your academic dashboard</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin">
          <div class="form-group">
            <label for="username">Username</label>
            <input
              type="text"
              id="username"
              v-model="username"
              placeholder="Enter your username"
              required
            />
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input
              type="password"
              id="password"
              v-model="password"
              placeholder="Enter your password"
              required
            />
          </div>

          <button type="submit" class="login-btn">Sign In</button>
        </form>

        <!-- Footer Credit -->
        <div class="login-footer">
          <p>
            Designed with 🎓 for Laravel API
            <span class="api-badge">v11</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const username = ref('')
const password = ref('')

const handleLogin = () => {
  // Here you would call your authentication API
  console.log('Login attempt', { username: username.value, password: password.value })
  alert('Login functionality would connect to Laravel API')
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(145deg, #f6f0e7 0%, #e6dcd2 100%);
  position: relative;
  overflow: hidden;
}

/* Watercolor background effect */
.login-page::before {
  content: '';
  position: absolute;
  top: -20%;
  left: -10%;
  width: 70%;
  height: 70%;
  background: radial-gradient(circle at 30% 50%, rgba(149, 187, 220, 0.3) 0%, transparent 50%),
              radial-gradient(circle at 80% 70%, rgba(200, 160, 140, 0.2) 0%, transparent 40%);
  filter: blur(60px);
  border-radius: 50%;
  z-index: 0;
}

.login-page::after {
  content: '';
  position: absolute;
  bottom: -20%;
  right: -10%;
  width: 70%;
  height: 70%;
  background: radial-gradient(circle at 70% 50%, rgba(160, 140, 180, 0.25) 0%, transparent 50%),
              radial-gradient(circle at 20% 80%, rgba(120, 160, 150, 0.2) 0%, transparent 40%);
  filter: blur(60px);
  border-radius: 50%;
  z-index: 0;
}

.login-container {
  width: 100%;
  max-width: 440px;
  padding: 20px;
  position: relative;
  z-index: 1;
}

.login-card {
  background: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 32px;
  padding: 40px 32px;
  box-shadow: 0 30px 60px -20px rgba(0, 0, 0, 0.3),
              0 0 0 1px rgba(255, 255, 255, 0.6) inset;
  border: 1px solid rgba(255, 255, 255, 0.4);
}

.login-header {
  text-align: center;
  margin-bottom: 36px;
}

.logo svg {
  width: 64px;
  height: 64px;
  margin-bottom: 16px;
}

h1 {
  font-size: 28px;
  font-weight: 600;
  color: #2c3e50;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.subtitle {
  color: #5a6b7a;
  font-size: 15px;
  margin: 0;
  font-weight: 400;
}

.form-group {
  margin-bottom: 24px;
}

label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #2c3e50;
  margin-bottom: 8px;
}

input {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 16px;
  font-size: 15px;
  background: rgba(255, 255, 255, 0.8);
  transition: all 0.2s;
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: #4F46E5;
  background: white;
  box-shadow: 0 4px 12px rgba(79, 70, 229, 0.15);
}

input::placeholder {
  color: #9aa9b7;
  opacity: 0.6;
}

.login-btn {
  width: 100%;
  padding: 14px;
  background: #4F46E5;
  color: white;
  border: none;
  border-radius: 16px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  margin-top: 8px;
  box-shadow: 0 8px 20px -8px rgba(79, 70, 229, 0.6);
}

.login-btn:hover {
  background: #4338ca;
  transform: translateY(-2px);
  box-shadow: 0 12px 24px -8px rgba(79, 70, 229, 0.8);
}

.login-footer {
  text-align: center;
  margin-top: 32px;
  color: #5a6b7a;
  font-size: 13px;
}

.api-badge {
  background: rgba(79, 70, 229, 0.1);
  color: #4F46E5;
  padding: 4px 8px;
  border-radius: 30px;
  font-weight: 500;
  margin-left: 6px;
  font-size: 11px;
  border: 1px solid rgba(79, 70, 229, 0.2);
}

/* Optional: add some academic icons via pseudo-elements */
.login-card {
  position: relative;
  overflow: hidden;
}

.login-card::after {
  content: '📚';
  position: absolute;
  bottom: -20px;
  right: -20px;
  font-size: 120px;
  opacity: 0.03;
  transform: rotate(-10deg);
  pointer-events: none;
}

.login-card::before {
  content: '✎';
  position: absolute;
  top: -30px;
  left: -20px;
  font-size: 140px;
  opacity: 0.03;
  transform: rotate(15deg);
  pointer-events: none;
}

@media (max-width: 480px) {
  .login-card {
    padding: 32px 24px;
  }
  
  h1 {
    font-size: 24px;
  }
}
</style>