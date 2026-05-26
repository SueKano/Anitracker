<?php

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Entity\UserEpisodeWatch;
use App\Entity\UserSeries;
use App\Enum\SeriesStatus;
use App\Exception\AnilistUnavailableException;
use App\Repository\SeriesRepository;
use App\Services\SeriesRefresher;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SeriesController extends AbstractController
{
    private const string ANILIST_DETAIL_QUERY = 'query($id:Int){
      Media(id:$id,type:ANIME){
        id title{romaji english} coverImage{extraLarge}
        episodes status nextAiringEpisode{episode}
        season source genres format seasonYear airingSchedule{nodes{airingAt}}
      }}';
    private const string BAN_KEY_PREFIX = 'anilist_ban_';
    private const int BAN_TTL_SECONDS = 12 * 3600;

    public function __construct(protected EntityManagerInterface $entityManager, protected SeriesRepository $seriesRepository,
                                #[Target('anilist.client')] private readonly HttpClientInterface $httpClient, private readonly SeriesRefresher $seriesRefresher,
                                #[Target('anilistSearchBurstLimiter')] private readonly RateLimiterFactoryInterface $anilistSearchBurstLimiter,
                                #[Target('anilistDetailBurstLimiter')] private readonly RateLimiterFactoryInterface $anilistDetailBurstLimiter,
                                #[Target('anilistSearchHourLimiter')] private readonly RateLimiterFactoryInterface $anilistSearchHourLimiter,
                                #[Target('anilistDetailHourLimiter')] private readonly RateLimiterFactoryInterface $anilistDetailHourLimiter,
                                private readonly CacheItemPoolInterface $cache, private readonly LoggerInterface $logger)
    {
    }

    private function enforceUserAnilistLimits(User $user, RateLimiterFactoryInterface $burstLimiter, RateLimiterFactoryInterface $hourLimiter,
                                              string $endpoint): ?JsonResponse
    {
        $userId = $user->getUserIdentifier();
        $banItem = $this->cache->getItem(self::BAN_KEY_PREFIX . $userId);

        if ($banItem->isHit()) {
            return new JsonResponse(['error' => 'Acceso temporalmente suspendido por abuso'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$burstLimiter->create($userId)->consume()->isAccepted()) {
            $this->logger->warning('AniList burst limit hit', ['user' => $userId, 'endpoint' => $endpoint]);
            return new JsonResponse(['error' => 'Demasiadas peticiones. Espera un momento.'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$hourLimiter->create($userId)->consume()->isAccepted()) {
            $banItem->set(true);
            $banItem->expiresAfter(self::BAN_TTL_SECONDS);
            $this->cache->save($banItem);
            $this->logger->warning('AniList hour limit hit — user banned 12h', ['user' => $userId, 'endpoint' => $endpoint]);
            return new JsonResponse(['error' => 'Acceso suspendido durante 12 horas por abuso'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }

    #[Route('/api/series/deleteSeries', name: 'delete_series', methods: ['POST'])]
    public function deleteSeries(Request $request, #[CurrentUser] User $user): Response
    {
        $seriesData = json_decode($request->getContent(), true);
        $series = $this->findOneSeriesByAnilistId($seriesData['anilistId']);
        $seriesToDelete = $this->entityManager->getRepository(UserSeries::class)->findOneBy(['user' => $user, 'series' => $series]);

        $this->entityManager->remove($seriesToDelete);
        $this->entityManager->flush();

        return $this->json(['response' => 'ok']);
    }

    #[Route('/api/series/addEpisodeToSeries', name: 'add_episode_to_series', methods: ['POST'])]
    public function addEpisodeToSeries(Request $request, #[CurrentUser] User $user): Response
    {
        $data = json_decode($request->getContent(), true);
        $foundSeries = $this->findOneSeriesByAnilistId($data['anilistId']);
        $userSeries = $this->entityManager->getRepository(UserSeries::class)->findOneBy(['user' => $user, 'series' => $foundSeries]);

        if ($userSeries === null) {
            return $this->json(['error' => 'No tienes esta serie en tu lista'], 404);
        }

        $numberEpisodeToAdd = $userSeries->getLastEpisodeWatchedCount() + 1;
        if (!$this->episodeIsAvailable($foundSeries, $numberEpisodeToAdd)) {
            try {
                if ($this->seriesRefresher->refreshIfReleasingDue($foundSeries)) {
                    $this->entityManager->flush();
                }
            } catch (AnilistUnavailableException) {
                return $this->json(['error' => 'No se pudo verificar el episodio, prueba en unos minutos'], 503);
            }

            if (!$this->episodeIsAvailable($foundSeries, $numberEpisodeToAdd)) {
                if ($foundSeries->getAiringStatus() === SeriesStatus::FINISHED->value) {
                    return $this->json(['error' => 'Esta serie ya está completa'], 409);
                }
                return $this->json(['error' => 'El episodio aún no se ha emitido'], 409);
            }
        }
        if ($foundSeries->getAiringStatus() === SeriesStatus::FINISHED->value && $numberEpisodeToAdd === $foundSeries->getTotalEpisodes()) {
            $userSeries->setIsCompleted(true);
        }

        $userSeries->setLastEpisodeWatchedCount($numberEpisodeToAdd);
        $userSeries->setCountEpisodesCompleted($numberEpisodeToAdd);

        $userSeriesHistory = new UserEpisodeWatch();
        $userSeriesHistory->setUser($user);
        $userSeriesHistory->setSeries($foundSeries);

        $this->entityManager->persist($userSeriesHistory);
        $this->entityManager->flush();

        return $this->json(['lastEpisodeWatched' => $numberEpisodeToAdd, 'isCompleted' => $userSeries->isCompleted()]);
    }

    private function episodeIsAvailable(Series $series, int $episode): bool
    {
        $seriesEpisodes = match ($series->getAiringStatus()) {
            SeriesStatus::FINISHED->value  => $series->getTotalEpisodes(),
            SeriesStatus::RELEASING->value => $series->getCurrentAiringEpisode(),
            default => 0,
        };

        return $episode <= $seriesEpisodes;
    }

    private function ensureSeriesIsTrackable(Series $series): ?Response
    {
        if ($series->getAiringStatus() === SeriesStatus::NOT_YET_RELEASED->value) {
            return $this->json(['error' => 'No puedes seguir un anime que aún no se ha emitido'], 409);
        }

        return null;
    }

    #[Route('/api/series/getUserSeries', name: 'get_user_series', methods: ['GET'])]
    public function getUserSeries(#[CurrentUser] User $user): Response
    {
        $userSeries = $this->entityManager->getRepository(UserSeries::class)->findByUser($user);

        return $this->json(['userSeries' => $userSeries], Response::HTTP_OK, [], ['groups' => ['home:userSeries']]);
    }

    #[Route('/api/series/search', name: 'get_series', methods: ['GET'])]
    public function getSeries(Request $request, #[CurrentUser] User $user): Response
    {
        $seriesNameUserInput = $request->query->get('animeName');
        if (mb_strlen($seriesNameUserInput) < 2) {
            return $this->json(['series' => []]);
        }
        $localSeriesFound = $this->seriesRepository->searchSimilarAnime($seriesNameUserInput);
        if (count($localSeriesFound) >= 5) {
            return $this->json(['series' => $localSeriesFound], Response::HTTP_OK, [], ['groups' => ['search:series']]);
        }
        if ($limitResponse = $this->enforceUserAnilistLimits($user, $this->anilistSearchBurstLimiter, $this->anilistSearchHourLimiter, 'search')) {
            return $limitResponse;
        }
        $seriesFromAniList = $this->fetchFromAnilist($seriesNameUserInput);
        $localAnilistIds = array_map(fn (Series $series) => $series->getAnilistId(), $localSeriesFound);
        $filteredAnilistSeries = array_filter(
            $seriesFromAniList,
            fn (Series $series) => !in_array($series->getAnilistId(), $localAnilistIds, true)
        );

        return $this->json(['series' => [...$localSeriesFound, ...$filteredAnilistSeries]], Response::HTTP_OK, [], ['groups' => ['search:series']]);
    }

    #[Route('/api/series/anilist/{anilistId}', name: 'get_series_anilist_detail', requirements: ['anilistId' => '\d+'], methods: ['GET'])]
    public function getAnilistSeriesDetail(int $anilistId, #[CurrentUser] User $user): Response
    {
        $series = $this->entityManager->getRepository(Series::class)->findOneByAnilistId($anilistId);

        if (!$series) {
            if ($limitResponse = $this->enforceUserAnilistLimits($user, $this->anilistDetailBurstLimiter, $this->anilistDetailHourLimiter, 'detail')) {
                return $limitResponse;
            }
            $media = $this->fetchAnilistDataById($anilistId);
            if ($media === null) {
                return $this->json(['error' => 'No se encontró el anime'], Response::HTTP_NOT_FOUND);
            }
            $series = Series::createSeriesFromAnilistData($media);
            $this->entityManager->persist($series);
            $this->entityManager->flush();
        }
        $tracking = $this->entityManager->getRepository(UserSeries::class)->findOneBy(['user' => $user, 'series' => $series]);

        return $this->json(['series' => $series, 'tracking' => $tracking], Response::HTTP_OK, [], ['groups' => ['detail:series', 'detail:userSeries']]);
    }

    #[Route('/api/series/createUserSeries', name: 'search_series', methods: ['POST'])]
    public function addNewUserSeries(Request $request, #[CurrentUser] User $user): Response
    {
        $seriesData = json_decode($request->getContent(), true);
        $anilistId = $seriesData['anilistId'];
        $series = $this->entityManager->getRepository(Series::class)->findOneByAnilistId($anilistId);
        $isNewSeries = false;

        if (!$series) {
            if ($limitResponse = $this->enforceUserAnilistLimits($user, $this->anilistDetailBurstLimiter, $this->anilistDetailHourLimiter, 'createUserSeries')) {
                return $limitResponse;
            }
            $media = $this->fetchAnilistDataById($anilistId);
            if ($media === null) {
                return $this->json(['error' => 'Anime no encontrado'], Response::HTTP_NOT_FOUND);
            }
            $series = Series::createSeriesFromAnilistData($media);
            $isNewSeries = true;
        }

        if ($errorResponse = $this->ensureSeriesIsTrackable($series)) {
            return $errorResponse;
        }

        if ($isNewSeries) {
            $this->entityManager->persist($series);
        }

        $userSeries = new UserSeries();
        $userSeries->setUser($user);
        $userSeries->setSeries($series);

        $this->entityManager->persist($userSeries);
        $this->entityManager->flush();

        return $this->json(['status' => 'ok']);
    }

    #[Route('/api/series/addSeriesToFavourite', name: 'add_to_favourites', methods: ['POST'])]
    public function addToFavoriteSeries(Request $request, #[CurrentUser] User $user): Response
    {
        $seriesData = json_decode($request->getContent(), true);
        $foundSeries = $this->findOneSeriesByAnilistId($seriesData['anilistId']);
        $seriesToChange = $this->entityManager->getRepository(UserSeries::class)->findOneBy(['user' => $user, 'series' => $foundSeries]);
        $seriesToChange->setIsFavourite(!$seriesToChange->IsFavourite());
        $this->entityManager->flush();

        return $this->json(['isFavourite' => $seriesToChange->IsFavourite()]);
    }

    private function queryAniList(string $query, array $variables): array
    {
        $response = $this->httpClient->request('POST', '', [
            'json' => [
                'query'     => $query,
                'variables' => $variables,
            ],
        ]);

        return $response->toArray();
    }

    private function fetchFromAniList(string $searchTerm): array
    {
        $graphqlQuery = 'query($search:String,$perPage:Int){
                        Page(page:1,perPage:$perPage){
                          media(search:$search,type:ANIME){
                            id title{romaji english} coverImage{extraLarge}
                            episodes status nextAiringEpisode{episode}
                            season source genres format seasonYear synonyms
                          }
                        }
                      }';

        try {
            $responseData = $this->queryAniList($graphqlQuery, [
                'search'  => $searchTerm,
                'perPage' => 5,
            ]);
        } catch (\Throwable) {
            return [];
        }

        return array_map(fn (array $media) => Series::createSeriesFromAnilistData($media), $responseData['data']['Page']['media'] ?? []);
    }

    private function fetchAnilistDataById(int $anilistId): ?array
    {
        $responseData = $this->queryAniList(self::ANILIST_DETAIL_QUERY, ['id' => $anilistId]);
        return $responseData['data']['Media'] ?? null;
    }

    private function findOneSeriesByAnilistId(int $anilistId): ?Series
    {
        return $this->entityManager->getRepository(Series::class)->findOneByAnilistId($anilistId);
    }
}
