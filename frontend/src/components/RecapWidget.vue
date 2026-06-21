<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { X, ChevronLeft, ChevronRight, Sparkles } from 'lucide-vue-next'
import type { Recap } from '../types/Recap'
import { formatDuration, getWatchTimeParts } from '../utils/formatDuration'
import { translateSeason, translateFormat, translateGenre } from '../utils/translateLabels'

const props = defineProps<{ recap: Recap }>()
const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()

const completionRatio = computed(() => {
  if (!props.recap.worksAdded) return null
  const ratio = (props.recap.worksCompleted.total / props.recap.worksAdded) * 100
  return Math.min(100, Math.round(ratio))
})

const TOTAL_SLIDES = 6
const SWIPE_THRESHOLD = 50

const currentSlide = ref(0)
const touchStartX = ref(0)
const maxSlideSeen = ref(0)

const isIntro = computed(() => currentSlide.value === 0)

const pairSlides = computed(() => {
  const { firstWatched, lastWatched, fastestSeries, slowestSeries } = props.recap
  return [
    {
      label: t('recap.firstLastLabel'),
      title: t('recap.firstLastTitle'),
      cards: [
        { tag: t('recap.tagFirst'), userSeries: firstWatched, duration: null as number | null },
        { tag: t('recap.tagLast'), userSeries: lastWatched, duration: null as number | null },
      ],
    },
    {
      label: t('recap.paceLabel'),
      title: t('recap.paceTitle'),
      cards: [
        { tag: t('recap.tagFastest'), userSeries: fastestSeries.userSeries, duration: fastestSeries.duration },
        { tag: t('recap.tagSlowest'), userSeries: slowestSeries.userSeries, duration: slowestSeries.duration },
      ],
    },
  ]
})

watch(currentSlide, (n) => {
  if (n > maxSlideSeen.value) maxSlideSeen.value = n
})

function nextSlide() {
  if (currentSlide.value < TOTAL_SLIDES - 1) currentSlide.value++
}

function prevSlide() {
  if (currentSlide.value > 0) currentSlide.value--
}

function onTouchStart(event: TouchEvent) {
  touchStartX.value = event.changedTouches[0].clientX
}

function onTouchEnd(event: TouchEvent) {
  const deltaX = event.changedTouches[0].clientX - touchStartX.value
  if (Math.abs(deltaX) < SWIPE_THRESHOLD) return
  if (deltaX < 0) nextSlide()
  else prevSlide()
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') emit('close')
  else if (event.key === 'ArrowRight') nextSlide()
  else if (event.key === 'ArrowLeft') prevSlide()
}

onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div class="recap-overlay">
    <header class="recap-header">
      <span class="recap-year">{{ t('recap.year', { year: recap.year }) }}</span>
      <button class="recap-close" :aria-label="t('recap.closeAria')" @click="emit('close')">
        <X :stroke-width="2" />
      </button>
    </header>

    <div class="recap-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
      <section class="recap-slide recap-intro" @click="nextSlide">
        <div class="intro-sparkles" aria-hidden="true">
          <Sparkles class="sparkle sparkle-1" :stroke-width="1.5" />
          <Sparkles class="sparkle sparkle-2" :stroke-width="1.5" />
          <Sparkles class="sparkle sparkle-3" :stroke-width="1.5" />
        </div>
        <p class="intro-tagline">{{ t('recap.introTagline') }}</p>
        <h1 class="intro-year">{{ recap.year }}</h1>
        <p class="intro-hint">{{ t('recap.introHint') }}</p>
      </section>

      <section class="recap-slide">
        <header class="slide-heading">
          <span class="slide-label">{{ t('recap.topSeasonLabel') }}</span>
          <h2 class="slide-title">{{ translateSeason(recap.topSeason) }}</h2>
          <span class="slide-label">{{ t('recap.seasonTotal', { count: recap.totalSeriesSeason }) }}</span>
        </header>
        <span class="slide-caption">{{ t('recap.someSeries') }}</span>
        <div class="season-posters" :class="`count-${recap.topSeriesSeason.length}`">
          <figure v-for="userSeries in recap.topSeriesSeason" :key="userSeries.series.romajiName">
            <img :src="userSeries.series.portraitUrl" :alt="userSeries.series.romajiName" loading="lazy"/>
            <figcaption>{{ userSeries.series.romajiName }}</figcaption>
          </figure>
        </div>
      </section>

      <section v-for="(slide, slideIdx) in pairSlides" :key="slideIdx" class="recap-slide">
        <template v-if="maxSlideSeen >= slideIdx + 1">
          <header class="slide-heading">
            <span class="slide-label">{{ slide.label }}</span>
            <h2 class="slide-title">{{ slide.title }}</h2>
          </header>
          <div class="pair">
            <template v-for="card in slide.cards" :key="card.tag">
              <article v-if="card.userSeries" class="pair-card">
                <span class="pair-tag">{{ card.tag }}</span>
                <img :src="card.userSeries.series.portraitUrl" :alt="card.userSeries.series.romajiName" loading="lazy"/>
                <p class="pair-name">{{ card.userSeries.series.romajiName }}</p>
                <p v-if="card.duration != null" class="pair-duration">{{ formatDuration(card.duration) }}</p>
              </article>
            </template>
          </div>
        </template>
      </section>

      <section class="recap-slide">
        <header class="slide-heading">
          <span class="slide-label">{{ t('recap.yourYear') }}</span>
          <h2 class="slide-title">{{ t('recap.inNumbers') }}</h2>
        </header>

        <div class="cifras">
          <div class="cifras-pair">
            <div class="cifras-cell">
              <p class="cifras-num">{{ recap.worksCompleted.total }}</p>
              <p class="cifras-label">{{ t('recap.seriesCompleted') }}</p>
            </div>
            <div class="cifras-cell">
              <p class="cifras-time">
                <span v-for="(part, index) in getWatchTimeParts(recap.episodesWatched)" :key="index" class="cifras-time-part">
                  <span class="cifras-time-num">{{ part.value }}</span>
                  <span class="cifras-time-unit">{{ part.unit }}</span>
                </span>
              </p>
              <p class="cifras-label">{{ t('recap.ofAnime') }}</p>
            </div>
          </div>

          <div v-if="completionRatio !== null" class="cifras-ratio">
            <i18n-t keypath="recap.ratio" tag="p" class="cifras-ratio-text">
              <template #added><span class="cifras-ratio-num">{{ recap.worksAdded }}</span></template>
              <template #completed><span class="cifras-ratio-num">{{ recap.worksCompleted.total }}</span></template>
              <template #percent><span class="cifras-ratio-num">{{ completionRatio }}%</span></template>
            </i18n-t>
          </div>
        </div>
      </section>

      <section class="recap-slide">
        <header class="slide-heading">
          <h2 class="slide-title">{{ t('recap.preferences') }}</h2>
        </header>

        <div class="dist-block">
          <h3 class="dist-title">{{ t('recap.byFormat') }}</h3>
          <div class="dist-rows">
            <div v-for="(count, format) in recap.worksCompleted.formats" :key="format" class="dist-row">
              <span class="dist-name">{{ translateFormat(format) }}</span>
              <span class="dist-count">{{ count }}</span>
            </div>
          </div>
        </div>

        <div class="dist-block">
          <h3 class="dist-title">{{ t('recap.favGenres') }}</h3>
          <div class="dist-rows">
            <div v-for="(count, genre) in recap.topGenres" :key="genre" class="dist-row">
              <span class="dist-name">{{ translateGenre(genre) }}</span>
              <span class="dist-count">{{ count }}</span>
            </div>
          </div>
        </div>
      </section>
    </div>

    <button v-if="currentSlide > 0" class="nav-arrow nav-arrow-left" :aria-label="t('recap.prevAria')" @click="prevSlide">
      <ChevronLeft :stroke-width="2" />
    </button>
    <button v-if="!isIntro && currentSlide < TOTAL_SLIDES - 1" class="nav-arrow nav-arrow-right" :aria-label="t('recap.nextAria')" @click="nextSlide">
      <ChevronRight :stroke-width="2" />
    </button>

    <div v-if="!isIntro" class="recap-dots">
      <button v-for="i in TOTAL_SLIDES - 1" :key="i" class="recap-dot" :class="{ active: currentSlide === i }" :aria-label="t('recap.slideAria', { n: i })"
              @click="currentSlide = i"/>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/RecapWidget.css';
</style>