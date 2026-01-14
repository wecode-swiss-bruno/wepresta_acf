import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'


// Create app
const app = createApp(App)

// Install Pinia
const pinia = createPinia()
app.use(pinia)

// Mount to #builder-app
const mountPoint = document.getElementById('builder-app')
if (mountPoint) {
  // ARCHITECTURE: Since we are in the Main App (BuilderController), we must provide 
  // the configuration for CPT components which run as children here.
  // This bridges the data provided by BuilderController to child CPT components.
  if ((window as any).acfConfig) {
    const acfConfig = (window as any).acfConfig
    // Map acfConfig (Main App) to cptConfig (CPT App) structure
    const cptConfig = {
      apiUrl: acfConfig.apiUrl,
      languages: acfConfig.languages,
      defaultLangId: acfConfig.currentLangId || 1, // Fallback
      shopId: acfConfig.shopId,
      token: acfConfig.token
    }
    app.provide('cptConfig', cptConfig)
  }

  app.mount('#builder-app')
  mountPoint.classList.add('mounted')
}
