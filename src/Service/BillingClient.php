<?php

namespace App\Service;

use App\Exception\BillingUnavailableException;

class BillingClient
{
    public function __construct(
        private readonly string $baseUrl
    ) {
    }

    public function auth(string $username, string $password): array
    {
        return $this->request('POST', '/api/v1/auth', [
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function register(string $email, string $password): array
    {
        return $this->request('POST', '/api/v1/register', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    public function getCurrentUser(string $token): array
    {
        return $this->request('GET', '/api/v1/users/current', [], $token);
    }

    private function request(
        string $method,
        string $path,
        array $data = [],
        ?string $token = null
    ): array {
        $curl = curl_init();

        if (false === $curl) {
            throw new BillingUnavailableException();
        }

        $url = rtrim($this->baseUrl, '/') . $path;

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        if (null !== $token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 5,
        ];

        if ([] !== $data) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if (false === $json) {
                throw new BillingUnavailableException();
            }

            $options[CURLOPT_POSTFIELDS] = $json;
        }

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);

        if (false === $response) {
            curl_close($curl);

            throw new BillingUnavailableException();
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

        curl_close($curl);

        if ('' === $response) {
            return [
                'code' => $statusCode,
                'data' => [],
            ];
        }

        $decodedResponse = json_decode($response, true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            return [
                'code' => $statusCode,
                'data' => [
                    'message' => $response,
                ],
            ];
        }

        return [
            'code' => $statusCode,
            'data' => $decodedResponse,
        ];
    }
}