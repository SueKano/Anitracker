import type { Anime } from '../types/Anime'

export function progressPercent(anime: Anime) {
  return anime.total ? Math.round((anime.progress / anime.total) * 100) : 0
}

export function availableEpisodes(anime: Anime) {
  return anime.airing ? anime.aired : anime.total
}

export function episodesBehind(anime: Anime) {
  return availableEpisodes(anime) - anime.progress
}