<script setup lang="ts">
import { ref, computed } from 'vue'
import { Calendar } from 'lucide-vue-next'
import { translateDay } from '../utils/translateLabels.ts'
import { episodesBehind } from '../utils/animeStats'
import type { Anime } from '../types/Anime'

const props = defineProps<{ animeList: Anime[] }>()
const emit = defineEmits<{ select: [id: number] }>()

const DAYS = [
  { abbr: 'L', key: 'MONDAY' },
  { abbr: 'M', key: 'TUESDAY' },
  { abbr: 'X', key: 'WEDNESDAY' },
  { abbr: 'J', key: 'THURSDAY' },
  { abbr: 'V', key: 'FRIDAY' },
  { abbr: 'S', key: 'SATURDAY' },
  { abbr: 'D', key: 'SUNDAY' },
]

function jsToIdx(weekday: number) { return weekday === 0 ? 6 : weekday - 1 }

const now = new Date()
const todayIdx = jsToIdx(now.getDay())

const monday = new Date(now)
monday.setDate(now.getDate() - todayIdx)

const weekDates = Array.from({ length: 7 }, (_, i) => {
  const date = new Date(monday)
  date.setDate(monday.getDate() + i)
  return date.getDate()
})

const selected = ref(todayIdx)
const selectedDayKey = computed(() => DAYS[selected.value].key)

const animeByDay = computed(() => {
  const map = new Map<string, Anime[]>()
  for (const anime of props.animeList) {
    if (!anime.airing || !anime.dayOfWeek) continue
    const key = anime.dayOfWeek.toUpperCase()
    const list = map.get(key) ?? []
    list.push(anime)
    map.set(key, list)
  }
  return map
})

const daysView = computed(() => DAYS.map((day, i) => ({
  abbr: day.abbr,
  key: day.key,
  date: weekDates[i],
  hasAnime: animeByDay.value.has(day.key),
})))

const dayAnime = computed(() =>
  (animeByDay.value.get(selectedDayKey.value) ?? []).map(anime => ({ ...anime, behind: episodesBehind(anime) }))
)
</script>

<template>
  <div class="cal">
    <h1 class="page-title">Calendario</h1>
    <div class="strip">
      <button v-for="(day, i) in daysView" :key="day.key" class="day-btn" :class="{ active: selected === i, today: todayIdx === i }" @click="selected = i">
        <span class="day-name">{{ day.abbr }}</span>
        <span class="day-num">{{ day.date }}</span>
        <span v-if="day.hasAnime" class="day-dot"/>
      </button>
    </div>
    <div class="section-head">
      <span class="section-label">{{ translateDay(selectedDayKey) }}</span>
      <span class="section-count">{{ dayAnime.length }} {{ dayAnime.length === 1 ? 'serie' : 'series' }}</span>
    </div>
    <div v-if="dayAnime.length > 0" class="grid">
      <div v-for="anime in dayAnime" :key="anime.id" class="tile" @click="emit('select', anime.id)">
        <div class="tile-cover">
          <img :src="anime.cover" :alt="anime.title" loading="lazy" />
          <div v-if="anime.behind > 0" class="behind-badge">
            {{ anime.behind }} atrás
          </div>
          <div v-else-if="anime.aired > 0" class="uptodate-badge">Al día</div>
        </div>
        <p class="tile-title" :title="anime.title">{{ anime.title }}</p>
      </div>
    </div>

    <div v-else class="empty">
      <Calendar :stroke-width="1.2" />
      <span>Sin emisión este día</span>
    </div>
  </div>
</template>

<style scoped>
@import '../styles/CalendarPage.css';
</style>