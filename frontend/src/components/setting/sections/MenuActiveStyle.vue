<template>
  <div>
    <h6 class="mt-4 mb-3">Sidebar Active Style</h6>
    <div class="d-grid gap-3 grid-cols-2 mb-4">
      <radio-input 
        btn-name="sidebar_menu_style" 
        :img-component="true" 
        labelClass="p-0 d-block overflow-hidden" 
        label-title="Rounded One Side" 
        class-name="text-center" 
        id="navs-rounded" 
        :default-checked="sidebarMenuStyle" 
        value="sidebar-default navs-rounded" 
        @onChange="updateRadio"
      >
        <img src="@/assets/images/settings/dark/06.png" alt="mini" class="mode dark-img img-fluid" width="200" height="200" loading="lazy" />
        <img src="@/assets/images/settings/light/06.png" alt="mini" class="mode light-img img-fluid" width="200" height="200" loading="lazy" />
      </radio-input>
      <radio-input 
        btn-name="sidebar_menu_style" 
        :img-component="true" 
        labelClass="p-0 d-block overflow-hidden" 
        label-title="Rounded All" 
        class-name="text-center" 
        id="navs-rounded-all" 
        :default-checked="sidebarMenuStyle" 
        value="sidebar-default navs-rounded-all" 
        @onChange="updateRadio"
      >
        <img src="@/assets/images/settings/dark/07.png" alt="hover" class="mode dark-img img-fluid" width="200" height="200" loading="lazy" />
        <img src="@/assets/images/settings/light/07.png" alt="hover" class="mode light-img img-fluid" width="200" height="200" loading="lazy" />
      </radio-input>
      <radio-input 
        btn-name="sidebar_menu_style" 
        :img-component="true" 
        labelClass="p-0 d-block overflow-hidden" 
        label-title="Pill One Side" 
        class-name="text-center" 
        id="navs-pill" 
        :default-checked="sidebarMenuStyle" 
        value="sidebar-default navs-pill" 
        @onChange="updateRadio"
      >
        <img src="@/assets/images/settings/dark/09.png" alt="boxed" class="mode dark-img img-fluid" width="200" height="200" loading="lazy" />
        <img src="@/assets/images/settings/light/09.png" alt="boxed" class="mode light-img img-fluid" width="200" height="200" loading="lazy" />
      </radio-input>
      <radio-input 
        btn-name="sidebar_menu_style" 
        :img-component="true" 
        labelClass="p-0 d-block overflow-hidden" 
        label-title="Pill All" 
        class-name="text-center" 
        id="navs-pill-all" 
        :default-checked="sidebarMenuStyle" 
        value="sidebar-default navs-pill-all" 
        @onChange="updateRadio"
      >
        <img src="@/assets/images/settings/dark/08.png" alt="boxed" class="mode dark-img img-fluid" width="200" height="200" loading="lazy" />
        <img src="@/assets/images/settings/light/08.png" alt="boxed" class="mode light-img img-fluid" width="200" height="200" loading="lazy" />
      </radio-input>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useSettingStore } from '@/stores/setting'
import RadioInput from '@/components/custom/elements/RadioInput'

// Define component
// RadioInput is auto-imported in <script setup> since it's in the template

const settingStore = useSettingStore()

// Get sidebar menu style from store
const sidebarMenuStyle = computed(() => settingStore.sidebarMenuStyle)

// Update handler
const updateRadio = (value) => {
  // name will be 'sidebar_menu_style' from btn-name prop
  settingStore.updateSidebarMenuStyle(value)
}
</script>