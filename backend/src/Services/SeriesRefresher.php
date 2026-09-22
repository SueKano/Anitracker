<?php

namespace App\Services;

use App\Entity\Series;
use App\Enum\SeriesStatus;
use App\Exception\AnilistUnavailableException;

class SeriesRefresher
{
    public const string NOT_YET_RELEASED_TTL = '-7 days';
    public const string RELEASING_SERIES_TTL = '-6 hours';

    public function __construct(private readonly AnilistApiClient $anilistClient)
    {
    }

    public function refreshFromAnilist(Series $series): void
    {
        $data = $this->anilistClient->fetchAnilistDataById($series->getAnilistId());

        if ($data === null) {
            throw new AnilistUnavailableException('Respuesta inesperada de AniList');
        }
        $series->mapAnilistData($data);
        $series->setLastRefreshedAt(new \DateTime());
    }

    public function refreshManyFromAnilist(array $series): array
    {
        $anilistIds = array_map(static fn (Series $serie) => $serie->getAnilistId(), $series);
        $mediaByAnilistId = $this->anilistClient->fetchMediaByIds($anilistIds);

        $refreshed = [];
        foreach ($series as $serie) {
            if (!isset($mediaByAnilistId[$serie->getAnilistId()])) {
                continue;
            }
            $serie->mapAnilistData($mediaByAnilistId[$serie->getAnilistId()]);
            $serie->setLastRefreshedAt(new \DateTime());
            $refreshed[] = $serie;
        }

        return $refreshed;
    }

    public function refreshIfReleasingDue(Series $series): bool
    {
        if ($series->getAiringStatus() !== SeriesStatus::RELEASING->value) {
            return false;
        }
        if ($series->getNextAiringAt() !== null && $series->getNextAiringAt() > new \DateTime()) {
            return false;
        }

        $this->refreshFromAnilist($series);
        return true;
    }
}
