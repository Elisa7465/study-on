<?php

namespace App\Security;

use App\Exception\BillingUnavailableException;
use App\Service\BillingClient;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class BillingAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private const REMEMBER_ME_TOKEN_TTL = 604800;

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly BillingClient $billingClient,
        private readonly CacheItemPoolInterface $cache
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('email');
        $password = $request->getPayload()->getString('password');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        try {
            $authResponse = $this->billingClient->auth($email, $password);
        } catch (BillingUnavailableException) {
            throw new CustomUserMessageAuthenticationException(
                'Сервис временно недоступен. Попробуйте авторизоваться позднее.'
            );
        }

        if (Response::HTTP_OK !== $authResponse['code']) {
            throw new CustomUserMessageAuthenticationException(
                $authResponse['data']['message'] ?? 'Неверный email или пароль.'
            );
        }

        $apiToken = $authResponse['data']['token'] ?? null;

        if (null === $apiToken) {
            throw new CustomUserMessageAuthenticationException('Ошибка авторизации.');
        }

        $userLoader = function () use ($apiToken): User {
            try {
                $currentUserResponse = $this->billingClient->getCurrentUser($apiToken);
            } catch (BillingUnavailableException) {
                throw new CustomUserMessageAuthenticationException(
                    'Сервис временно недоступен. Попробуйте авторизоваться позднее.'
                );
            }

            if (Response::HTTP_OK !== $currentUserResponse['code']) {
                throw new CustomUserMessageAuthenticationException(
                    $currentUserResponse['data']['message'] ?? 'Ошибка получения пользователя.'
                );
            }

            $currentUser = $currentUserResponse['data'];

            $user = new User();

            $user
                ->setEmail($currentUser['username'] ?? '')
                ->setRoles($currentUser['roles'] ?? [])
                ->setApiToken($apiToken);

            if (isset($currentUser['balance'])) {
                $user->setBalance((float) $currentUser['balance']);
            }

            $cacheItem = $this->cache->getItem('billing_token_' . hash('sha256', $user->getUserIdentifier()));
            $cacheItem->set($apiToken);
            $cacheItem->expiresAfter(self::REMEMBER_ME_TOKEN_TTL);
            $this->cache->save($cacheItem);

            return $user;
        };

        return new SelfValidatingPassport(
            new UserBadge($apiToken, $userLoader),
            [
                new CsrfTokenBadge(
                    'authenticate',
                    $request->getPayload()->getString('_csrf_token')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_course_index'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
