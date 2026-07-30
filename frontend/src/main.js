import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import './registerServiceWorker';
import router from './router';
import VuePersianDatetimePicker from 'vue-persian-datetime-picker';

// Library Components
import VueSweetalert2 from 'vue-sweetalert2';
import VueApexCharts from 'vue3-apexcharts';
import BootstrapVue3 from 'bootstrap-vue-3';
import CounterUp from 'vue3-autocounter';
import 'aos/dist/aos.css';

// Custom Components & Directives
import globalComponent from './plugins/global-components';
import globalDirective from './plugins/global-directive';
import globalMixin from './plugins/global-mixin';

require('waypoints/lib/noframework.waypoints.min');

const app = createApp(App);
const pinia = createPinia();

// Apply plugins in order (all before mounting)
app.use(pinia);
app.use(router);
app.component('date-picker', VuePersianDatetimePicker);

// Library Components
app.use(VueSweetalert2);
app.use(VueApexCharts);
app.use(BootstrapVue3);
app.component('counter-up', CounterUp);

// Custom Components & Directives
app.use(globalComponent);
app.use(globalDirective);
app.mixin(globalMixin);

// Mount only once
app.mount('#app');