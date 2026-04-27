<?php

namespace App\Tests\Mock;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\Response;

final class BillingClientMock extends BillingClient
{
    public function __construct()
    {
        parent::__construct('http://test-billing');
    }

    public function auth(string $username, string $password): array
    {
        if ('billing-unavailable@mail.ru' === $username) {
            throw new BillingUnavailableException();
        }

        if ('password' !== $password) {
            return [
                'code' => Response::HTTP_UNAUTHORIZED,
                'data' => [
                    'message' => 'Неверный email или пароль',
                ],
            ];
        }

        if ('test-user@mail.ru' === $username) {
            return [
                'code' => Response::HTTP_OK,
                'data' => [
                    'token' => 'user-jwt-token',
                ],
            ];
        }

        if ('test-admin@mail.ru' === $username) {
            return [
                'code' => Response::HTTP_OK,
                'data' => [
                    'token' => 'admin-jwt-token',
                ],
            ];
        }

        return [
            'code' => Response::HTTP_UNAUTHORIZED,
            'data' => [
                'message' => 'Неверный email или пароль',
            ],
        ];
    }

    public function register(string $email, string $password): array
    {
        if ('billing-unavailable@mail.ru' === $email) {
            throw new BillingUnavailableException();
        }

        if ('test-user@mail.ru' === $email || 'exists@mail.ru' === $email) {
            return [
                'code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'data' => [
                    'message' => 'Пользователь с таким email уже существует',
                ],
            ];
        }

        return [
            'code' => Response::HTTP_CREATED,
            'data' => [
                'token' => 'new-user-jwt-token',
            ],
        ];
    }

    public function getCurrentUser(string $token): array
    {
        if ('unavailable-token' === $token) {
            throw new BillingUnavailableException();
        }

        return match ($token) {
            'user-jwt-token' => [
                'code' => Response::HTTP_OK,
                'data' => [
                    'username' => 'test-user@mail.ru',
                    'roles' => ['ROLE_USER'],
                    'balance' => 7250.50,
                ],
            ],
            'admin-jwt-token' => [
                'code' => Response::HTTP_OK,
                'data' => [
                    'username' => 'test-admin@mail.ru',
                    'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
                    'balance' => 0.0,
                ],
            ],
            'new-user-jwt-token' => [
                'code' => Response::HTTP_OK,
                'data' => [
                    'username' => 'new-user@mail.ru',
                    'roles' => ['ROLE_USER'],
                    'balance' => 0.0,
                ],
            ],
            default => [
                'code' => Response::HTTP_UNAUTHORIZED,
                'data' => [
                    'message' => 'Invalid token',
                ],
            ],
        };
    }
}