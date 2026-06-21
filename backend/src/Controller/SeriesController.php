<?php

namespace App\Controller;

use App\Entity\Series;
use App\Entity\User;
use App\Entity\UserEpisodeWatch;
use App\Entity\UserSeries;
use App\Enum\ErrorCode;
use App\Enum\SeriesStatus;
use App\Exception\AnilistUnavailableException;
use App\Exception\JikanUnavailableException;
use App\Repository\SeriesRepository;
use App\Services\AnilistApiClient;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

class SeriesController extends AbstractController
{
    private const string BAN_KEY_PREFIX = 'anilist_ban_';
    private const int BAN_TTL_SECONDS = 12 * 3600;

    public function __construct(protected EntityManagerInterface $entityManager, protected SeriesRepository $seriesRepository,
                                private readonly SeriesRefresher $seriesRefresher, private readonly LoggerInterface $logger,
                                #[Target('anilistSearchBurstLimiter')] private readonly RateLimiterFactoryInterface $anilistSearchBurstLimiter,
                                #[Target('anilistDetailBurstLimiter')] private readonly RateLimiterFactoryInterface $anilistDetailBurstLimiter,
                                #[Target('anilistSearchHourLimiter')] private readonly RateLimiterFactoryInterface $anilistSearchHourLimiter,
                                #[Target('anilistDetailHourLimiter')] private readonly RateLimiterFactoryInterface $anilistDetailHourLimiter,
                                private readonly CacheItemPoolInterface $cache, private readonly AnilistApiClient $anilistClient)
    {
    }

