<?php

namespace App\Tests\Controller;

use App\Security\User;
use App\Service\BillingClient;
use App\Tests\Mock\BillingClientMock;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

trait ControllerTestTrait
{
    protected function createClientWithBillingMock(): KernelBrowser
    {
        $client = static::createClient();
        $client->disableReboot();

        static::getContainer()->set(BillingClient::class, new BillingClientMock());

        return $client;
    }

    protected function submitLoginForm(
        KernelBrowser $client,
        string $email,
        string $password = 'password'
    ): void {
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();

        $client->submitForm('Войти', [
            'email' => $email,
            'password' => $password,
            '_remember_me' => false,
        ]);
    }

    protected function loginAsUser(KernelBrowser $client): Crawler
    {
        $this->submitLoginForm($client, 'test-user@mail.ru');

        self::assertResponseRedirects();

        return $client->followRedirect();
    }

    protected function loginAsAdmin(KernelBrowser $client): Crawler
    {
        $this->submitLoginForm($client, 'test-admin@mail.ru');

        self::assertResponseRedirects();

        return $client->followRedirect();
    }

    protected function loginUserDirectly(KernelBrowser $client): void
    {
        $user = new User();

        $user
            ->setEmail('test-user@mail.ru')
            ->setRoles(['ROLE_USER'])
            ->setBalance(7250.50)
            ->setApiToken('user-jwt-token');

        $client->loginUser($user, 'main');
    }

    protected function loginAdminDirectly(KernelBrowser $client): void
    {
        $user = new User();

        $user
            ->setEmail('test-admin@mail.ru')
            ->setRoles(['ROLE_USER', 'ROLE_SUPER_ADMIN'])
            ->setBalance(0.0)
            ->setApiToken('admin-jwt-token');

        $client->loginUser($user, 'main');
    }

    protected function loginUserWithUnavailableBilling(KernelBrowser $client): void
    {
        $user = new User();

        $user
            ->setEmail('test-user@mail.ru')
            ->setRoles(['ROLE_USER'])
            ->setBalance(7250.50)
            ->setApiToken('unavailable-token');

        $client->loginUser($user, 'main');
    }
}