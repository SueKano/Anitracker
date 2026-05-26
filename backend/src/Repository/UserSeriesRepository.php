<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserSeries;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserSeriesRepository extends ServiceEntityRepository
{
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
            ->andWhere('us.completedAt >= :start AND us.completedAt < :end')
            ->setParameter('user', $user)
            ->setParameter('start', new DateTime("$year-01-01 00:00:00"))
            ->setParameter('end', new DateTime(($year + 1) . '-01-01 00:00:00'))
            ->orderBy('us.completedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countAddedByUserAndYear(User $user, int $year): int
    {
        return (int) $this->createQueryBuilder('us')
            ->select('COUNT(us.id)')
            ->where('us.user = :user')
            ->andWhere('us.createdAt >= :start AND us.createdAt < :end')
            ->setParameter('user', $user)
            ->setParameter('start', new DateTime("$year-01-01"))
            ->setParameter('end', new DateTime(($year + 1) . '-01-01'))
            ->getQuery()
            ->getSingleScalarResult();
    }
}
