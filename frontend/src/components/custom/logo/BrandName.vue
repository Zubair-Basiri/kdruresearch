<template>
  {{ appName }}
</template>

<script>
// import { computed } from 'vue'
// import { useStore } from 'vuex'
export default {
  setup() {
    // const store = useStore()
    const appName = "KURD"
    return { appName }
  }
}
</script>
