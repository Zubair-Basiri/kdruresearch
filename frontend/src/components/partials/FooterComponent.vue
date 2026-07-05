<template>
  <!-- Footer Section Start -->
  <footer :class="`footer ${footerStyle}`">
    <div class="footer-body">
      <ul class="left-panel list-inline mb-0 p-0">
        <li class="list-inline-item">
          <router-link :to="{ name: 'default.privacy-policy' }">Privacy Policy</router-link>
        </li>
        <!-- <li class="list-inline-item"><router-link :to="{ name: 'default.terms-and-conditions' }">Terms of Use</router-link></li> -->
      </ul>
      <div class="right-panel">
        ©2026, All Rights Reserved. Made 
        by <a href="https://kdru.edu.af/" target="_blank">Kandahar University</a>
        <a href="https://vcresearch.kdru.edu.af/" target="_blank">, Vice Chancellery of Research</a>.
      </div>
    </div>
  </footer>
  <!-- Footer Section End -->
  <b-offcanvas 
    v-model="shareOffcanvas" 
    @hide="hideShareOffcanvas" 
    placement="bottom" 
    title="Share"
  >
    <share-offcanvas></share-offcanvas>
  </b-offcanvas>
</template>

<script setup>
import { computed, watch, ref } from 'vue'
import { useSettingStore } from '@/stores/setting'
import { useMainStore } from '@/stores/main'
// import ShareOffcanvas from '@/components/widgets/ShareOffcanvasNew.vue'

// Define components
// Note: In <script setup>, components are auto-registered, but we need to register ShareOffcanvas
// Since it's not auto-imported, we need to add it to the template's scope
// We can either import it and use it directly (which works in <script setup>)
// So no extra registration needed

const settingStore = useSettingStore()
const mainStore = useMainStore()

const footerStyle = computed(() => settingStore.footerStyle)

// Bottom Canvas
const shareOffcanvas = ref(mainStore.shareOffcanvas)

// Watch for changes in the store
watch(
  () => mainStore.shareOffcanvas,
  (newValue) => {
    shareOffcanvas.value = newValue
  }
)

const hideShareOffcanvas = () => {
  mainStore.closeShareOffcanvas()
}
</script>