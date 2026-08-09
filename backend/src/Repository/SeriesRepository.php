<?php

namespace App\Repository;

use App\Entity\Series;
use App\Enum\SeriesStatus;
use App\Services\SeriesRefresher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

class SeriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Series::class);
    }

    public function findAiringSeriesForAutoRefresh(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.airingStatus = :releasing AND s.isAdult = false AND ((s.nextAiringAt IS NOT NULL AND s.nextAiringAt <= :now) OR
                                (s.nextAiringAt IS NULL AND (s.lastRefreshedAt IS NULL OR s.lastRefreshedAt <= :staleThreshold)))')
            ->setParameter('releasing', SeriesStatus::RELEASING->value)
            ->setParameter('staleThreshold', new \DateTime(SeriesRefresher::RELEASING_SERIES_TTL))
            ->setParameter('now', new \DateTimeImmutable('@' . time()))
            ->getQuery()
            ->getResult();
    }

    public function findFutureSeriesForAutoRefresh(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.airingStatus = :notYetReleased AND s.isAdult = false AND ((s.nextAiringAt IS NOT NULL AND s.nextAiringAt <= :now) OR
                                (s.nextAiringAt IS NULL AND (s.lastRefreshedAt IS NULL OR s.lastRefreshedAt <= :notYetReleasedThreshold)))')
            ->setParameter('notYetReleased', SeriesStatus::NOT_YET_RELEASED->value)
            ->setParameter('now', new \DateTimeImmutable('@' . time()))
            ->setParameter('notYetReleasedThreshold', new \DateTime(SeriesRefresher::NOT_YET_RELEASED_TTL))
            ->getQuery()
            ->getResult();
    }

    public function findSeriesPendingCoverMirror(int $limit): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.portraitMirrorUrl IS NULL AND s.portraitUrl IS NOT NULL')
            ->orderBy('s.id', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchSimilarAnime(string $query, int $limit = 20): array
    {
        $normalizedQuery = mb_strtolower(trim($query));
        $likeQuery = '%' . addcslashes($normalizedQuery, '%_\\') . '%';
        $prefixQuery = addcslashes($normalizedQuery, '%_\\') . '%';

        $sql = <<<SQL
          SELECT id
          FROM series
          WHERE f_unaccent(LOWER(romaji_name)) ILIKE f_unaccent(:likeQuery)
             OR f_unaccent(LOWER(COALESCE(english_name, ''))) ILIKE f_unaccent(:likeQuery)
             OR f_unaccent(LOWER(romaji_name)) % f_unaccent(:query)
             OR f_unaccent(LOWER(COALESCE(english_name, ''))) % f_unaccent(:query)
             OR EXISTS (
                 SELECT 1 FROM jsonb_array_elements_text(synonyms) AS synonym
                 WHERE f_unaccent(LOWER(synonym)) ILIKE f_unaccent(:likeQuery)
                    OR f_unaccent(LOWER(synonym)) % f_unaccent(:query)
             )
          ORDER BY
              CASE
                  WHEN f_unaccent(LOWER(romaji_name)) ILIKE f_unaccent(:prefixQuery)
                    OR f_unaccent(LOWER(COALESCE(english_name, ''))) ILIKE f_unaccent(:prefixQuery)
                  THEN 0 ELSE 1
              END,
              CASE
                  WHEN f_unaccent(LOWER(romaji_name)) ILIKE f_unaccent(:likeQuery)
                    OR f_unaccent(LOWER(COALESCE(english_name, ''))) ILIKE f_unaccent(:likeQuery)
                  THEN 0 ELSE 1
              END,
              GREATEST(
                  similarity(f_unaccent(LOWER(romaji_name)), f_unaccent(:query)),
                  similarity(f_unaccent(LOWER(COALESCE(english_name, ''))), f_unaccent(:query)) * 0.8
              ) DESC
          LIMIT :limit
      SQL;

        $orderedIds = $this->getEntityManager()->getConnection()
            ->executeQuery($sql, ['query' => $normalizedQuery, 'likeQuery' => $likeQuery, 'prefixQuery' => $prefixQuery, 'limit' => $limit], ['limit' => ParameterType::INTEGER])
            ->fetchFirstColumn();

        if (empty($orderedIds)) {
            return [];
        }

        $seriesById = [];
        foreach ($this->findBy(['id' => $orderedIds]) as $series) {
            $seriesById[$series->getId()] = $series;
        }

        return array_values(array_map(fn (int $id) => $seriesById[$id], array_map('intval', $orderedIds)));
    }
}
