<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use App\Service\JwtDecoder;

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly BillingClient $billingClient,
        private readonly RequestStack $requestStack,
        private readonly JwtDecoder $jwtDecoder,
    ) {
    }

    public function loadUserByIdentifier($identifier): UserInterface
    {
        $token = $this->requestStack->getMainRequest()?->cookies->get(BillingAuthenticator::BILLING_TOKEN_COOKIE);
        $refreshToken = $this->requestStack->getMainRequest()?->cookies->get(
            BillingAuthenticator::BILLING_REFRESH_TOKEN_COOKIE
        );

        if (null === $token || '' === $token) {
            throw new UserNotFoundException('РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РЅРµ РЅР°Р№РґРµРЅ.');
        }

        try {
            $currentUserResponse = $this->billingClient->getCurrentUser($token);
        } catch (BillingUnavailableException) {
            throw new CustomUserMessageAuthenticationException(
                'РЎРµСЂРІРёСЃ РІСЂРµРјРµРЅРЅРѕ РЅРµРґРѕСЃС‚СѓРїРµРЅ. РџРѕРїСЂРѕР±СѓР№С‚Рµ Р°РІС‚РѕСЂРёР·РѕРІР°С‚СЊСЃСЏ РїРѕР·РґРЅРµРµ.'
            );
        }

        if (200 !== $currentUserResponse['code']) {
            throw new CustomUserMessageAuthenticationException(
                $currentUserResponse['data']['message'] ?? 'РћС€РёР±РєР° Р°РІС‚РѕСЂРёР·Р°С†РёРё'
            );
        }

        $currentUser = $currentUserResponse['data'];
        $username = (string) ($currentUser['username'] ?? '');

        if ('' === $username || $username !== (string) $identifier) {
            throw new UserNotFoundException('РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ РЅРµ РЅР°Р№РґРµРЅ.');
        }

        $user = new User();

        $user->setEmail($username);
        $user->setRoles($currentUser['roles'] ?? []);
        $user->setApiToken($token);
        $user->setRefreshToken($refreshToken);

        if (isset($currentUser['balance'])) {
            $user->setBalance((float) $currentUser['balance']);
        }

        return $user;
    }

    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        if (!$this->jwtDecoder->isExpired($user->getApiToken())) {
            return $user;
        }

        $refreshToken = $user->getRefreshToken();

        if (null === $refreshToken || '' === $refreshToken) {
            return $user;
        }

        try {
            $refreshResponse = $this->billingClient->getTokenByRefreshToken($refreshToken);
        } catch (BillingUnavailableException) {
            return $user;
        }

        if (200 !== ($refreshResponse['code'] ?? 0)) {
            return $user;
        }

        $data = $refreshResponse['data'] ?? [];

        if (isset($data['token'])) {
            $user->setApiToken($data['token']);
        }

        if (isset($data['refresh_token'])) {
            $user->setRefreshToken($data['refresh_token']);
        } elseif (isset($data['refreshToken'])) {
            $user->setRefreshToken($data['refreshToken']);
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
    }
}
