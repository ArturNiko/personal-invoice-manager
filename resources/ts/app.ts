import { createApp } from 'vue';
import App from './App.vue';
import { router } from './router';
import { initTheme } from './Composables/useTheme';

initTheme();

createApp(App).use(router).mount('#app');
