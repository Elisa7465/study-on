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


    // Проверяет, что пользователь может открыть страницу истории операций.
    public function testUserCanOpenTransactionsPage(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginUserDirectly($client);

        $client->request('GET', '/profile/transactions');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'История операций');
        self::assertSelectorExists('table');
    }

    // Проверяет, что гость не может открыть историю операций.
    public function testGuestCannotOpenTransactionsPage(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/profile/transactions');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    // Проверяет, что в истории отображается пополнение баланса.
    public function testTransactionsPageShowsDeposit(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginUserDirectly($client);

        $client->request('GET', '/profile/transactions');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Пополнение');
        self::assertSelectorTextContains('body', '7250.50');
    }

    // Проверяет, что в истории отображается списание за курс.
    public function testTransactionsPageShowsPayment(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginUserDirectly($client);

        $client->request('GET', '/profile/transactions');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Списание');
        self::assertSelectorTextContains('body', '159.00');
    }

    // Проверяет, что транзакция по курсу содержит ссылку на курс.
    public function testTransactionWithCourseHasCourseLink(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginUserDirectly($client);

        $crawler = $client->request('GET', '/profile/transactions');

        self::assertResponseIsSuccessful();
        self::assertGreaterThan(0, $crawler->filter('a[href*="/courses/"]')->count());
    }

    // Проверяет, что со страницы профиля можно перейти в историю операций.
    public function testCanGoToTransactionsFromProfile(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginUserDirectly($client);

        $crawler = $client->request('GET', '/profile');

        self::assertResponseIsSuccessful();

        $link = $crawler->selectLink('История операций')->link();
        $client->click($link);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'История операций');
    }
}