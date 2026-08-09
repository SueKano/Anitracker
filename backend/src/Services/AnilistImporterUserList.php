<?php

namespace App\Services;

use App\Entity\Series;
use App\Entity\User;
use App\Entity\UserSeries;
use App\Enum\ImportListSeriesStatus;
use App\Enum\SeriesStatus;
use App\Repository\SeriesRepository;
use App\Repository\UserSeriesRepository;
use Doctrine\ORM\EntityManagerInterface;

class AnilistImporterUserList
{
    public function __construct(private readonly AnilistApiClient $anilistClient, private readonly EntityManagerInterface $entityManager,
                                private readonly SeriesRepository $seriesRepository, private readonly UserSeriesRepository $userSeriesRepository)
    {
    }

    public function importFromAnilist(User $user, string $userName): array
    {
        $trackedIds = array_flip($this->userSeriesRepository->findTrackedAnilistIdsByUser($user));
        $isFirstImport = !$this->userSeriesRepository->hasImportedSeries($user);
        $processed = 0;
        $alreadyTracked = 0;
        $createdAnilistIds = [];
        $notImported = [];
        foreach ($this->anilistClient->fetchUserListChunks($userName) as $entries) {
            $processed += count($entries);
            $seriesByAnilistId = $this->findExistingSeries($entries);

            foreach ($entries as $entry) {
                $anilistId = $entry['media']['id'];
                $series = $seriesByAnilistId[$anilistId] ?? Series::createSeriesFromAnilistData($entry['media']);
                $this->fillTagsAndStudios($series, $entry['media']);
                if ($series->getAiringStatus() === SeriesStatus::NOT_YET_RELEASED->value) {
                    $notImported[] = $series->getRomajiName();
                    continue;
                }
                if (isset($trackedIds[$anilistId])) {
                    $alreadyTracked++;
                    continue;
                }
                if (!isset($seriesByAnilistId[$anilistId])) {
                    $this->entityManager->persist($series);
                    $seriesByAnilistId[$anilistId] = $series;
                }
                $this->entityManager->persist($this->buildUserSeries($user, $series, $entry));
                $trackedIds[$anilistId] = true;
                $createdAnilistIds[] = $anilistId;
            }
            $this->entityManager->flush();
        }
        $this->markFavourites($user, $userName, $createdAnilistIds, $isFirstImport);

        return ['processed' => $processed, 'created' => count($createdAnilistIds), 'existed' => $alreadyTracked, 'withoutImport' => $notImported];
    }

    private function markFavourites(User $user, string $userName, array $createdAnilistIds, bool $isFirstImport): void
    {
        if (!$isFirstImport && !$createdAnilistIds) {
            return;
        }

        $favouriteIds = $this->anilistClient->fetchUserFavouriteAnilistIds($userName);
        $idsToMark = $isFirstImport ? array_keys($favouriteIds) :
            array_values(array_filter($createdAnilistIds, fn(int $anilistId) => isset($favouriteIds[$anilistId])));

        $this->userSeriesRepository->markFavouritesByAnilistIds($user, $idsToMark);
    }

    private function fillTagsAndStudios(Series $series, array $media): void
    {
        if ($series->getTags()) {
            return;
        }

        $series->setTags($media['tags'] ?? []);
        $series->setStudios(Series::resolveMainStudios($media));
    }

    private function findExistingSeries(array $entries): array
    {
        $anilistIds = array_column(array_column($entries, 'media'), 'id');
        $seriesByAnilistId = [];
        foreach ($this->seriesRepository->findByAnilistId($anilistIds) as $series) {
            $seriesByAnilistId[$series->getAnilistId()] = $series;
        }

        return $seriesByAnilistId;
    }
    private function buildUserSeries(User $user, Series $series, array $entry): UserSeries
    {
        $progress = $entry['progress'];
        $total = max($series->getTotalEpisodes(), $progress);
        [$isCompleted, $isRewatching, $lastEpisode, $countEpisodes] = match (ImportListSeriesStatus::from($entry['status'])) {
            ImportListSeriesStatus::COMPLETED => [true, false, $total, $total],
            ImportListSeriesStatus::CURRENT => [false, false, $progress, $progress],
            ImportListSeriesStatus::REPEATING => [true, true, $progress, $total + $progress],
        };
        return new UserSeries()->setUser($user)
            ->setSeries($series)
            ->setIsCompleted($isCompleted)
            ->setIsRewatching($isRewatching)
            ->setLastEpisodeWatchedCount($lastEpisode)
            ->setCountEpisodesCompleted($countEpisodes)
            ->setScore((int) $entry['score'])
            ->setImportedAt(new \DateTime())
            ->setCompletedAt($this->normalizeEndDate($entry['completedAt']));
    }
    private function normalizeEndDate(array $date): ?\DateTime
    {
        if (!$date['year']){
            return null;
        } else {
            return new \DateTime(sprintf('%d-%d-%d', $date['year'], $date['month'] ?? 1, $date['day'] ?? 1));
        }
    }
}
