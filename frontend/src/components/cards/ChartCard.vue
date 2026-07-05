<template>
  <div class="card shadow-sm rounded-4">
    <div class="card-header bg-white fw-bold">
      {{ title }}
    </div>
    <div class="card-body">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'

defineProps({
  title: String
})
</script>