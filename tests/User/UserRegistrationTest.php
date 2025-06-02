<?php

namespace App\Tests\User;

use App\Entity\User;
use App\User\UserRegistration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationTest extends TestCase
{
    public function testRegisterSuccess(): void
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn(null);
        $emMock->method('getRepository')->willReturn($repoMock);
        $emMock->expects($this->once())->method('persist')->with($this->isInstanceOf(User::class));
        $emMock->expects($this->once())->method('flush');

        $passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasherMock->method('hashPassword')->willReturn('hashed_password');

        $request = new Request([], [
            'username' => 'testuser',
            'password' => 'password123',
            'confirm_password' => 'password123'
        ]);

        $registration = new UserRegistration($emMock, $passwordHasherMock);
        $result = $registration->register($request);

        $this->assertTrue($result['success']);
        $this->assertEquals('Account created successfully!', $result['message']);
    }

    public function testRegisterPasswordsDoNotMatch(): void
    {
        $emMock = $this->createMock(EntityManagerInterface::class);
        $passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);

        $request = new Request([], [
            'username' => 'testuser',
            'password' => 'password123',
            'confirm_password' => 'wrongpassword'
        ]);

        $registration = new UserRegistration($emMock, $passwordHasherMock);
        $result = $registration->register($request);

        $this->assertFalse($result['success']);
        $this->assertEquals('Passwords do not match.', $result['message']);
    }

    public function testRegisterUsernameExists(): void
    {
        $existingUser = new User();
        $emMock = $this->createMock(EntityManagerInterface::class);
        $repoMock = $this->createMock(EntityRepository::class);
        $repoMock->method('findOneBy')->willReturn($existingUser);
        $emMock->method('getRepository')->willReturn($repoMock);

        $passwordHasherMock = $this->createMock(UserPasswordHasherInterface::class);

        $request = new Request([], [
            'username' => 'testuser',
            'password' => 'password123',
            'confirm_password' => 'password123'
        ]);

        $registration = new UserRegistration($emMock, $passwordHasherMock);
        $result = $registration->register($request);

        $this->assertFalse($result['success']);
        $this->assertEquals('Username already exists.', $result['message']);
    }
}