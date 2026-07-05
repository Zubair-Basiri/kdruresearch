<template>
  <div class="card">
    <div class="card-header">
      <span>{{ title }}</span>

      <!-- <select v-if="filter" class="mini-select">
        <option>2024</option>
        <option>2023</option>
        <option>2022</option>
      </select> -->
    </div>

    <div class="card-body">
      <slot/>
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  title: String,
  filter: Boolean
})
</script>

<style scoped>
.card{
  background:#fff;
  border-radius:14px;
  padding:10px;
  box-shadow:0 15px 40px rgba(0,0,0,.08);
  height:450px;
}
.card-header{
  display:flex;
  justify-content:space-between;
  font-weight:600;
  font-size:14px;
}
.mini-select{
  font-size:12px;
  padding:2px 6px;
}

.chart-card {
  width: 100%;
}

.card-body{
  margin-top:-20px;
}
</style>
