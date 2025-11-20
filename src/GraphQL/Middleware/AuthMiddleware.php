<?php

namespace App\GraphQL\Middleware;

use Overblog\GraphQLBundle\Resolver\ResolverContext;
use Overblog\GraphQLBundle\Resolver\ResolverMiddlewareInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authenticator\JWTAuthenticator;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthMiddleware implements ResolverMiddlewareInterface
{
    public function __construct(
        private JWTAuthenticator $jwtAuthenticator,
        private RequestStack $requestStack
        ) {}
        
        public function __invoke($object, $args, ResolverContext $context, $info)
        {
        dd($this->tokenStorage->getToken());   // ← if you see the token object → firewall works!
        // This is the name of the field they are trying to call (login, me, createTodo, etc.)
        $fieldName = $info->fieldName;

        // Allow login without any token
        if ($fieldName === 'login') {
            return null; // let it continue normally
        }

        // For ALL other fields → require JWT
        $request = $this->requestStack->getCurrentRequest();
        
        // This does the real JWT check (same as Lexik does on normal API routes)
        $this->jwtAuthenticator->authenticate($request);

        // If token is bad or missing → throws exception → GraphQL returns error
        // If good → we do nothing and let the resolver run
        return null;
    }
}