<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\UserTokenRepository;

class AuthService
{
    public function __construct(
        private UserRepository $users,
        private UserTokenRepository $tokens
    ) {}

    public function register(string $email, string $password, string $role = 'company'): int
    {
        $existing = $this->users->findByEmail($email);
        if ($existing) {
            throw new \Exception('Email already in use');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->users->create($email, $hash, $role);
    }

    public function login(string $email, string $password): string
    {
        $user = $this->users->findByEmail($email);
        if (!$user) {
            throw new \Exception('Invalid credentials');
        }

        if (!password_verify($password, $user['password_hash'])) {
            throw new \Exception('Invalid credentials');
        }

        $expiresAt = new \DateTimeImmutable('+30 days');
        return $this->tokens->createToken((int)$user['id'], $expiresAt);
    }

    public function getUserData(string $token): array
    {
        $user = $this->tokens->findUserByToken($token);

        if (!$user) {
            throw new \Exception('Invalid Token');
        }

        return [
            "email" => $user["email"],
            "role" => $user["role"]
        ];
    }
}
