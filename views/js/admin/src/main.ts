import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'

// #region agent log - Debug Vue init and jQuery state
const debugLog = (msg: string, data: Record<string, unknown>) => {
  fetch('http://127.0.0.1:7255/ingest/f2bffaed-a8e3-4b3c-ad3b-1dc62db7151d',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({location:'main.ts',message:msg,data,timestamp:Date.now(),sessionId:'debug-session',hypothesisId:'H3'})}).catch(()=>{});
};
debugLog('Vue main.ts executing', {
  jQueryBefore: typeof (window as any).jQuery !== 'undefined',
  dollarBefore: typeof (window as any).$ !== 'undefined'
});
// #endregion

// Create app
const app = createApp(App)

// Install Pinia
const pinia = createPinia()
app.use(pinia)

// Mount to #acf-builder-app
const mountPoint = document.getElementById('acf-builder-app')
if (mountPoint) {
  // #region agent log - Before Vue mount
  debugLog('Before Vue mount', {
    jQueryBefore: typeof (window as any).jQuery !== 'undefined',
    dollarBefore: typeof (window as any).$ !== 'undefined'
  });
  // #endregion
  app.mount('#acf-builder-app')
  mountPoint.classList.add('mounted')
  // #region agent log - After Vue mount
  debugLog('After Vue mount', {
    jQueryAfter: typeof (window as any).jQuery !== 'undefined',
    dollarAfter: typeof (window as any).$ !== 'undefined'
  });
  // #endregion
}
