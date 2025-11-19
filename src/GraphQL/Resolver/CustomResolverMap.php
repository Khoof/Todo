<?php

namespace App\GraphQL\Resolver;

use App\Service\AuthService;
use Overblog\GraphQLBundle\Resolver\ResolverMap;
use GraphQL\Type\Definition\ResolveInfo;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use ArrayObject;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CustomResolverMap extends ResolverMap
{
    public function __construct(
        private AuthService $authService,
        private JWTTokenManagerInterface $jwtManager,
        private TokenStorageInterface $tokenStorage
        ) {}
        
        protected function map(): array
        {
            return [
                'Mutation' => [
                    self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {
                        return match ($info->fieldName) {
                            'login' => $this->handleLogin($args),
                            default => null,
                        };
                    },
                ],
                
                'Query' => [
                    self::RESOLVE_FIELD => function ($value, ArgumentInterface $args, ArrayObject $context, ResolveInfo $info) {
                        return match ($info->fieldName) {
                            'me' => $this->getCurrentUser(),
                            default => throw new \RuntimeException('Query not implemented yet'),
                        };
                    },
                ],
            ];
        }
        
        private function handleLogin(ArgumentInterface $args): array
        
    {
        $user = $this->authService->login($args['email'], $args['password']);

        if (!$user) {
            throw new AuthenticationException('Invalid credentials, Password Dobara Check krlo,And Try again');
        }

        return [
            'token' => $this->jwtManager->create($user),
            'user'  => $user,
        ];
    }

    private function getCurrentUser()
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token || !($user = $token->getUser()) instanceof \App\Entity\User) {
            throw new AuthenticationException('Unauthenticated, Go Geet yourself a Token With Mutation, and Try again');
        }

        return $user;
    }
}