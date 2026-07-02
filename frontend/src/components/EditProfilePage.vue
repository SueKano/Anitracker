<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, Eye, EyeOff } from 'lucide-vue-next'
import { useToast } from '../composables/useToast'
import { useAccount } from '../composables/useAccount'

const props = defineProps<{ username: string, initialSection: 'profile' | 'password'}>()
const emit = defineEmits<{ back: [], profileUpdated: [username: string] }>()

const toast = useToast()
const { renameUser, changePassword: changePasswordRequest } = useAccount()
const { t } = useI18n()

const activeSection = ref<'profile' | 'password'>(props.initialSection)

const newUsername = ref(props.username)
const profileLoading = ref(false)

const currentPassword = ref('')
const newPassword = ref('')
const confirmPassword = ref('')
const passwordLoading = ref(false)
const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

async function saveProfile() {
  profileLoading.value = true
  if (await renameUser(newUsername.value)) {
    emit('profileUpdated', newUsername.value)
  }
  profileLoading.value = false
}

async function changePassword() {
  if (newPassword.value !== confirmPassword.value) {
    toast.error(t('toast.passwordsDontMatch'))
    return
  }
  if (newPassword.value.length < 8) {
    toast.error(t('toast.passwordTooShort'))
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
      <button class="btn-back" @click="emit('back')" :aria-label="t('editProfile.backAria')">
        <ChevronLeft :stroke-width="2.2" />
      </button>
      <h1 class="edit-title">
        {{ activeSection === 'password' ? t('editProfile.titlePassword') : t('editProfile.titleProfile') }}
      </h1>
    </div>

    <div v-if="activeSection === 'profile'" class="section">
      <div class="field">
        <label class="field-label">{{ t('editProfile.username') }}</label>
        <input v-model="newUsername" type="text" class="field-input" :placeholder="t('editProfile.usernamePlaceholder')" />
      </div>

      <button class="btn-save" :disabled="profileLoading" @click="saveProfile">
        {{ profileLoading ? t('editProfile.saving') : t('editProfile.saveChanges') }}
      </button>
    </div>

    <div v-if="activeSection === 'password'" class="section">
      <div class="field">
        <label class="field-label">{{ t('editProfile.currentPassword') }}</label>
        <div class="field-wrap">
          <input v-model="currentPassword" :type="showCurrentPassword ? 'text' : 'password'" class="field-input" :placeholder="t('editProfile.currentPasswordPlaceholder')" autocomplete="current-password" />
          <button type="button" class="btn-eye" @click="showCurrentPassword = !showCurrentPassword" :aria-label="showCurrentPassword ? t('editProfile.hidePasswordAria') : t('editProfile.showPasswordAria')">
            <Eye v-if="!showCurrentPassword" :stroke-width="1.8" />
            <EyeOff v-else :stroke-width="1.8" />
          </button>
        </div>
      </div>

      <div class="field">
        <label class="field-label">{{ t('editProfile.newPassword') }}</label>
        <div class="field-wrap">
          <input v-model="newPassword" :type="showNewPassword ? 'text' : 'password'" class="field-input" :placeholder="t('editProfile.newPasswordPlaceholder')" autocomplete="new-password" />
          <button type="button" class="btn-eye" @click="showNewPassword = !showNewPassword" :aria-label="showNewPassword ? t('editProfile.hidePasswordAria') : t('editProfile.showPasswordAria')">
            <Eye v-if="!showNewPassword" :stroke-width="1.8" />
            <EyeOff v-else :stroke-width="1.8" />
          </button>
        </div>
      </div>

      <div class="field">
        <label class="field-label">{{ t('editProfile.confirmPassword') }}</label>
        <div class="field-wrap">
          <input v-model="confirmPassword" :type="showConfirmPassword ? 'text' : 'password'" class="field-input" :placeholder="t('editProfile.confirmPasswordPlaceholder')" autocomplete="new-password" />
          <button type="button" class="btn-eye" @click="showConfirmPassword = !showConfirmPassword" :aria-label="showConfirmPassword ? t('editProfile.hidePasswordAria') : t('editProfile.showPasswordAria')">
            <Eye v-if="!showConfirmPassword" :stroke-width="1.8" />
            <EyeOff v-else :stroke-width="1.8" />
          </button>
        </div>
      </div>

      <button class="btn-save" :disabled="passwordLoading || !currentPassword || !newPassword || !confirmPassword" @click="changePassword">
        {{ passwordLoading ? t('editProfile.changing') : t('editProfile.changePassword') }}
      </button>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/EditProfilePage.css';
</style>