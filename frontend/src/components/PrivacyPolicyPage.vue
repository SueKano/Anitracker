<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const emit = defineEmits<{ back: [], contact: [] }>()
const { t } = useI18n()

const SECTION_NUMBERS = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13']

const sections = computed(() =>
  SECTION_NUMBERS.map(number => ({
    number,
    title: t(`privacy.sections.${number}.title`),
    body: t(`privacy.sections.${number}.body`),
    action: number === '13' ? ('contact' as const) : undefined,
  }))
)
</script>

<template>
  <div class="policy">
    <button class="policy-back" :aria-label="t('privacy.backAria')" @click="emit('back')">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 18l-6-6 6-6"/>
      </svg>
    </button>

    <article class="policy-article">
      <h1 class="policy-title">
        <span class="policy-title-line">{{ t('privacy.titleLine1') }}</span>
        <span class="policy-title-line policy-title-italic">{{ t('privacy.titleLine2') }}</span>
      </h1>
      <div class="policy-rule" aria-hidden="true">
        <span class="policy-rule-dot"/>
      </div>
      <section v-for="(section, index) in sections" :key="section.number" class="policy-section" :style="{ animationDelay: 80 + index * 60 + 'ms' }">
        <div class="policy-section-head">
          <span class="policy-num">{{ section.number }}</span>
          <h2 class="policy-section-title">{{ section.title }}</h2>
        </div>
        <p class="policy-body">{{ section.body }}</p>
        <button v-if="section.action === 'contact'" type="button" class="policy-link" @click="emit('contact')">
          {{ t('privacy.goToContact') }}
        </button>
      </section>
      <p class="policy-updated">{{ t('privacy.lastUpdated', { date: t('privacy.lastUpdatedDate') }) }}</p>
    </article>
  </div>
</template>

<style scoped>
@import '../styles/PrivacyPolicyPage.css';
</style>