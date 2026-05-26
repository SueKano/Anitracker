const SEASON_LABELS: Record<string, string> = {
  WINTER: 'Invierno',
  SPRING: 'Primavera',
  SUMMER: 'Verano',
  FALL: 'Otoño',
}

const FORMAT_LABELS: Record<string, string> = {
  TV: 'TV',
  TV_SHORT: 'TV corto',
  MOVIE: 'Película',
  SPECIAL: 'Especial',
  OVA: 'OVA',
  ONA: 'ONA',
  MUSIC: 'Música',
}

const SOURCE_LABELS: Record<string, string> = {
  ORIGINAL: 'Original',
  MANGA: 'Manga',
  LIGHT_NOVEL: 'Novela ligera',
  VISUAL_NOVEL: 'Novela visual',
  VIDEO_GAME: 'Videojuego',
  NOVEL: 'Novela',
  ANIME: 'Anime',
  WEB_NOVEL: 'Novela web',
}

const DAY_LABELS: Record<string, string> = {
  MONDAY: 'Lunes',
  TUESDAY: 'Martes',
  WEDNESDAY: 'Miércoles',
  THURSDAY: 'Jueves',
  FRIDAY: 'Viernes',
  SATURDAY: 'Sábado',
  SUNDAY: 'Domingo',
}

const GENRES_LABELS: Record<string, string> = {
  Music: 'Música',
  Comedy: 'Comedia',
  Adventure: 'Aventura',
  Action: 'Acción',
  Fantasy: 'Fantasía',
  Mystery: 'Misterio',
  Psychological: 'Psicológico',
  Sports: 'Deportes',
  'Sci-Fi': 'Ciencia ficción',
  Thriller: 'Suspense',
  'Mahou Shoujo': 'Chicas mágicas',
}

function translate(map: Record<string, string>, value: string | null | undefined): string {
  if (!value) return ''
  return map[value.toUpperCase()] ?? value
}

export const translateSeason = (value: string | null | undefined) => translate(SEASON_LABELS, value)
export const translateFormat = (value: string | null | undefined) => translate(FORMAT_LABELS, value)
export const translateSource = (value: string | null | undefined) => translate(SOURCE_LABELS, value)
export const translateDay    = (value: string | null | undefined) => translate(DAY_LABELS, value)
export const translateGenres    = (value: string | null | undefined) => {
  if (!value) return ''
  return value.split(',').map(genre => genre.trim())
      .map(genre => GENRES_LABELS[genre] ?? genre).join(', ')
}
export const translateGenre = (value: string | null | undefined): string => {
  if (!value) return ''
  return GENRES_LABELS[value] ?? value
}