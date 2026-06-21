<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useToast } from '../composables/useToast'
import { useAuth } from '../composables/useAuth'
import { User, Mail, Lock, Eye, EyeOff } from 'lucide-vue-next'

const emit = defineEmits<{ 'login-success': [username: string], 'register-success': [username: string], googleLogin: [] }>()

const toast = useToast()
const { login, register } = useAuth()
const { t } = useI18n()

const username = ref('')
const password = ref('')
const email = ref('')
const showPassword = ref(false)
const isRegister = ref(false)
const loading = ref(false)

async function handleSubmit() {
  if (!username.value || !password.value) {
    toast.error(t('toast.fillAllFields'))
    return
  }
  loading.value = true
  if (isRegister.value) {
    if (await register(username.value, password.value, email.value)) emit('register-success', username.value)
  } else {
    if (await login(username.value, password.value)) emit('login-success', username.value)
  }
  loading.value = false
}

function toggleMode() {
  isRegister.value = !isRegister.value
  username.value = ''
  password.value = ''
  showPassword.value = false
}
</script>

<template>
  <div class="login">
    <div class="login-brand">
      <img src="/favicon.svg" alt="" class="login-icon" />
      <span class="login-wordmark">AniTracker</span>
      <span class="login-sub">{{ isRegister ? t('login.subCreate') : t('login.subLogin') }}</span>
    </div>

    <form class="login-form" @submit.prevent="handleSubmit">
      <div class="input-group">
        <label class="input-label" for="login-user">{{ isRegister ? t('login.userLabelRegister') : t('login.userLabelLogin') }}</label>
        <div class="input-wrap">
          <User :stroke-width="1.8" />
          <input id="login-user" v-model="username" type="text" :placeholder="isRegister ? t('login.userPlaceholderRegister') : t('login.userPlaceholderLogin')"
                 autocomplete="username"/>
        </div>
      </div>

      <div class="input-group">
        <div v-if="isRegister">
          <label class="input-label" for="login-email">{{ t('login.emailLabel') }} <span class="input-hint">{{ t('login.emailHint') }}</span></label>
          <div class="input-wrap">
            <Mail :stroke-width="1.8" />
            <input id="login-email" v-model="email" type="email" :placeholder="t('login.emailPlaceholder')" autocomplete="email"/>
          </div>
        </div>

        <label class="input-label" for="login-pass">{{ t('login.passwordLabel') }}</label>
        <div class="input-wrap">
          <Lock :stroke-width="1.8" />
          <input id="login-pass" v-model="password" :type="showPassword ? 'text' : 'password'"
                 :placeholder="isRegister ? t('login.passwordPlaceholderRegister') : t('login.passwordPlaceholderLogin')"
                 :autocomplete="isRegister ? 'new-password' : 'current-password'"/>
          <button type="button" class="btn-eye" @click="showPassword = !showPassword" :aria-label="t('login.showPasswordAria')">
            <Eye v-if="!showPassword" :stroke-width="1.8" />
            <EyeOff v-else :stroke-width="1.8" />
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login" :disabled="!username.trim() || !password">
        {{ isRegister ? t('login.submitRegister') : t('login.submitLogin') }}
      </button>
    </form>

    <template v-if="!isRegister">
      <div class="divider"><span>{{ t('login.orContinue') }}</span></div>

      <button class="btn-google" @click="emit('googleLogin')">
        <svg viewBox="0 0 24 24">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        {{ t('login.google') }}
      </button>
    </template>

    <div class="login-footer">
      <p v-if="!isRegister">{{ t('login.noAccount') }} <button class="btn-register" @click="toggleMode">{{ t('login.createAccount') }}</button></p>
      <p v-else>{{ t('login.haveAccount') }} <button class="btn-register" @click="toggleMode">{{ t('login.goLogin') }}</button></p>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/LoginPage.css';
</style>
