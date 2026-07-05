<template>
  <div :class="`content-inner pb-0 ${pageLayout}`" id="page_layout">
    <slot></slot>
  </div>
</template>

<script>
import { computed } from 'vue'
import { useSettingStore } from '@/stores/setting'
export default {
  setup() {
    const store = useSettingStore()
    const pageLayout = computed(() => store.page_layout)
    return {
      pageLayout
    }
  }
}
</script>
