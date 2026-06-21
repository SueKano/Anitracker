<?php

namespace App\Services;

use App\Entity\Series;
use App\Exception\AnilistUnavailableException;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class AnilistApiClient
{
    private const string ANILIST_DETAIL_QUERY = 'query($id:Int){
      Media(id:$id,type:ANIME){
        id title{romaji english} coverImage{extraLarge}
        episodes status nextAiringEpisode{episode airingAt} idMal isAdult
        season source genres format seasonYear synonyms airingSchedule{nodes{airingAt episode}}
      }}';

    private const int SEARCH_PER_PAGE = 10;
    private const int SEARCH_CACHE_TTL = 3600;

    public function __construct(#[Target('anilist.client')] private HttpClientInterface $anilistClient, private CacheItemPoolInterface $cache)
    {
    }

    public function fetchFromAnilist(string $searchTerm, int $page = 0): array
    {
        $pageNode = $this->fetchSearchPageCached($searchTerm, $page);
        $series = array_map(fn (array $media) => Series::createSeriesFromAnilistData($media), $pageNode['media'] ?? []);

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
                'query'     => $query,
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
}
