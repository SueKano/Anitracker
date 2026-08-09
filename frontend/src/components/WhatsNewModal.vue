<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Star, RotateCcw, Sparkles, History, ArrowDownUp, Download } from 'lucide-vue-next'
import { WHATS_NEW_VERSION } from '../composables/useWhatsNew'

const emit = defineEmits<{ close: [] }>()

const { t } = useI18n()

const features = [
  { key: 'import', icon: Download },
  { key: 'score', icon: Star },
  { key: 'rewatch', icon: RotateCcw },
  { key: 'recap', icon: Sparkles },
  { key: 'activity', icon: History },
  { key: 'sort', icon: ArrowDownUp },
]
</script>

<template>
  <div class="modal-overlay" @click.self="emit('close')">
    <div class="modal">
      <div class="whats-new-head">
        <h3 class="modal-title">{{ t('whatsNew.title') }}</h3>
        <span class="whats-new-version">v{{ WHATS_NEW_VERSION }}</span>
      </div>
      <ul class="whats-new-list">
        <li v-for="feature in features" :key="feature.key" class="whats-new-item">
          <component :is="feature.icon" class="whats-new-icon" :stroke-width="1.8" />
          <div class="whats-new-text">
            <span class="whats-new-name">{{ t(`whatsNew.${feature.key}.name`) }}</span>
            <span class="whats-new-desc">{{ t(`whatsNew.${feature.key}.desc`) }}</span>
          </div>
        </li>
      </ul>
      <button class="modal-save whats-new-close" @click="emit('close')">{{ t('whatsNew.gotIt') }}</button>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/WhatsNewModal.css';
</style>