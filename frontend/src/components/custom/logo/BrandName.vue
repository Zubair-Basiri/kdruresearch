<template>
  {{ appName }}
</template>

<script>
// import { computed } from 'vue'
// import { useStore } from 'vuex'
export default {
  setup() {
    // const store = useStore()
    const appName = "ARPMS"
    return { appName }
  }
}
</script>
