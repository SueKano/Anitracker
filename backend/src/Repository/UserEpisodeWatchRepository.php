<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserEpisodeWatch;
use App\Entity\UserSeries;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserEpisodeWatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserEpisodeWatch::class);
    }

    public function getCompletedEpisodesByUserAndYear(User $user, int $year): int
    {
        return (int) $this->createQueryBuilder('userEpisodeWatch')
            ->select('COUNT(userEpisodeWatch.id)')
            ->join('userEpisodeWatch.series', 'series')
            ->where('userEpisodeWatch.createdAt >= :startDate AND userEpisodeWatch.createdAt < :endDate AND series.format != :format')
            ->andWhere('userEpisodeWatch.user = :user')
            ->setParameter('user', $user)
            ->setParameter('startDate', new DateTime($year . '-01-01'))
            ->setParameter('endDate', new DateTime(($year + 1 ) . '-01-01'))
            ->setParameter('format', 'MOVIE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFirstWatchPerSeries(User $user, array $seriesIds): array
    {
        if (empty($seriesIds)) {
            return [];
        }

        $rows = $this->createQueryBuilder('userEpisodeWatch')
            ->select('IDENTITY(userEpisodeWatch.series) AS seriesId, MIN(userEpisodeWatch.createdAt) AS firstWatch')
            ->where('userEpisodeWatch.user = :user')
            ->andWhere('userEpisodeWatch.series IN (:seriesIds)')
            ->groupBy('userEpisodeWatch.series')
            ->setParameter('user', $user)
            ->setParameter('seriesIds', $seriesIds)
            ->getQuery()
            ->getArrayResult();

        $userSeriesFirstWatch = [];
        foreach ($rows as $row) {
            $userSeriesFirstWatch[(int) $row['seriesId']] = new DateTime($row['firstWatch']);
        }
        return $userSeriesFirstWatch;
    }
}