    private function enforceUserAnilistLimits(User $user, RateLimiterFactoryInterface $burstLimiter, RateLimiterFactoryInterface $hourLimiter,
                                              string $endpoint): ?JsonResponse
    {
        $userId = $user->getUserIdentifier();
        $banItem = $this->cache->getItem(self::BAN_KEY_PREFIX . $userId);

        if ($banItem->isHit()) {
            return new JsonResponse(['errorCode' => ErrorCode::ACCESS_SUSPENDED_ABUSE->value], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$burstLimiter->create($userId)->consume()->isAccepted()) {
            $this->logger->warning('AniList burst limit hit', ['user' => $userId, 'endpoint' => $endpoint]);
            return new JsonResponse(['errorCode' => ErrorCode::RATE_LIMITED->value], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$hourLimiter->create($userId)->consume()->isAccepted()) {
            $banItem->set(true);
            $banItem->expiresAfter(self::BAN_TTL_SECONDS);
            $this->cache->save($banItem);
            $this->logger->warning('AniList hour limit hit — user banned 12h', ['user' => $userId, 'endpoint' => $endpoint]);
            return new JsonResponse(['errorCode' => ErrorCode::ACCESS_SUSPENDED_12H->value], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return null;
    }

    #[Route('/api/series/deleteSeries', name: 'delete_series', methods: ['POST'])]
    public function deleteSeries(Request $request, #[CurrentUser] User $user): Response
    {
        $seriesData = json_decode($request->getContent(), true);
        $series = $this->findOneSeriesByAnilistId($seriesData['anilistId']);
        $seriesToDelete = $this->entityManager->getRepository(UserSeries::class)->findOneBy(['user' => $user, 'series' => $series]);
        if (!$seriesToDelete) {
            return $this->json(['errorCode' => ErrorCode::SERIES_NOT_FOUND->value], Response::HTTP_NOT_FOUND);
        }

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
            return $this->json(['errorCode' => ErrorCode::SERIES_NOT_IN_LIST->value], 404);
        }

        $numberEpisodeToAdd = $userSeries->getLastEpisodeWatchedCount() + 1;
        if (!$this->episodeIsAvailable($foundSeries, $numberEpisodeToAdd)) {
            try {
                if ($this->seriesRefresher->refreshIfReleasingDue($foundSeries)) {
                    $this->entityManager->flush();
                }
            } catch (AnilistUnavailableException) {
                return $this->json(['errorCode' => ErrorCode::EPISODE_VERIFICATION_FAILED->value], 503);
            }

            if (!$this->episodeIsAvailable($foundSeries, $numberEpisodeToAdd)) {
                if ($foundSeries->getAiringStatus() === SeriesStatus::FINISHED->value) {
                    return $this->json(['errorCode' => ErrorCode::SERIES_ALREADY_COMPLETED->value], 409);
                }
                return $this->json(['errorCode' => ErrorCode::EPISODE_NOT_AIRED->value], 409);
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

    #[Route('/api/series/getUserSeries', name: 'get_user_series', methods: ['GET'])]
    public function getUserSeries(#[CurrentUser] User $user): Response
    {
        $userSeries = $this->entityManager->getRepository(UserSeries::class)->findByUser($user, ['id' => 'DESC']);

        return $this->json(['userSeries' => $userSeries], Response::HTTP_OK, [], ['groups' => ['home:userSeries']]);
    }

    #[Route('/api/series/search', name: 'get_series', methods: ['GET'])]
    public function getSeries(Request $request, #[CurrentUser] User $user): Response
    {
        $seriesNameUserInput = $request->query->get('animeName');
        if (mb_strlen($seriesNameUserInput) < 3) {
            return $this->json(['series' => [], 'hasMore' => false]);
        }
        $page = max(0, $request->query->getInt('page'));
        $localSeriesFound = $page === 0 ? $this->seriesRepository->searchSimilarAnime($seriesNameUserInput) : [];

        if ($this->enforceUserAnilistLimits($user, $this->anilistSearchBurstLimiter, $this->anilistSearchHourLimiter, 'search') !== null) {
            return $this->json(['series' => $localSeriesFound, 'hasMore' => false, 'limited' => true], Response::HTTP_OK, [], ['groups' => ['search:series']]);
        }
        ['series' => $seriesFromAniList, 'hasMore' => $hasMore] = $this->anilistClient->fetchFromAnilist($seriesNameUserInput, $page);

        $localAnilistIds = array_map(fn (Series $series) => $series->getAnilistId(), $localSeriesFound);
        $filteredAnilistSeries = array_filter($seriesFromAniList, fn (Series $series) => !in_array($series->getAnilistId(), $localAnilistIds, true));

        return $this->json(['series' => [...$localSeriesFound, ...$filteredAnilistSeries], 'hasMore' => $hasMore], Response::HTTP_OK, [], ['groups' => ['search:series']]);
    }

    #[Route('/api/series/anilist/{anilistId}', name: 'get_series_anilist_detail', requirements: ['anilistId' => '\d+'], methods: ['GET'])]
    public function getAnilistSeriesDetail(int $anilistId, #[CurrentUser] User $user): Response
    {
        $series = $this->entityManager->getRepository(Series::class)->findOneByAnilistId($anilistId);

        if (!$series) {
            if ($limitResponse = $this->enforceUserAnilistLimits($user, $this->anilistDetailBurstLimiter, $this->anilistDetailHourLimiter, 'detail')) {
                return $limitResponse;
            }
            try {
                $media = $this->anilistClient->fetchAnilistDataById($anilistId);
            } catch (AnilistUnavailableException) {
                return $this->json(['errorCode' => ErrorCode::SERIES_LOOKUP_FAILED->value], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            if ($media === null) {
                return $this->json(['errorCode' => ErrorCode::SERIES_NOT_FOUND->value], Response::HTTP_NOT_FOUND);
            }

            $series = Series::createSeriesFromAnilistData($media);
            $this->refreshAdultSeriesFromMal($series);
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
            try {
                $media = $this->anilistClient->fetchAnilistDataById($anilistId);
            } catch (AnilistUnavailableException) {
                return $this->json(['errorCode' => ErrorCode::SERIES_LOOKUP_FAILED->value], Response::HTTP_SERVICE_UNAVAILABLE);
            }

            if ($media === null) {
                return $this->json(['errorCode' => ErrorCode::SERIES_NOT_FOUND->value], Response::HTTP_NOT_FOUND);
            }

            $series = Series::createSeriesFromAnilistData($media);
            $this->refreshAdultSeriesFromMal($series);
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
        if (!$seriesToChange) {
            return $this->json(['errorCode' => ErrorCode::SERIES_NOT_FOUND->value], Response::HTTP_NOT_FOUND);
        }

        $seriesToChange->setIsFavourite(!$seriesToChange->IsFavourite());
        $this->entityManager->flush();

        return $this->json(['isFavourite' => $seriesToChange->IsFavourite()]);
    }

    #[Route('/api/series/updateEpisodeToAdultSeries', name: 'update_episode_adult_series', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function updateEpisodeToAdultSeries(Request $request): Response
    {
        $seriesData = json_decode($request->getContent(), true);
        $series = $this->findOneSeriesByAnilistId($seriesData['anilistId']);

        if (!$series) {
            return $this->json(['errorCode' => ErrorCode::SERIES_NOT_FOUND->value], Response::HTTP_NOT_FOUND);
        }
        if (!$series->getIsAdult()) {
            return $this->json(['errorCode' => ErrorCode::SERIES_NOT_ADULT->value], Response::HTTP_BAD_REQUEST);
        }
        if ($seriesData['currentAiringEpisode'] < 1 || $seriesData['currentAiringEpisode'] > 12){
            return $this->json(['errorCode' => ErrorCode::INVALID_VALUE->value], Response::HTTP_BAD_REQUEST);
        }

        $series->setCurrentAiringEpisode($seriesData['currentAiringEpisode']);
        $series->setTotalEpisodes($seriesData['currentAiringEpisode']);
        $this->entityManager->flush();

        return $this->json(['status' => 'ok']);
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
            return $this->json(['errorCode' => ErrorCode::SERIES_NOT_YET_RELEASED->value], 409);
        }

        return null;
    }

    private function findOneSeriesByAnilistId(int $anilistId): ?Series
    {
        return $this->entityManager->getRepository(Series::class)->findOneByAnilistId($anilistId);
    }

    private function refreshAdultSeriesFromMal(Series $series): void
    {
        if ($series->getIsAdult()){
            try {
                $this->seriesRefresher->refreshFromMal($series);
            } catch (JikanUnavailableException) {
            }
        }
    }
}
