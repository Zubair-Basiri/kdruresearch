<template>
  <div class="col-md-4">
    <div class="card shadow-sm rounded-4 p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <small class="text-muted">{{ title }}</small>
          <h4 class="fw-bold">{{ value }}</h4>
        </div>
        <i :class="`bi ${icon} fs-2 text-primary`"></i>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  title: String,
  value: String,
  icon: String
})
</script>

