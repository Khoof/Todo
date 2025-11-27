<?php

namespace App\Service;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Error\UserError;

class TodoMutationService
{
    public function __construct(
        private TodoRepository $todoRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createOrUpdateTodo(User $user, ArgumentInterface $args): Todo
{   
        $todoId   = $args['id'] ?? null;
        $newPriority = $args['priority'] ?? null;

        // Find or create
        if ($todoId) {
            $todo = $this->todoRepository->findOneByIdAndUser((int)$todoId, $user);
        if (!$todo) {
            throw new UserError('Todo not found or access denied.');
        }
        $oldPriority = $todo->getPriority();
        } else {
            $todo = new Todo();
            $todo->setUser($user);
            $this->entityManager->persist($todo);
            $oldPriority = null;
        }

        // Update fields
        if (isset($args['title']))       $todo->setTitle($args['title']);
        if (isset($args['description'])) $todo->setDescription($args['description']);
        if (isset($args['completed']))   $todo->setCompleted($args['completed']);

       // Priority Reordering if priority changed
        if ($newPriority !== null && $newPriority !== $oldPriority) {
        $this->reprioritizeTodos($user, $oldPriority, $newPriority);
        $todo->setPriority($newPriority);
        }

        $this->entityManager->flush();

        return $todo;
}

    
    private function reprioritizeTodos(User $user, ?int $old, ?int $new): void
{
        if ($old === $new || $new === null) 
            return;

        $qb = $this->entityManager->createQueryBuilder();

        if ($old === null || $new < $old) {
            // Naya todo ya upar shift → affected todos ki priority +1
            $qb->update(Todo::class, 't')
               ->set('t.priority', 't.priority + 1')
               ->where('t.user = :user')
               ->andWhere('t.priority >= :new')
               ->setParameter('user', $user)
               ->setParameter('new', $new);
        
        if ($old !== null) {
            $qb->andWhere('t.priority < :old')
               ->setParameter('old', $old);
        }
        } else {
            // Neeche shift down priority → affected todos ki priority -1
            $qb->update(Todo::class, 't')
               ->set('t.priority', 't.priority - 1')
               ->where('t.user = :user')
               ->andWhere('t.priority > :old')
               ->andWhere('t.priority <= :new')
               ->setParameter('user', $user)
               ->setParameter('old', $old)
               ->setParameter('new', $new);
        }

        $qb->getQuery()->execute();
}
    
    public function deleteTodo(User $user, ArgumentInterface $args): bool
    {
        $todo = $this->todoRepository->findOneByIdAndUser((int)$args['id'], $user);

        if (!$todo) {
            throw new UserError('Todo not found or you do not have permission to delete it');
        }

        $this->entityManager->remove($todo);
        $this->entityManager->flush();

        return true;
    }

    public function toggleTodoComplete(User $user, ArgumentInterface $args): Todo
    {
        $todo = $this->todoRepository->findOneByIdAndUser((int)$args['id'], $user);

        if (!$todo) {
            throw new UserError('Todo not found or you do not have permission to toggle it');
        }

        $todo->setCompleted(!$todo->isCompleted());
        $this->entityManager->flush();

        return $todo;
    }
}