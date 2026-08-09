<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { X, ChevronLeft, ChevronRight, Sparkles, Download, LoaderCircle } from 'lucide-vue-next'
import type { Recap } from '../types/Recap'
import { formatDuration, getWatchTimeParts } from '../utils/formatDuration'
import { translateSeason, translateGenre } from '../utils/translateLabels'
import { useToast } from '../composables/useToast'

type CaptureOptions = Parameters<typeof import('html-to-image').toPng>[1]

const props = defineProps<{ recap: Recap }>()
const emit = defineEmits<{ close: [] }>()
const { t } = useI18n()
const toast = useToast()

const IMAGE_WIDTH = 1080

const recapTrack = ref<HTMLElement | null>(null)
const isCapturing = ref(false)

async function downloadSlide() {
  const slide = recapTrack.value?.children[currentSlide.value] as HTMLElement | undefined
  if (!slide) return

  isCapturing.value = true
  const options: CaptureOptions = {
    backgroundColor: getComputedStyle(document.documentElement).getPropertyValue('--bg').trim(),
    pixelRatio: IMAGE_WIDTH / slide.offsetWidth,
    cacheBust: true,
  }

  try {
    const { toPng } = await import('html-to-image')
    const dataUrl = await toPng(slide, options)
    const link = document.createElement('a')
    link.download = `anitracker-${props.recap.year}-${currentSlide.value}.png`
    link.href = dataUrl
    link.click()
  } catch {
    toast.error(t('toast.recapDownloadError'))
  } finally {
    isCapturing.value = false
  }
}

const summary = computed(() => {
  const { scoreSeries, topSeriesSeason } = props.recap
  const source = scoreSeries ? scoreSeries.top : topSeriesSeason.slice(0, 3)

  return {
    label: scoreSeries ? t('recap.scoresTitle') : t('recap.someSeries'),
    series: source.map(userSeries => ({
      name: userSeries.series.romajiName,
      portrait: userSeries.series.portraitUrl,
      score: scoreSeries ? userSeries.score : null,
    })),
  }
})

const BASE_SLIDES = 6
const SWIPE_THRESHOLD = 50
const SCORE_SLIDE_INDEX = 4

const TOTAL_SLIDES = computed(() => BASE_SLIDES + (props.recap.scoreSeries ? 1 : 0))

const currentSlide = ref(0)
const touchStartX = ref(0)
const maxSlideSeen = ref(0)

const isIntro = computed(() => currentSlide.value === 0)

const genreShare = (seriesCount: number) => (seriesCount / props.recap.worksCompleted.total) * 100 + '%'
const showsEverySeason = computed(() => props.recap.topSeriesSeason.length === props.recap.totalSeriesSeason)

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

watch(currentSlide, (number) => {
  if (number > maxSlideSeen.value) maxSlideSeen.value = number
})

