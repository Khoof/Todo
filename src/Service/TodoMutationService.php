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

    public function createTodo(User $user, ArgumentInterface $args): Todo
    {
        $todo = new Todo();
        $todo->setTitle($args['title']);
        $todo->setDescription($args['description']);
        $todo->setCompleted($args['completed'] ?? false);
        $todo->setUser($user);

        $this->entityManager->persist($todo);
        $this->entityManager->flush();

        return $todo;
    }

    public function updateTodo(User $user, ArgumentInterface $args): Todo
    {
        $todo = $this->todoRepository->findOneByIdAndUser((int)$args['id'], $user);

        if (!$todo) {
            throw new UserError('Todo not found or you do not have permission to update it');
        }

        if (isset($args['title'])) {
            $todo->setTitle($args['title']);
        }

        if (isset($args['description'])) {
            $todo->setDescription($args['description']);
        }

        if (isset($args['completed'])) {
            $todo->setCompleted($args['completed']);
        }

        $this->entityManager->flush();

        return $todo;
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