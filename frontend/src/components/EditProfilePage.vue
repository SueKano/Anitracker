<script setup lang="ts">
import { ref } from 'vue'
import { ChevronLeft, Eye, EyeOff } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'
import { useAccount } from '../composables/useAccount'

const props = defineProps<{ username: string, profileImage: string | null, initialSection: 'profile' | 'password'}>()

const emit = defineEmits<{ back: [], profileUpdated: [username: string] }>()

const toast = useToast()
const { updateProfile, changePassword: changePasswordRequest } = useAccount()

const activeSection = ref<'profile' | 'password'>(props.initialSection)

const newUsername = ref(props.username)
const avatarFile = ref<File | null>(null)
const avatarPreview = ref<string | null>(null)
const profileLoading = ref(false)

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const passwordLoading = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

function onAvatarChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function saveProfile() {
  profileLoading.value = true
  if (await updateProfile(newUsername.value, avatarFile.value)) {
    emit('profileUpdated', newUsername.value)
  }
  profileLoading.value = false
}

async function changePassword() {
  if (newPassword.value !== confirmPassword.value) {
    toast.error('Las contraseñas no coinciden')
    return
  }
  if (newPassword.value.length < 8) {
    toast.error('La contraseña debe tener mínimo 8 caracteres')
    return
  }
  passwordLoading.value = true
  if (await changePasswordRequest(currentPassword.value, newPassword.value)) {
    currentPassword.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
  }
  passwordLoading.value = false
}
</script>

<template>
  <div class="edit-profile">
    <div class="edit-header">
      <button class="btn-back" @click="emit('back')" aria-label="Volver">
        <ChevronLeft :stroke-width="2.2" />
      </button>
      <h1 class="edit-title">
        {{ activeSection === 'password' ? 'Cambiar contraseña' : 'Editar perfil' }}
      </h1>
    </div>

    <div v-if="activeSection === 'profile'" class="section">
      <div class="avatar-upload" @click="($refs.avatarInput as HTMLInputElement).click()">
        <div class="avatar-circle">
          <img v-if="avatarPreview" :src="avatarPreview" alt="Avatar" class="avatar-img" />
          <img v-else-if="profileImage" :src="profileImage" alt="Avatar" class="avatar-img" />
          <span v-else class="avatar-letter">{{ username.charAt(0).toUpperCase() }}</span>
        </div>
        <span class="avatar-label">Cambiar foto</span>
        <input ref="avatarInput" type="file" accept="image/*" hidden @change="onAvatarChange" />
      </div>

      <div class="field">
        <label class="field-label">Nombre de usuario</label>
        <input v-model="newUsername" type="text" class="field-input" placeholder="Tu nombre" />
      </div>

      <button class="btn-save" :disabled="profileLoading" @click="saveProfile">
        {{ profileLoading ? 'Guardando...' : 'Guardar cambios' }}
      </button>
    </div>

    <div v-if="activeSection === 'password'" class="section">
      <div class="field">
        <label class="field-label">Contraseña actual</label>
        <div class="field-wrap">
          <input v-model="currentPassword" :type="showCurrentPassword ? 'text' : 'password'" class="field-input" placeholder="Tu contraseña actual" autocomplete="current-password" />
          <button type="button" class="btn-eye" @click="showCurrentPassword = !showCurrentPassword" :aria-label="showCurrentPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
            <Eye v-if="!showCurrentPassword" :stroke-width="1.8" />
            <EyeOff v-else :stroke-width="1.8" />
          </button>
        </div>
      </div>

      <div class="field">
        <label class="field-label">Nueva contraseña</label>
        <div class="field-wrap">
          <input v-model="newPassword" :type="showNewPassword ? 'text' : 'password'" class="field-input" placeholder="Mínimo 8 caracteres" autocomplete="new-password" />
          <button type="button" class="btn-eye" @click="showNewPassword = !showNewPassword" :aria-label="showNewPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
            <Eye v-if="!showNewPassword" :stroke-width="1.8" />
            <EyeOff v-else :stroke-width="1.8" />
          </button>
        </div>
      </div>

      <div class="field">
        <label class="field-label">Confirmar contraseña</label>
        <div class="field-wrap">
          <input v-model="confirmPassword" :type="showConfirmPassword ? 'text' : 'password'" class="field-input" placeholder="Repite la nueva contraseña" autocomplete="new-password" />
          <button type="button" class="btn-eye" @click="showConfirmPassword = !showConfirmPassword" :aria-label="showConfirmPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
            <Eye v-if="!showConfirmPassword" :stroke-width="1.8" />
            <EyeOff v-else :stroke-width="1.8" />
          </button>
        </div>
      </div>

      <button class="btn-save" :disabled="passwordLoading || !currentPassword || !newPassword || !confirmPassword" @click="changePassword">
        {{ passwordLoading ? 'Cambiando...' : 'Cambiar contraseña' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/EditProfilePage.css';
</style>