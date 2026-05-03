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

class UserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private readonly BillingClient $billingClient,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function loadUserByIdentifier($identifier): UserInterface
    {
        $token = $this->requestStack->getMainRequest()?->cookies->get(BillingAuthenticator::BILLING_TOKEN_COOKIE);

        if (null === $token || '' === $token) {
            throw new UserNotFoundException('Пользователь не найден.');
        }

        try {
            $currentUserResponse = $this->billingClient->getCurrentUser($token);
        } catch (BillingUnavailableException) {
            throw new CustomUserMessageAuthenticationException(
                'Сервис временно недоступен. Попробуйте авторизоваться позднее.'
            );
        }

        if (200 !== $currentUserResponse['code']) {
            throw new CustomUserMessageAuthenticationException(
                $currentUserResponse['data']['message'] ?? 'Ошибка авторизации'
            );
        }

        $currentUser = $currentUserResponse['data'];
        $username = (string) ($currentUser['username'] ?? '');

        if ('' === $username || $username !== (string) $identifier) {
            throw new UserNotFoundException('Пользователь не найден.');
        }

        $user = new User();

        $user->setEmail($username);
        $user->setRoles($currentUser['roles'] ?? []);
        $user->setApiToken($token);

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
