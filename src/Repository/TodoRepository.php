<?php

namespace App\Repository;

use App\Entity\Todo;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }
    
    /**
     * Find all todos for a specific user
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find todos by user and completion status
     */
    public function findByUserAndCompleted(User $user, bool $completed): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.completed = :completed')
            ->setParameter('user', $user)
            ->setParameter('completed', $completed)
            ->orderBy('t.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find a todo by ID and user (for security)
     */
    public function findOneByIdAndUser(int $id, User $user): ?Todo
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->andWhere('t.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}