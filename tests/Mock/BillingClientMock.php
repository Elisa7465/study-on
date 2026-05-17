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
                    'refresh_token' => 'user-refresh-token',
                ],
            ];
        }

        if ('test-admin@mail.ru' === $username) {
            return [
                'code' => Response::HTTP_OK,
                'data' => [
                    'token' => 'admin-jwt-token',
                    'refresh_token' => 'admin-refresh-token',
                ],
            ];
        }

        if ('poor-user@mail.ru' === $username) {
            return [
                'code' => Response::HTTP_OK,
                'data' => [
                    'token' => 'poor-user-jwt-token',
                    'refresh_token' => 'poor-user-refresh-token',
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
                'refresh_token' => 'new-user-refresh-token',
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
                    'balance' => 999.00,
                ],
            ],
            'poor-user-jwt-token' => [
                'code' => Response::HTTP_OK,
                'data' => [
                    'username' => 'poor-user@mail.ru',
                    'roles' => ['ROLE_USER'],
                    'balance' => 0.00,
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
            'new-jwt-token' => [
                'code' => Response::HTTP_OK,
                'data' => [
                    'username' => 'test-user@mail.ru',
                    'roles' => ['ROLE_USER'],
                    'balance' => 7250.50,
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

    public function getTokenByRefreshToken(string $refreshToken): array
    {
        return [
            'code' => Response::HTTP_OK,
            'data' => [
                'token' => 'new-jwt-token',
                'refresh_token' => 'new-refresh-token',
            ],
        ];
    }

    public function getCourses(): array
    {
        return [
            'code' => Response::HTTP_OK,
            'data' => [
                [
                    'code' => 'php-basic',
                    'type' => 'buy',
                    'price' => '159.00',
                ],
                [
                    'code' => 'english-basic',
                    'type' => 'rent',
                    'price' => '99.90',
                ],
                [
                    'code' => 'html-basic',
                    'type' => 'free',
                ],
                [
                    'code' => 'not-paid-course',
                    'type' => 'buy',
                    'price' => '200.00',
                ],
            ],
        ];
    }

    public function getCourse(string $code): array
    {
        foreach ($this->getCourses()['data'] as $course) {
            if ($course['code'] === $code) {
                return [
                    'code' => Response::HTTP_OK,
                    'data' => $course,
                ];
            }
        }

        return [
            'code' => Response::HTTP_NOT_FOUND,
            'data' => [
                'message' => 'Курс не найден',
            ],
        ];
    }

    public function payCourse(string $code, string $token): array
    {
        if ('poor-user-jwt-token' === $token) {
            return [
                'code' => Response::HTTP_NOT_ACCEPTABLE,
                'data' => [
                    'message' => 'На вашем счету недостаточно средств',
                ],
            ];
        }

        if ('php-basic' === $code) {
            return [
                'code' => Response::HTTP_OK,
                'data' => [
                    'success' => true,
                    'course_type' => 'buy',
                    'expires_at' => null,
                ],
            ];
        }

        if ('english-basic' === $code) {
            return [
                'code' => Response::HTTP_OK,
                'data' => [
                    'success' => true,
                    'course_type' => 'rent',
                    'expires_at' => (new \DateTimeImmutable('+1 week'))->format(\DateTimeInterface::ATOM),
                ],
            ];
        }

        return [
            'code' => Response::HTTP_NOT_FOUND,
            'data' => [
                'message' => 'Курс не найден',
            ],
        ];
    }

    public function createCourse(array $data, string $token): array
    {
        return [
            'code' => Response::HTTP_CREATED,
            'data' => [
                'success' => true,
            ],
        ];
    }

    public function updateCourse(string $code, array $data, string $token): array
    {
        return [
            'code' => Response::HTTP_OK,
            'data' => [
                'success' => true,
            ],
        ];
    }
    public function getTransactions(string $token, array $filters = []): array
    {
        $transactions = [
            [
                'id' => 1,
                'created_at' => '2026-05-04T10:00:00+00:00',
                'type' => 'deposit',
                'amount' => '7250.50',
            ],
            [
                'id' => 2,
                'created_at' => '2026-05-04T10:10:00+00:00',
                'type' => 'payment',
                'course_code' => 'php-basic',
                'amount' => '159.00',
            ],
            [
                'id' => 3,
                'created_at' => '2026-05-04T10:20:00+00:00',
                'type' => 'payment',
                'course_code' => 'english-basic',
                'amount' => '99.90',
                'expires_at' => (new \DateTimeImmutable('+1 week'))->format(\DateTimeInterface::ATOM),
            ],
        ];

        if ('poor-user-jwt-token' === $token) {
            $transactions = [
                [
                    'id' => 4,
                    'created_at' => '2026-05-04T10:00:00+00:00',
                    'type' => 'deposit',
                    'amount' => '0.00',
                ],
            ];
        }

        if (($filters['type'] ?? null) === 'payment') {
            $transactions = array_filter(
                $transactions,
                static fn (array $transaction): bool => 'payment' === $transaction['type']
            );
        }

        if (($filters['type'] ?? null) === 'deposit') {
            $transactions = array_filter(
                $transactions,
                static fn (array $transaction): bool => 'deposit' === $transaction['type']
            );
        }

        if (isset($filters['course_code'])) {
            $transactions = array_filter(
                $transactions,
                static fn (array $transaction): bool => ($transaction['course_code'] ?? null) === $filters['course_code']
            );
        }

        return [
            'code' => Response::HTTP_OK,
            'data' => array_values($transactions),
        ];
    }
}