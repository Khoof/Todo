<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

class AuthService
{
    private $em;
    private $passwordEncoder;
    private $jwtManager;

    public function __construct(
        EntityManagerInterface $em,
        
        UserPasswordEncoderInterface $passwordEncoder,
        
        JWTTokenManagerInterface $jwtManager
        ) {
            
            $this->em = $em;
            
        $this->passwordEncoder = $passwordEncoder;

        $this->jwtManager = $jwtManager;

    }
    
    public function login(string $email, string $password): array
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        if (!$this->passwordEncoder->isPasswordValid($user, $password)) {
            throw new \Exception('Invalid credentials');
        }
        
        $token = $this->jwtManager->create($user);
        
        return [
            'token' => $token,
            'user' => $user,
        ];
    }
}