function nextSlide() {
  if (currentSlide.value < TOTAL_SLIDES.value - 1) currentSlide.value++
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
      <div class="recap-actions">
        <button class="recap-icon" :aria-label="t('recap.downloadAria')" :disabled="isCapturing" @click="downloadSlide">
          <LoaderCircle v-if="isCapturing" class="recap-spin" :stroke-width="2" />
          <Download v-else :stroke-width="2" />
        </button>
        <button class="recap-icon" :aria-label="t('recap.closeAria')" @click="emit('close')">
          <X :stroke-width="2" />
        </button>
      </div>
    </header>

    <div ref="recapTrack" class="recap-track" :style="{ transform: `translateX(-${currentSlide * 100}%)` }" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
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
        <span class="slide-caption">{{ showsEverySeason ? t('recap.allSeries') : t('recap.someSeries') }}</span>
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

      <section v-if="recap.scoreSeries" class="recap-slide">
        <template v-if="maxSlideSeen >= SCORE_SLIDE_INDEX">
          <header class="slide-heading">
            <span class="slide-label">{{ t('recap.scoresLabel') }}</span>
            <h2 class="slide-title">{{ t('recap.scoresTitle') }}</h2>
          </header>

          <div class="podium">
            <figure v-for="(userSeries, rank) in recap.scoreSeries.top" :key="userSeries.series.romajiName" class="podium-step">
              <div class="podium-frame">
                <img :src="userSeries.series.portraitUrl" :alt="userSeries.series.romajiName" loading="lazy"/>
                <span class="podium-score" :class="{ 'podium-score--first': rank === 0 }">{{ userSeries.score }}</span>
              </div>
              <figcaption class="podium-name">{{ userSeries.series.romajiName }}</figcaption>
            </figure>
          </div>

          <div class="score-footer">
            <div class="score-cell">
              <p class="score-cell-label">{{ t('recap.averageLabel') }}</p>
              <p class="score-cell-value">{{ recap.scoreSeries.average.toFixed(1) }}</p>
            </div>
            <div class="score-cell">
              <p class="score-cell-label">{{ t('recap.disappointmentLabel') }}</p>
              <p class="score-cell-value score-cell-value--low">{{ recap.scoreSeries.disappointment.score }}</p>
              <p class="score-cell-name">{{ recap.scoreSeries.disappointment.series.romajiName }}</p>
            </div>
          </div>
        </template>
      </section>

      <section class="recap-slide">
        <header class="slide-heading">
          <span class="slide-label">{{ t('recap.preferences') }}</span>
          <h2 class="slide-title">{{ t('recap.favGenres') }}</h2>
        </header>

        <template v-if="maxSlideSeen >= TOTAL_SLIDES - 2">
          <div class="genre-cards">
            <article v-for="(genre, rank) in recap.topGenres" :key="genre.name" class="genre-card" :style="{ animationDelay: rank * 0.12 + 's' }">
              <div class="genre-backdrop" aria-hidden="true">
                <img v-for="portrait in genre.portraits" :key="portrait" :src="portrait" alt="" loading="lazy"/>
              </div>
              <span class="genre-rank" aria-hidden="true">{{ rank + 1 }}</span>
              <div class="genre-body">
                <h3 class="genre-name">{{ translateGenre(genre.name) }}</h3>
                <p class="genre-stats">
                  <span class="genre-stat">
                    <span class="genre-stat-num">{{ genre.seriesCount }}</span>{{ t('recap.genreSeries') }}
                  </span>
                  <span class="genre-stat">
                    <span class="genre-stat-num">{{ genre.episodes }}</span>{{ t('recap.genreEpisodes') }}
                  </span>
                  <span class="genre-stat">
                    <template v-for="(part, index) in getWatchTimeParts(genre.episodes)" :key="index">
                      <span class="genre-stat-num">{{ part.value }}</span>{{ part.unit }}
                    </template>
                  </span>
                </p>
              </div>
              <div class="genre-share" :style="{ width: genreShare(genre.seriesCount), animationDelay: rank * 0.12 + 0.25 + 's' }"/>
            </article>
          </div>
        </template>
      </section>

      <section class="recap-slide summary-slide">
        <template v-if="maxSlideSeen >= TOTAL_SLIDES - 1">
          <header class="summary-head">
            <p class="summary-kicker">{{ t('recap.cardTitle') }}</p>
            <p class="summary-year">{{ recap.year }}</p>
          </header>

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

          <div class="summary-block">
            <p class="summary-block-label">{{ summary.label }}</p>
            <div class="summary-posters">
              <figure v-for="item in summary.series" :key="item.name">
                <div class="summary-frame">
                  <img :src="item.portrait" :alt="item.name" loading="lazy"/>
                  <span v-if="item.score !== null" class="summary-score">{{ item.score }}</span>
                </div>
                <figcaption class="summary-name">{{ item.name }}</figcaption>
              </figure>
            </div>
          </div>

          <div class="summary-facts">
            <div class="summary-fact">
              <p class="summary-fact-label">{{ t('recap.factGenre') }}</p>
              <p class="summary-fact-value">{{ translateGenre(recap.topGenres[0].name) }}</p>
            </div>
            <div class="summary-fact">
              <p class="summary-fact-label">{{ t('recap.factSeason') }}</p>
              <p class="summary-fact-value">{{ translateSeason(recap.topSeason) }}</p>
            </div>
          </div>

          <p class="summary-brand">myanitracker.com</p>
        </template>
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