<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps<{ title: string; initialScore: number }>()
const emit = defineEmits<{ save: [score: number], close: [] }>()

const { t } = useI18n()

const scoreInput = ref<number | string>(props.initialScore || '')
const parsedScore = computed(() => Number(scoreInput.value))
const isValid = computed(() => Number.isInteger(parsedScore.value) && parsedScore.value >= 1 && parsedScore.value <= 10)

function save() {
  if (isValid.value) emit('save', parsedScore.value)
}
</script>

<template>
  <div class="modal-overlay" @click.self="emit('close')">
    <div class="modal">
      <h3 class="modal-title">{{ t('score.title', { title }) }}</h3>
      <label class="modal-label">{{ t('score.label') }}</label>
      <input v-model.number="scoreInput" type="number" min="1" max="10" class="modal-input" @keyup.enter="save" />
      <div class="modal-actions">
        <button class="modal-cancel" @click="emit('close')">{{ t('common.cancel') }}</button>
        <button class="modal-save" :disabled="!isValid" @click="save">{{ t('common.save') }}</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/Modal.css';
</style>