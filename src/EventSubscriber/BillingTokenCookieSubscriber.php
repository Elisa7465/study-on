<?php

namespace App\EventSubscriber;

use App\Security\BillingAuthenticator;
use App\Security\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class BillingTokenCookieSubscriber implements EventSubscriberInterface
{
    private const REMEMBER_ME_COOKIE = 'REMEMBERME';
    private const REMEMBER_ME_TOKEN_TTL = 604800;

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $rememberMeEnabled = $request->cookies->has(self::REMEMBER_ME_COOKIE);

        $this->syncCookie(
            $response,
            $request->cookies->get(BillingAuthenticator::BILLING_TOKEN_COOKIE),
            BillingAuthenticator::BILLING_TOKEN_COOKIE,
            $user->getApiToken(),
            $request->isSecure(),
            $rememberMeEnabled
        );

        $this->syncCookie(
            $response,
            $request->cookies->get(BillingAuthenticator::BILLING_REFRESH_TOKEN_COOKIE),
            BillingAuthenticator::BILLING_REFRESH_TOKEN_COOKIE,
            $user->getRefreshToken(),
            $request->isSecure(),
            $rememberMeEnabled
        );
    }

    private function syncCookie(
        \Symfony\Component\HttpFoundation\Response $response,
        ?string $currentValue,
        string $cookieName,
        ?string $newValue,
        bool $secure,
        bool $rememberMeEnabled
    ): void {
        if (null === $newValue || '' === $newValue || $newValue === $currentValue) {
            return;
        }

        $cookie = Cookie::create($cookieName)
            ->withValue($newValue)
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure($secure)
            ->withSameSite(Cookie::SAMESITE_LAX);

        if ($rememberMeEnabled) {
            $cookie = $cookie->withExpires(time() + self::REMEMBER_ME_TOKEN_TTL);
        }

        $response->headers->setCookie($cookie);
    }
}
