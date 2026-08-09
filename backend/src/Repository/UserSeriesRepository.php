<?php

namespace App\Repository;

use App\Entity\Series;
use App\Entity\User;
use App\Entity\UserSeries;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSeriesRepository extends ServiceEntityRepository
{
    public const int ACTIVITY_WINDOW_DAYS = 90;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSeries::class);
    }

    public function findCompletedSeriesByUserAndYear(User $user, int $year): array
    {
        return $this->createQueryBuilder('us')
            ->innerJoin('us.series', 's')
            ->addSelect('s')
            ->where('us.user = :user')
            ->andWhere('us.isCompleted = true')
            ->andWhere('us.importedAt IS NULL OR us.completedAt > us.importedAt')
            ->andWhere('us.completedAt >= :start AND us.completedAt < :end')
            ->setParameter('user', $user)
            ->setParameter('start', new DateTime("$year-01-01 00:00:00"))
            ->setParameter('end', new DateTime(($year + 1) . '-01-01 00:00:00'))
            ->orderBy('us.completedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function findRecentActivityByUser(User $user, int $days = self::ACTIVITY_WINDOW_DAYS): array
    {
        return $this->createQueryBuilder('us')
            ->innerJoin('us.series', 's')
            ->addSelect('s')
            ->where('us.user = :user')
            ->andWhere('us.lastProgressAt > :since')
            ->setParameter('user', $user)
            ->setParameter('since', new DateTime("-$days days"))
            ->orderBy('us.lastProgressAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findTrackedAnilistIdsByUser(User $user): array
    {
        return $this->createQueryBuilder('us')
            ->select('s.anilistId')
            ->innerJoin('us.series', 's')
            ->where('us.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function hasImportedSeries(User $user): bool
    {
        return (bool) $this->createQueryBuilder('us')
            ->select('COUNT(us.id)')
            ->where('us.user = :user')
            ->andWhere('us.importedAt IS NOT NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markFavouritesByAnilistIds(User $user, array $anilistIds): int
    {
        if (!$anilistIds) {
            return 0;
        }

        $seriesIds = $this->getEntityManager()->createQueryBuilder()
            ->select('s.id')
            ->from(Series::class, 's')
            ->where('s.anilistId IN (:anilistIds)')
            ->getDQL();

        return $this->createQueryBuilder('us')
            ->update()
            ->set('us.isFavourite', ':favourite')
            ->where('us.user = :user')
            ->andWhere("us.series IN ($seriesIds)")
            ->setParameter('favourite', true)
            ->setParameter('user', $user)
            ->setParameter('anilistIds', $anilistIds)
            ->getQuery()
            ->execute();
    }
}
