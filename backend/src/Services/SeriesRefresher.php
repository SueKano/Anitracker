<?php

namespace App\Services;

use App\Entity\Series;
use App\Enum\SeriesStatus;
use App\Exception\AnilistUnavailableException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SeriesRefresher
{
    public const string NOT_YET_RELEASED_TTL = '-5 days';

    private const string QUERY = 'query($id:Int){
        Media(id:$id,type:ANIME){
            status
            episodes
            nextAiringEpisode{episode airingAt}
        }
    }';

    public function __construct(#[Target('anilist.client')] private readonly HttpClientInterface $httpClient)
    {
    }

    public function refresh(Series $series): void
    {
        try {
            $response = $this->httpClient->request('POST', '', [
                'json' => [
                    'query' => self::QUERY,
                    'variables' => ['id' => $series->getAnilistId()],
                ],
            ]);
            $data = $response->toArray()['data']['Media'] ?? null;
        } catch (HttpExceptionInterface $e) {
            throw new AnilistUnavailableException('No se pudo consultar AniList', 0, $e);
        }

        if ($data === null) {
            throw new AnilistUnavailableException('Respuesta inesperada de AniList');
        }

        $series->setAiringStatus($data['status']);

        if ($data['episodes'] > 0) {
            $series->setTotalEpisodes($data['episodes']);
        }

        $next = $data['nextAiringEpisode'] ?? null;
        $series->setLastRefreshedAt(new \DateTime());

        if ($next === null) {
            $series->setNextAiringAt(null);
            if ($data['status'] === SeriesStatus::FINISHED->value) {
                $series->setCurrentAiringEpisode($series->getTotalEpisodes());
            } elseif ($data['status'] === SeriesStatus::NOT_YET_RELEASED->value) {
                $series->setCurrentAiringEpisode(0);
            }
            $series->setAiringDay(null);
            return;
        }

        $series->setCurrentAiringEpisode(max(0, $next['episode'] - 1));
        $series->setNextAiringAt(new \DateTime()->setTimestamp($next['airingAt']));

        if ($data['status'] === SeriesStatus::RELEASING->value) {
            $day = new \DateTimeImmutable('@' . $next['airingAt'])->setTimezone(new \DateTimeZone('Europe/Madrid'))
                ->format('l');
            $series->setAiringDay(strtoupper($day));
        }
    }

    public function refreshIfReleasingDue(Series $series): bool
    {
        if ($series->getAiringStatus() !== SeriesStatus::RELEASING->value) {
            return false;
        }
        if ($series->getNextAiringAt() !== null && $series->getNextAiringAt() > new \DateTime()) {
            return false;
        }

        $this->refresh($series);
        return true;
    }
}
