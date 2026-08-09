<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronLeft, Download } from 'lucide-vue-next'
import { useAnilistImport } from '../composables/useAnilistImport'

const emit = defineEmits<{ back: [], imported: [] }>()
const { t } = useI18n()
const { importing, result, importFromAnilist } = useAnilistImport()

const userName = ref('')
const canSubmit = computed(() => userName.value.trim().length > 0 && !importing.value)

async function submit() {
  if (!canSubmit.value) return
  if (await importFromAnilist(userName.value.trim())) emit('imported')
}
</script>

<template>
  <div class="import">
    <button class="import-back" :aria-label="t('common.back')" @click="emit('back')">
      <ChevronLeft :stroke-width="2.2" />
    </button>

    <h1 class="import-title">{{ t('import.title') }}</h1>
    <p class="import-lede">{{ t('import.lede') }}</p>

    <form class="import-form" @submit.prevent="submit">
      <label class="import-label" for="anilist-user">{{ t('import.userLabel') }}</label>
      <input id="anilist-user" v-model="userName" class="import-input" type="text" autocomplete="off"
             autocapitalize="none" spellcheck="false" :placeholder="t('import.userPlaceholder')" :disabled="importing" />
      <button class="import-submit" type="submit" :disabled="!canSubmit">
        <Download :stroke-width="1.8" />
        {{ importing ? t('import.importing') : t('import.submit') }}
      </button>
    </form>

    <p v-if="importing" class="import-wait">{{ t('import.wait') }}</p>

    <section v-if="result" class="import-result">
      <p class="import-result-head">{{ t('import.resultTitle') }}</p>
      <ul class="import-stats">
        <li><strong>{{ result.created }}</strong> {{ t('import.statCreated') }}</li>
        <li><strong>{{ result.existed }}</strong> {{ t('import.statExisted') }}</li>
        <li><strong>{{ result.processed }}</strong> {{ t('import.statProcessed') }}</li>
      </ul>

      <div v-if="result.withoutImport.length > 0" class="import-skipped">
        <p class="import-skipped-head">{{ t('import.skippedTitle', { count: result.withoutImport.length }) }}</p>
        <ul class="import-skipped-list">
          <li v-for="title in result.withoutImport" :key="title">{{ title }}</li>
        </ul>
      </div>
    </section>
  </div>
</template>

<style scoped>
@import '../styles/ImportListPage.css';
</style>