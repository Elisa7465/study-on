<?php

namespace App\Service;

final class JwtDecoder
{
    public function decodePayload(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return [];
        }

        $payload = strtr($parts[1], '-_', '+/');
        $padding = strlen($payload) % 4;

        if ($padding > 0) {
            $payload .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($payload, true);

        if (false === $decoded) {
            return [];
        }

        $data = json_decode($decoded, true);

        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    public function isExpired(?string $jwt, int $reserveSeconds = 30): bool
    {
        if (null === $jwt || '' === $jwt) {
            return true;
        }

        $payload = $this->decodePayload($jwt);

        if (!isset($payload['exp'])) {
            return true;
        }

        return $payload['exp'] <= time() + $reserveSeconds;
    }
}