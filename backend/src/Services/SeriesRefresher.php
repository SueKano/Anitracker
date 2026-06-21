<?php

namespace App\Services;

use App\Entity\Series;
use App\Enum\SeriesStatus;
use App\Exception\AnilistUnavailableException;
use App\Exception\JikanUnavailableException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SeriesRefresher
{
    public const string NOT_YET_RELEASED_TTL = '-7 days';
    public const string ADULT_SERIES_TTL = '-7 days';
    public const string RELEASING_SERIES_TTL = '-6 hours';

    public function __construct(private readonly AnilistApiClient $anilistClient, #[Target('jikan.client')] private readonly HttpClientInterface $jikanClient)
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

    public function refreshFromMal(Series $series): void
    {
        if ($series->getIdMal() === null) {
            return;
        }

        try {
            $response = $this->jikanClient->request('GET', 'anime/'. $series->getIdMal());
            $data = $response->toArray()['data'] ?? null;
        } catch (HttpExceptionInterface $e) {
            throw new JikanUnavailableException('No se pudo consultar MyAnimeList', 0, $e);
        }

        if ($data === null) {
            throw new JikanUnavailableException('Respuesta inesperada de MyAnimeList');
        }

        $this->updateTotalEpisodes($series, $data['episodes'] ?? null);

        $status = match ($data['status'] ?? null){
            'Finished Airing' => SeriesStatus::FINISHED->value,
            'Currently Airing' => SeriesStatus::RELEASING->value,
            'Not yet aired' => SeriesStatus::NOT_YET_RELEASED->value,
            default => $series->getAiringStatus(),
        };
        $series->setAiringStatus($status);
        $series->setLastRefreshedAt(new \DateTime());
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

    private function updateTotalEpisodes(Series $series, ?int $episodes): void
    {
        if ($episodes > $series->getTotalEpisodes()) {
            $series->setTotalEpisodes($episodes);
        }
    }
}
