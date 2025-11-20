<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Overblog\GraphQLBundle\Definition\ArgumentInterface;
use Overblog\GraphQLBundle\Error\UserError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class AuthResolverService
{
    public function __construct(
        private AuthService $authService,
        private JWTTokenManagerInterface $jwtManager,
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function handleLogin(ArgumentInterface $args): array
    {
        $user = $this->authService->login($args['email'], $args['password']);

        if (!$user) {
            throw new UserError('Invalid credentials, Password Dobara Check krlo,And Try again');
        }

        return [
            'token' => $this->jwtManager->create($user),
            'user'  => $user,
        ];
    }

    public function getCurrentUser(): User
    {
        $token = $this->tokenStorage->getToken();

        if (null === $token || !($user = $token->getUser()) instanceof \App\Entity\User) {
            throw new UserError('Unauthenticated, Go Geet yourself a Token With Mutation, and Try again');
        }

        return $user;
    }

    // ← YE NEW REGISTER METHOD
    public function register(ArgumentInterface $args): array
    {
        $email = $args['email'] ?? throw new UserError('Email is required');
        $password = $args['password'] ?? throw new UserError('Password is required');
        $username = $args['username'] ?? throw new UserError('Username is required');
        $name = $args['name'] ?? null;

        // Check duplicates
        $repo = $this->em->getRepository(User::class);
        if ($repo->findOneBy(['email' => $email])) {
            throw new UserError('Email already registered');
        }
        if ($repo->findOneBy(['username' => $username])) {
            throw new UserError('Username already taken');
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setName($name);
        $user->setRoles(['ROLE_USER']);

        // Hash password
        $hashed = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        return [
            'token' => $this->jwtManager->create($user),
            'user' => $user,
        ];
    }
}