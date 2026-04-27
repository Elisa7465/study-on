<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfileControllerTest extends WebTestCase
{
    use ControllerTestTrait;

    public function testGuestCannotOpenProfile(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/profile');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testUserCanOpenProfile(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->loginUserDirectly($client);

        $client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Профиль');
        self::assertSelectorTextContains('body', 'test-user@mail.ru');
        self::assertSelectorTextContains('body', 'Пользователь');
        self::assertSelectorTextContains('body', '7 250.50');
    }

    public function testAdminCanOpenProfile(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->loginAdminDirectly($client);

        $client->request('GET', '/profile');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Профиль');
        self::assertSelectorTextContains('body', 'test-admin@mail.ru');
        self::assertSelectorTextContains('body', 'Администратор');
        self::assertSelectorTextContains('body', '0.00');
    }

    public function testProfileShowsErrorWhenBillingUnavailable(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->loginUserWithUnavailableBilling($client);

        $client->request('GET', '/profile');

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertSelectorTextContains('body', 'Сервис временно недоступен');
    }
}