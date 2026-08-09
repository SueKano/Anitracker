import { createApp } from 'vue'
import '@fontsource-variable/fraunces/opsz.css'
import '@fontsource-variable/fraunces/opsz-italic.css'
import '@fontsource-variable/dm-sans/opsz.css'
import '@fontsource-variable/dm-sans/opsz-italic.css'
import './style.css'
import App from './App.vue'
import { i18n, applyDocumentLocale, currentLocale } from './i18n'

applyDocumentLocale(currentLocale())
createApp(App).use(i18n).mount('#app')
