<?php

namespace App\Repository;

use App\Entity\Answer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Answer>
 */
class AnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Answer::class);
    }

    public function getSumVotesByQuestion(int $questionId): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('SUM(a.votes)')
            ->where('a.question = :questionId')
            ->setParameter('questionId', $questionId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
