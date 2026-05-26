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
        $countAddedWorks = $this->userSeriesRepository->countAddedByUserAndYear($user, $year);
        $totalEpisodesCompletedYear = $this->episodeWatchRepository->getCompletedEpisodesByUserAndYear($user, $year);
        $mostWatchedGenres = $this->getMostWatchedGenresByUser($seriesCompleted);
        $firstAndLastWatchedPlay = $this->getFirstAndLastCompletedPlayByUser($seriesCompleted);
        $worksWatchedBySeason = $this->getMostWatchedSeriesBySeason($seriesCompleted);
        $laziestSeries = $this->getLaziestWatchOfSeries($seriesCompleted);

        $seriesIds = array_map(fn($userSeries) => $userSeries->getSeries()->getId(), $seriesCompleted);
        $firstWatchMap = $this->episodeWatchRepository->findFirstWatchPerSeries($user, $seriesIds);
        $fastestSeries = $this->getFastestWatchOfSeries($seriesCompleted, $firstWatchMap);

        return [
            'year'             => $year,
            'worksCompleted'   => [
                'total'    => array_sum($totalWorksCompleted),
                'formats'  => $totalWorksCompleted,
            ],
            'worksAdded'        => $countAddedWorks,
            'episodesWatched'   => $totalEpisodesCompletedYear,
            'topGenres'         => array_slice($mostWatchedGenres, 0, 5, true),
            'topSeason'         => $worksWatchedBySeason['season'],
            'topSeriesSeason'   => $worksWatchedBySeason['seriesSeason'],
            'totalSeriesSeason' => $worksWatchedBySeason['totalSeriesSeason'],
            'firstWatched'      => $firstAndLastWatchedPlay['firstSeries'],
            'lastWatched'       => $firstAndLastWatchedPlay['lastSeries'],
            'slowestSeries'     => $laziestSeries,
            'fastestSeries'     => $fastestSeries,
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
        $seriesGenres = array_map(fn($us) => $us->getSeries()->getGenres(), $userSeriesData);
        $allGenres = array_merge(... $seriesGenres);
        $countGenres = array_count_values($allGenres);
        arsort($countGenres);

        return $countGenres;
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
        $seriesSeason = array_slice($totalSeriesSeason, 0, 3);

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
}
