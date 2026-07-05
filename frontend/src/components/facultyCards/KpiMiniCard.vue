<template>
  <div class="kpi-card">
    <div
      class="color-bar"
      :style="{ backgroundColor: color }"
    ></div>

    <div class="kpi-content">
      <div class="kpi-title">{{ title }}</div>
      <div class="kpi-value">{{ value }}</div>
    </div>
  </div>
</template>

<script setup>
/* eslint-disable no-undef */
defineProps({
  title: String,
  value: [String, Number],
  color: {
    type: String,
    default: '#6b7280'
  }
})
</script>

<style scoped>
.kpi-card {
  display: flex;
  background: #ffffff;
  border-radius: 14px;
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
  overflow: hidden;
  min-height: 90px;
}

.color-bar {
  width: 6px;
}

.kpi-content {
  padding: 14px;
  flex: 1;
}

.kpi-title {
  font-size: 12px;
  color: #6b7280;
  margin-bottom: 6px;
  font-weight: 600;
}

.kpi-value {
  font-size: 20px;
  font-weight: 700;
  color: #111827;
}
</style>