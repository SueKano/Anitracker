<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from '../composables/useToast'
import { useAuth } from '../composables/useAuth'

const emit = defineEmits<{ 'login-success': [username: string], 'register-success': [username: string], googleLogin: [] }>()

const toast = useToast()
const { login, register } = useAuth()

const username = ref('')
const password = ref('')
const email = ref('')
const showPassword = ref(false)
const isRegister = ref(false)
const loading = ref(false)

async function handleSubmit() {
  if (!username.value || !password.value) {
    toast.error('Por favor completa todos los campos')
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
      <span class="login-sub">{{ isRegister ? 'Crea tu cuenta' : 'Tu progreso, tu ritmo' }}</span>
    </div>

    <form class="login-form" @submit.prevent="handleSubmit">
      <div class="input-group">
        <label class="input-label" for="login-user">{{ isRegister ? 'Nombre de usuario' : 'Usuario' }}</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/>
          </svg>
          <input id="login-user" v-model="username" type="text" :placeholder="isRegister ? 'Elige un nombre de usuario' : 'Tu nombre de usuario'"
                 autocomplete="username"/>
        </div>
      </div>

      <div class="input-group">
        <div v-if="isRegister">
          <label class="input-label" for="login-email">Email <span class="input-hint">— no se verifica, puedes inventártelo</span></label>
          <div class="input-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>
            </svg>
            <input id="login-email" v-model="email" type="email" placeholder="tu@correo.com" autocomplete="email"/>
          </div>
        </div>

        <label class="input-label" for="login-pass">Contraseña</label>
        <div class="input-wrap">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          <input id="login-pass" v-model="password" :type="showPassword ? 'text' : 'password'"
                 :placeholder="isRegister ? 'Crea una contraseña' : 'Tu contraseña'" :autocomplete="isRegister ? 'new-password' : 'current-password'"/>
          <button type="button" class="btn-eye" @click="showPassword = !showPassword" aria-label="Mostrar contraseña">
            <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8S1 12 1 12z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
              <line x1="1" y1="1" x2="23" y2="23"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login" :disabled="!username.trim() || !password">
        {{ isRegister ? 'Crear cuenta' : 'Iniciar sesión' }}
      </button>
    </form>

    <template v-if="!isRegister">
      <div class="divider"><span>o continúa con</span></div>

      <button class="btn-google" @click="emit('googleLogin')">
        <svg viewBox="0 0 24 24">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18A10.96 10.96 0 0 0 1 12c0 1.77.42 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        Continuar con Google
      </button>
    </template>

    <div class="login-footer">
      <p v-if="!isRegister">¿No tienes cuenta? <button class="btn-register" @click="toggleMode">Crear cuenta</button></p>
      <p v-else>¿Ya tienes cuenta? <button class="btn-register" @click="toggleMode">Iniciar sesión</button></p>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/LoginPage.css';
</style>
