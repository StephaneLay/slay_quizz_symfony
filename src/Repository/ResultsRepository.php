<?php

namespace App\Repository;

use App\Entity\Results;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Results>
 */
class ResultsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Results::class);
    }

    public function findTopResultsForQuizz($quizzId): array
{
    return $this->createQueryBuilder('r')
        ->where('r.completedAt IS NOT NULL')
        ->andWhere('r.quizz = :quizzId')
        ->setParameter('quizzId', $quizzId)
        ->orderBy('r.score', 'DESC')
        ->setMaxResults(5)
        ->getQuery()
        ->getResult();
}

}
