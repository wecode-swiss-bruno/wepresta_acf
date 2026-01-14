import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'


// Create app
const app = createApp(App)

// Install Pinia
const pinia = createPinia()
app.use(pinia)

// Mount to #acf-builder-app
const mountPoint = document.getElementById('acf-builder-app')
if (mountPoint) {
  app.mount('#acf-builder-app')
  mountPoint.classList.add('mounted')
}
