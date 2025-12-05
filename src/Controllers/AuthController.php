<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;
use App\Repositories\UserTokenRepository;
use App\Services\AuthService;

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService(
            new UserRepository(),
            new UserTokenRepository()
        );
    }

    public function register()
    {
        try {
            $body = Request::json();

            $email = $body['email'] ?? '';
            $password = $body['password'] ?? '';
            $role = $body['role'] ?? 'company';

            if (!$email || !$password) {
                throw new \Exception('email and password are required');
            }

            $id = $this->service->register($email, $password, $role);

            Response::success(['userId' => $id], 201);

        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }

    public function login()
    {
        try {
            $body = Request::json();

            $email = $body['email'] ?? '';
            $password = $body['password'] ?? '';

            if (!$email || !$password) {
                throw new \Exception('email and password are required');
            }

            $token = $this->service->login($email, $password);

            Response::success(['token' => $token], 200);

        } catch (\Exception $e) {
            Response::error($e->getMessage(), 400);
        }
    }
}
