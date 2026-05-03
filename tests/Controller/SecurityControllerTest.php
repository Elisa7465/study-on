<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    use ControllerTestTrait;
      //авторизация вообще есть 
    public function testLoginPageIsAccessible(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Вход');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="email"]');
        self::assertSelectorExists('input[name="password"]');
        self::assertSelectorExists('button[type="submit"]');
    }
//авторизация успешна
    public function testLoginSuccess(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->submitLoginForm($client, 'test-user@mail.ru');

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Курсы');
    }
//рлохой пароль
    public function testLoginFailure(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->submitLoginForm($client, 'test-user@mail.ru', 'wrong-password');

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger');
    }
//если биллинг недоступен
    public function testLoginShowsErrorWhenBillingUnavailable(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->submitLoginForm($client, 'billing-unavailable@mail.ru');

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertSelectorTextContains(
            'body',
            'Сервис временно недоступен. Попробуйте авторизоваться позднее'
        );
    }
//при авторизации редиректна профиль
    public function testLoginRedirectsIfAlreadyAuthenticated(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->loginUserDirectly($client);

        $client->request('GET', '/login');

        self::assertResponseRedirects('/profile');
    }
//страничка регистрации есть
    public function testRegisterPageIsAccessible(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/register');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Регистрация');
        self::assertSelectorExists('form');
        self::assertSelectorExists('input[name="register[email]"]');
        self::assertSelectorExists('input[name="register[password][first]"]');
        self::assertSelectorExists('input[name="register[password][second]"]');
        self::assertSelectorExists('button[type="submit"]');
    }
//регистрация успешна
    public function testRegisterSuccess(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/register');

        $client->submitForm('Зарегистрироваться', [
            'register[email]' => 'new-user@mail.ru',
            'register[password][first]' => 'password123',
            'register[password][second]' => 'password123',
        ]);

        self::assertResponseRedirects();

        $client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Курсы');
    }
//пароли не совпадают при регистрации
    public function testRegisterPasswordMismatch(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/register');

        $client->submitForm('Зарегистрироваться', [
            'register[email]' => 'new-user@mail.ru',
            'register[password][first]' => 'password123',
            'register[password][second]' => 'different123',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
    }
//короткий пароль при регистрации
    public function testRegisterShortPasswordShowsValidationError(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/register');

        $client->submitForm('Зарегистрироваться', [
            'register[email]' => 'new-user@mail.ru',
            'register[password][first]' => '123',
            'register[password][second]' => '123',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('.invalid-feedback');
    }
//существует пользователь с таким email
    public function testRegisterDuplicateEmail(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/register');

        $client->submitForm('Зарегистрироваться', [
            'register[email]' => 'exists@mail.ru',
            'register[password][first]' => 'password123',
            'register[password][second]' => 'password123',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.alert-danger');
        self::assertSelectorTextContains('body', 'Пользователь с таким email уже существует');
    }
//если биллинг недоступен
    public function testRegisterShowsErrorWhenBillingUnavailable(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/register');

        $client->submitForm('Зарегистрироваться', [
            'register[email]' => 'billing-unavailable@mail.ru',
            'register[password][first]' => 'password123',
            'register[password][second]' => 'password123',
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            'body',
            'Сервис временно недоступен. Попробуйте зарегистрироваться позднее'
        );
    }
//при регистрации редиректна профиль
    public function testRegisterRedirectsIfAlreadyAuthenticated(): void
    {
        $client = $this->createClientWithBillingMock();

        $this->loginUserDirectly($client);

        $client->request('GET', '/register');

        self::assertResponseRedirects('/profile');
    }
}