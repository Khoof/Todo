<?php

namespace App\Service;

use App\Entity\Todo;
use App\Entity\User;
use App\Repository\TodoRepository;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Error\UserError;

class TodoQueryService
{
    public function __construct(
        private TodoRepository $todoRepository
    ) {}

    public function getTodos(User $user, ArgumentInterface $args): array
    {
        if (isset($args['completed'])) {
            return $this->todoRepository->findByUserAndCompleted($user, $args['completed']);
        }

        return $this->todoRepository->findByUser($user);
    }

    public function getTodoById(User $user, ArgumentInterface $args): ?Todo
    {
        $todo = $this->todoRepository->findOneByIdAndUser((int)$args['id'], $user);

        if (!$todo) {
            throw new UserError('Todo not found or you do not have permission to access it');
        }

        return $todo;
    }
}