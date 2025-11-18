<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class MutationService
{
    public function __construct(
        private UserProviderInterface $userProvider,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    public function loginUser(string $email, string $password): array
    {
        $user = $this->userProvider->loadUserByIdentifier($email);

        // THIS is the important part – check if password is correct
        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new BadCredentialsException('Wrong password dala ha bro!, Check and try again.');
        }

        $token = $this->jwtManager->create($user);

        return [
            'user' => $user,
            'token' => $token
        ];
    }
}