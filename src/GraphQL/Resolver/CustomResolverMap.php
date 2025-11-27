<?php

namespace App\GraphQL\Resolver;

use App\Service\AuthResolverService;
use App\Service\TodoQueryService;
use App\Service\TodoMutationService;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use ArrayObject;

class CustomResolverMap extends ResolverMap
{
    public function __construct(
        private AuthResolverService $authResolverService,
        private TodoQueryService $todoQueryService,
        private TodoMutationService $todoMutationService
    ) {}

    // ← YEH PRIVATE HELPER BHI ADD KAR DO (DRY principle ke liye)
    private function currentUser(): \App\Entity\User
    {
        return $this->authResolverService->getCurrentUser();
    }

    protected function map(): array
    {
        return [
            'Mutation' => [
                self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {
                    return match ($info->fieldName) {
                        'login'               => $this->authResolverService->handleLogin($args),
                        'register'            => $this->authResolverService->register($args),
                        'createOrUpdateTodo'  => $this->todoMutationService->createOrUpdateTodo($this->currentUser(), $args),
                        'deleteTodo'          => $this->todoMutationService->deleteTodo($this->currentUser(), $args),
                        'toggleTodoComplete'  => $this->todoMutationService->toggleTodoComplete($this->currentUser(), $args),
                         default              => null,
                    };
                },
            ],
            
            'Query' => [
                self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {
                    return match ($info->fieldName) {
                        'me'          => $this->authResolverService->getCurrentUser(),
                        'getTodos'    => $this->todoQueryService->getTodos($this->currentUser(), $args),
                        'getTodoById' => $this->todoQueryService->getTodoById($this->currentUser(), $args),
                        default       => throw new \RuntimeException('Query not implemented yet'),
                    };
                },
            ],
        ];
    }
}