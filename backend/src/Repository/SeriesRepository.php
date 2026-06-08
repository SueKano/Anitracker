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
            ->where('s.airingStatus = :releasing AND s.airingDay = :airingDay AND (s.nextAiringAt IS NULL OR s.nextAiringAt <= :now)')
            ->setParameter('releasing', SeriesStatus::RELEASING->value)
            ->setParameter('airingDay', strtoupper(new \DateTime()->format('l')))
            ->setParameter('now', new \DateTimeImmutable('@' . time()))
            ->getQuery()
            ->getResult();
    }

    public function findFutureSeriesForAutoRefresh(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.airingStatus = :notYetReleased AND s.airingDay = :airingDay AND (s.lastRefreshedAt IS NULL OR s.lastRefreshedAt < :today)')
            ->orWhere('s.airingStatus = :notYetReleased AND s.airingDay IS NULL AND (s.lastRefreshedAt IS NULL OR s.lastRefreshedAt <= :notYetReleasedThreshold)')
            ->setParameter('notYetReleased', SeriesStatus::NOT_YET_RELEASED->value)
            ->setParameter('airingDay', strtoupper(new \DateTime()->format('l')))
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('notYetReleasedThreshold', new \DateTime(SeriesRefresher::NOT_YET_RELEASED_TTL))
            ->getQuery()
            ->getResult();
    }
    public function searchSimilarAnime(string $query, int $limit = 20): array
    {
        $normalizedQuery = mb_strtolower(trim($query));
        $likeQuery = '%' . addcslashes($normalizedQuery, '%_\\') . '%';

        $sql = <<<SQL
          SELECT id
          FROM series
          WHERE LOWER(romaji_name) ILIKE :likeQuery
             OR LOWER(COALESCE(english_name, '')) ILIKE :likeQuery
             OR LOWER(romaji_name) % :query
             OR LOWER(COALESCE(english_name, '')) % :query
             OR EXISTS (
                 SELECT 1 FROM jsonb_array_elements_text(synonyms) AS synonym
                 WHERE LOWER(synonym) ILIKE :likeQuery
                    OR LOWER(synonym) % :query
             )
          ORDER BY
              CASE
                  WHEN LOWER(romaji_name) ILIKE :likeQuery
                    OR LOWER(COALESCE(english_name, '')) ILIKE :likeQuery
                  THEN 1
                  ELSE 0
              END DESC,
              GREATEST(
                  similarity(LOWER(romaji_name), :query),
                  similarity(LOWER(COALESCE(english_name, '')), :query)
              ) DESC
          LIMIT :limit
      SQL;

        $orderedIds = $this->getEntityManager()->getConnection()
            ->executeQuery($sql, ['query' => $normalizedQuery, 'likeQuery' => $likeQuery, 'limit' => $limit], ['limit' => ParameterType::INTEGER])
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
