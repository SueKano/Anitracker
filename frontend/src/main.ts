import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { i18n, applyDocumentLocale, currentLocale } from './i18n'

applyDocumentLocale(currentLocale())
createApp(App).use(i18n).mount('#app')
