<?php

namespace App\User;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistration
{
    private EntityManagerInterface $em;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;
    }

    /**
     * @return array{success: bool, message: string}
     */
    public function register(Request $request): array
    {
        $username = (string) $request->request->get('username');
        $password = (string) $request->request->get('password', '');
        $confirmPassword = (string) $request->request->get('confirm_password', '');
    
        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }
    
        if ($this->em->getRepository(User::class)->findOneBy(['username' => $username])) {
            return ['success' => false, 'message' => 'Username already exists.'];
        }
    
        $user = new User();
        $user->setUsername($username);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
    
        $this->em->persist($user);
        $this->em->flush();
    
        return ['success' => true, 'message' => 'Account created successfully!'];
    }
}