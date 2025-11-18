<?php

namespace App\GraphQL\Resolver;

use App\Service\MutationService;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use ArrayObject;

class CustomResolverMap extends ResolverMap
{
    public function __construct(
        private MutationService $mutationService
    ) {}

    protected function map(): array
    {
        return [
            'Mutation' => [
                self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {
                    return match ($info->fieldName) {
                        'login' => $this->mutationService->loginUser(
                            $args['email'],
                            $args['password']
                        ),
                        default => null,
                    };
                },
            ],

            // Add real queries later here, they will be protected automatically
            'Query' => [
                self::RESOLVE_FIELD => fn() => throw new \RuntimeException('Query not implemented yet'),
            ],
        ];
    }
}