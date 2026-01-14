import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHashHistory } from 'vue-router'
import CptApp from './components/cpt/CptApp.vue'
import PostList from './components/cpt/PostList.vue'
import PostForm from './components/cpt/PostForm.vue'

// Define routes
const routes = [
    { path: '/', component: PostList },
    { path: '/new', component: PostForm },
    { path: '/edit/:id', component: PostForm, props: true },
]

// Create router
const router = createRouter({
    history: createWebHashHistory(),
    routes,
})

// Create app
const app = createApp(CptApp)

// Install Pinia
const pinia = createPinia()
app.use(pinia)
app.use(router)

// Mount
const mountPoint = document.getElementById('cpt-app')
if (mountPoint) {
    // Extract config from DOM
    const config = {
        typeSlug: mountPoint.dataset.typeSlug || '',
        typeName: mountPoint.dataset.typeName || '',
        typeId: parseInt(mountPoint.dataset.typeId || '0'),
        apiUrl: mountPoint.dataset.apiUrl || '',
        languages: JSON.parse(mountPoint.dataset.languages || '[]'),
        defaultLangId: parseInt(mountPoint.dataset.defaultLangId || '0'),
        shopId: parseInt(mountPoint.dataset.shopId || '0'),
        token: mountPoint.dataset.token || '',
    }

    // Make config available globally or via provide/inject
    app.provide('cptConfig', config)

    // Also expose to window for existing helpers if needed
    window.acfConfig = {
        ...window.acfConfig, // Merge if exists
        apiUrl: config.apiUrl,
        token: config.token,
        languages: config.languages,
        defaultLangId: config.defaultLangId
    }

    app.mount('#cpt-app')
    mountPoint.classList.add('mounted')
}
