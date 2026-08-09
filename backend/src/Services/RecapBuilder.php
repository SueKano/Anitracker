<?php /** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace App\Services;

use App\Entity\User;
use App\Repository\UserEpisodeWatchRepository;
use App\Repository\UserSeriesRepository;

readonly class RecapBuilder
{
    public function __construct(private UserSeriesRepository $userSeriesRepository, private UserEpisodeWatchRepository $episodeWatchRepository)
    {
    }

    public function buildRecap(User $user, int $year): ?array
    {
        $seriesCompleted = $this->userSeriesRepository->findCompletedSeriesByUserAndYear($user, $year);
        if (empty($seriesCompleted) || count($seriesCompleted) < 8) {
            return null;
        }
        $totalWorksCompleted = $this->getCompletedWorksByFormat($seriesCompleted);
        $totalEpisodesCompletedYear = $this->episodeWatchRepository->getCompletedEpisodesByUserAndYear($user, $year);
        $mostWatchedGenres = $this->getMostWatchedGenresByUser($seriesCompleted);
        $firstAndLastWatchedPlay = $this->getFirstAndLastCompletedPlayByUser($seriesCompleted);
        $worksWatchedBySeason = $this->getMostWatchedSeriesBySeason($seriesCompleted);
        $laziestSeries = $this->getLaziestWatchOfSeries($seriesCompleted);

        $seriesIds = array_map(fn($userSeries) => $userSeries->getSeries()->getId(), $seriesCompleted);
        $firstWatchMap = $this->episodeWatchRepository->findFirstWatchPerSeries($user, $seriesIds);
        $fastestSeries = $this->getFastestWatchOfSeries($seriesCompleted, $firstWatchMap);
        $scoreSeries = $this->getScoresOfSeries($seriesCompleted);

        return [
            'year' => $year,
            'worksCompleted' => [
                'total' => array_sum($totalWorksCompleted),
                'formats' => $totalWorksCompleted,
            ],
            'episodesWatched' => $totalEpisodesCompletedYear,
            'topGenres' => $mostWatchedGenres,
            'topSeason' => $worksWatchedBySeason['season'],
            'topSeriesSeason' => $worksWatchedBySeason['seriesSeason'],
            'totalSeriesSeason' => $worksWatchedBySeason['totalSeriesSeason'],
            'firstWatched' => $firstAndLastWatchedPlay['firstSeries'],
            'lastWatched' => $firstAndLastWatchedPlay['lastSeries'],
            'slowestSeries' => $laziestSeries,
            'fastestSeries' => $fastestSeries,
            'scoreSeries' => $scoreSeries,
        ];
    }

    private function getCompletedWorksByFormat(array $userSeriesData): array
    {
        $completedSeriesCountByFormat = array_count_values(
            array_map(fn($us) => $us->getSeries()->getFormat(), $userSeriesData)
        );
        arsort($completedSeriesCountByFormat);

        return $completedSeriesCountByFormat;
    }

    private function getMostWatchedGenresByUser(array $userSeriesData): array
    {
        $seriesByGenre = [];
        foreach ($userSeriesData as $userSeries) {
            foreach ($userSeries->getSeries()->getGenres() as $genre) {
                $seriesByGenre[$genre][] = $userSeries;
            }
        }
        uasort($seriesByGenre, static fn(array $first, array $second) => count($second) <=> count($first));

        $topGenres = [];
        foreach (array_slice($seriesByGenre, 0, 3, true) as $genre => $genreSeries) {
            $topGenres[] = $this->buildGenreSummary($genre, $genreSeries);
        }

        return $topGenres;
    }

    private function buildGenreSummary(string $genre, array $genreSeries): array
    {
        $episodes = array_map(fn($userSeries) => $userSeries->getSeries()->getTotalEpisodes(), $genreSeries);
        usort($genreSeries, static fn($first, $second) => $second->getScore() <=> $first->getScore());
        $portraits = array_map(fn($userSeries) => $userSeries->getSeries()->getPortraitUrl(), array_slice($genreSeries, 0, 3));

        return ['name' => $genre, 'seriesCount' => count($genreSeries), 'episodes' => array_sum($episodes), 'portraits' => $portraits];
    }

    private function getFirstAndLastCompletedPlayByUser(array $userSeriesData): array
    {
        $firstCompletedSeries = reset($userSeriesData);
        $lastCompletedSeries = end($userSeriesData);

        return ['firstSeries' => $firstCompletedSeries, 'lastSeries' => $lastCompletedSeries];
    }

    private function getMostWatchedSeriesBySeason(array $userSeriesData): array
    {
        $mostWatchedSeriesBySeason = array_count_values(
            array_map(fn($us) => $us->getSeries()->getSeason(), $userSeriesData)
        );
        arsort($mostWatchedSeriesBySeason);
        $mostWatchedSeason = array_key_first($mostWatchedSeriesBySeason);
        $totalSeriesSeason = array_filter($userSeriesData, fn($us) => $us->getSeries()->getSeason() === $mostWatchedSeason);
        usort($totalSeriesSeason, static fn($first, $second) => $second->getScore() <=> $first->getScore());
        $seriesSeason = array_slice($totalSeriesSeason, 0, 6);

        return ['season' => $mostWatchedSeason, 'seriesSeason' => $seriesSeason, 'totalSeriesSeason' => count($totalSeriesSeason)];
    }

    private function getLaziestWatchOfSeries(array $userSeriesData): array
    {
        $noMoviesUserSeries = array_filter($userSeriesData, fn($us) => $us->getSeries()->getFormat() !== 'MOVIE');
        $slowestSeconds = 0;
        $laziestSeries = null;

        foreach ($noMoviesUserSeries as $userSeries) {
            $secondsDiff = $userSeries->getCompletedAt()->getTimestamp() - $userSeries->getCreatedAt()->getTimestamp();
            if ($secondsDiff > $slowestSeconds) {
                $slowestSeconds = $secondsDiff;
                $laziestSeries = $userSeries;
            }
        }
        return ['userSeries' => $laziestSeries, 'duration' => $slowestSeconds];
    }

    private function getFastestWatchOfSeries(array $userSeriesData, array $firstWatchMap): array
    {
        $onlyTvUserSeries = array_filter($userSeriesData, fn($us) => $us->getSeries()->getFormat() === 'TV');
        $fastestSeconds = PHP_INT_MAX;
        $fastestSeries = null;

        foreach ($onlyTvUserSeries as $userSeries) {
            $startedAt = $firstWatchMap[$userSeries->getSeries()->getId()];
            $secondsDiff = $userSeries->getCompletedAt()->getTimestamp() - $startedAt->getTimestamp();

            if ($secondsDiff < $fastestSeconds) {
                $fastestSeconds = $secondsDiff;
                $fastestSeries = $userSeries;
            }
        }

        return ['userSeries' => $fastestSeries, 'duration' => $fastestSeconds];
    }
    private function getScoresOfSeries(array $userSeriesData): ?array
    {
        $scoredSeries = array_filter($userSeriesData, fn($userSeries) => $userSeries->getScore() > 0);
        if (count($scoredSeries) < 4){
            return null;
        }
        usort($scoredSeries, fn($first, $second) => $second->getScore() <=> $first->getScore());
        $scores = array_map(fn($userSeries) => $userSeries->getScore(), $scoredSeries);

        return ['top' => array_slice($scoredSeries, 0, 3), 'average' => round(array_sum($scores) / count($scores), 1),
            'disappointment' => end($scoredSeries)];
    }
}
