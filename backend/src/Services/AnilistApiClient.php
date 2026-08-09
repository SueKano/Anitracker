<?php

namespace App\Services;

use App\Entity\Series;
use App\Enum\ErrorCode;
use App\Exception\AnilistUnavailableException;
use App\Exception\ApiException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class AnilistApiClient
{
    private const string ANILIST_DETAIL_QUERY = 'query($id:Int){
      Media(id:$id,type:ANIME){
        id title{romaji english} coverImage{extraLarge}
        episodes status nextAiringEpisode{episode airingAt} idMal isAdult
        season source genres format seasonYear synonyms tags{name rank} studios{edges{isMain node{name}}} airingSchedule{nodes{airingAt episode}}
      }}';

    private const string USER_LIST_QUERY = 'query($userName:String,$chunk:Int,$perChunk:Int){
      MediaListCollection(userName:$userName,type:ANIME,status_in:[CURRENT,COMPLETED,REPEATING],chunk:$chunk,perChunk:$perChunk){
        hasNextChunk
        lists{
          isCustomList
          entries{
            status progress score(format:POINT_10)
            completedAt{year month day}
            media{
              id title{romaji english} coverImage{extraLarge}
              episodes status nextAiringEpisode{episode airingAt} idMal isAdult
              season source genres format seasonYear synonyms tags{name rank} studios{edges{isMain node{name}}}
            }
          }
        }
      }}';

    private const string USER_FAVOURITES_QUERY = 'query($userName:String,$page:Int,$perPage:Int){
      User(name:$userName){
        favourites{
          anime(page:$page,perPage:$perPage){
            pageInfo{hasNextPage}
            nodes{id}
          }
        }
      }}';

    private const int SEARCH_PER_PAGE = 10;
    private const int SEARCH_CACHE_TTL = 3600;
    private const int LIST_PER_CHUNK = 500;
    private const int MAX_LIST_CHUNKS = 10;
    private const int FAVOURITES_PER_PAGE = 25;
    private const int MAX_FAVOURITE_PAGES = 12;

    public function __construct(#[Target('anilist.client')] private HttpClientInterface $anilistClient, private CacheItemPoolInterface $cache,
                                private LoggerInterface $logger)
    {
    }

    public function fetchFromAnilist(string $searchTerm, int $page = 0): array
    {
        $pageNode = $this->fetchSearchPageCached($searchTerm, $page);
        $series = array_map(fn(array $media) => Series::createSeriesFromAnilistData($media), $pageNode['media'] ?? []);

        return ['series' => $series, 'hasMore' => $pageNode['pageInfo']['hasNextPage'] ?? false];
    }

    private function fetchSearchPageCached(string $searchTerm, int $page): array
    {
        $normalizedTerm = mb_strtolower(trim($searchTerm));
        $cacheItem = $this->cache->getItem('anilist_search.' . hash('xxh128', $normalizedTerm . '.' . $page));

        if ($cacheItem->isHit()) {
            return $cacheItem->get();
        }

        $graphqlQuery = 'query($search:String,$page:Int,$perPage:Int){
                        Page(page:$page,perPage:$perPage){
                          pageInfo{hasNextPage}
                          media(search:$search,type:ANIME){
                            id title{romaji english} coverImage{extraLarge}
                            episodes status nextAiringEpisode{episode}
                            season source genres format seasonYear synonyms idMal isAdult
                          }
                        }
                      }';

        try {
            $responseData = $this->queryAnilist($graphqlQuery, ['search' => $searchTerm, 'page' => $page + 1, 'perPage' => self::SEARCH_PER_PAGE]);
        } catch (\Throwable) {
            return ['media' => [], 'pageInfo' => ['hasNextPage' => false]];
        }

        $pageNode = $responseData['data']['Page'] ?? ['media' => [], 'pageInfo' => ['hasNextPage' => false]];
        $cacheItem->set($pageNode);
        $cacheItem->expiresAfter(self::SEARCH_CACHE_TTL);
        $this->cache->save($cacheItem);

        return $pageNode;
    }

    private function queryAnilist(string $query, array $variables): array
    {
        $response = $this->anilistClient->request('POST', '', [
            'json' => [
                'query' => $query,
                'variables' => $variables,
            ],
        ]);

        return $response->toArray();
    }

    public function fetchAnilistDataById(int $anilistId): ?array
    {
        try {
            $responseData = $this->queryAnilist(self::ANILIST_DETAIL_QUERY, ['id' => $anilistId]);
        } catch (HttpExceptionInterface $e) {
            throw new AnilistUnavailableException('No se pudo consultar AniList', 0, $e);
        }

        return $responseData['data']['Media'] ?? null;
    }

    public function fetchUserListChunks(string $userName): \Generator
    {
        $chunk = 1;

        do {
            try {
                $responseData = $this->queryAnilist(self::USER_LIST_QUERY, ['userName' => $userName, 'chunk' => $chunk, 'perChunk' => self::LIST_PER_CHUNK]);
            } catch (ClientExceptionInterface $e) {
                throw $this->mapUserListError($e);
            } catch (HttpExceptionInterface $e) {
                throw new AnilistUnavailableException('No se pudo consultar AniList', 0, $e);
            }

            $collection = $responseData['data']['MediaListCollection'];
            $entries = [];
            foreach ($collection['lists'] as $list) {
                if (!$list['isCustomList']) {
                    $entries = [...$entries, ...$list['entries']];
                }
            }

            yield $entries;
            $chunk++;
        } while ($collection['hasNextChunk'] && $chunk <= self::MAX_LIST_CHUNKS);

        if ($collection['hasNextChunk']) {
            $this->logger->warning('AniList import truncated — the list exceeds the chunk limit', [
                'userName' => $userName, 'maxEntries' => self::MAX_LIST_CHUNKS * self::LIST_PER_CHUNK,
            ]);
        }
    }
    
    public function fetchUserFavouriteAnilistIds(string $userName): array
    {
        $favouriteIds = [];
        $page = 1;

        try {
            do {
                $responseData = $this->queryAnilist(self::USER_FAVOURITES_QUERY, [
                    'userName' => $userName, 'page' => $page, 'perPage' => self::FAVOURITES_PER_PAGE,
                ]);
                $animeNode = $responseData['data']['User']['favourites']['anime'];
                foreach ($animeNode['nodes'] as $node) {
                    $favouriteIds[$node['id']] = true;
                }
                $page++;
            } while ($animeNode['pageInfo']['hasNextPage'] && $page <= self::MAX_FAVOURITE_PAGES);

            if ($animeNode['pageInfo']['hasNextPage']) {
                $this->logger->warning('AniList favourites truncated — the list exceeds the page limit', [
                    'userName' => $userName, 'maxFavourites' => self::MAX_FAVOURITE_PAGES * self::FAVOURITES_PER_PAGE,
                ]);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('AniList favourites could not be read', ['userName' => $userName, 'exception' => $exception]);
        }

        return $favouriteIds;
    }

    private function mapUserListError(ClientExceptionInterface $exception): \Throwable
    {
        $message = mb_strtolower($exception->getResponse()->toArray(false)['errors'][0]['message'] ?? '');

        return match (true) {
            str_contains($message, 'private') => new ApiException(ErrorCode::ANILIST_LIST_PRIVATE, Response::HTTP_NOT_FOUND),
            str_contains($message, 'not found') => new ApiException(ErrorCode::ANILIST_USER_NOT_FOUND, Response::HTTP_NOT_FOUND),
            default => new AnilistUnavailableException('No se pudo consultar AniList', 0, $exception),
        };
    }
}
