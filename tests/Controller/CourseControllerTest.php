<?php

namespace App\Tests\Controller;

use App\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CourseControllerTest extends WebTestCase
{
    use ControllerTestTrait;
      //проверка что на странице есть курсы
    public function testIndexReturnsOkAndShowsCourses(): void
    {
        $client = $this->createClientWithBillingMock();
        $crawler = $client->request('GET', '/courses/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Курсы');
        self::assertCount(7, $crawler->filter('.card-title a'));
    }
//проверка странички курса 
    public function testShowReturnsOkAndShowsCorrectLessonsCount(): void
    {
        $client = $this->createClientWithBillingMock();
        $crawler=$client->request('GET', '/courses/');

        $link=$crawler->selectLink('Открыть курс')->first()->link();
        $crawler=$client->click($link);

        self::assertResponseIsSuccessful();
        self::assertCount(3, $crawler->filter('.list-group-item'));

    }
//ошибка курса которого нет
    public function testShowReturns404ForMissingCourse(): void
    {
        $client = $this->createClientWithBillingMock();
        $crawler=$client->request('GET', '/courses/999999');

        self::assertResponseStatusCodeSame(404);
    }
//новый курс
    public function testNewPageReturnsOk(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginAdminDirectly($client);
        $crawler=$client->request('GET', '/courses/new');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Создание курса');

        $form = $crawler->selectButton('Создать курс')->form([
            'course[symbolCode]' => 'docker-basic',
            'course[title]' => 'Основы Docker',
            'course[description]' => 'Курс по Docker',
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        $crawler=$client->followRedirect();
        self::assertSelectorTextContains('h1', 'Основы Docker');
    }

    public function testCreateCourseValidationEmpty(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginAdminDirectly($client);

        $datas=[
            [
                    'course[symbolCode]' => '',
                    'course[title]' => 'Основы Docker',
                    'course[description]' => 'Курс по Docker',
            ],
            [
                'course[symbolCode]' => 'course-without-title',
                'course[title]' => '',
                'course[description]' => 'Курс по PHP',
            ],           
        ];
        foreach ($datas as $data) {
            $crawler = $client->request('GET', '/courses/new');
            $form = $crawler->selectButton('Создать курс')->form($data);
            $client->submit($form);
            self::assertResponseStatusCodeSame(422);
            self::assertSelectorExists('.invalid-feedback');
        }
    }

    public function testCreateCourseWithDuplicateSymbolCodeShowsValidationError(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginAdminDirectly($client);
        $crawler = $client->request('GET', '/courses/new');

        $form = $crawler->selectButton('Создать курс')->form([
            'course[symbolCode]' => 'php-basic',
            'course[title]' => 'Другой курс',
            'course[description]' => 'Описание',
        ]);

        $client->submit($form);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Курс с таким символьным кодом уже существует');
    }
    public function testCreateCourseWithLinghtError(): void{
        $client = $this->createClientWithBillingMock();
        $this->loginAdminDirectly($client);

        $datas=[
            [
                    'course[symbolCode]' => str_repeat('0', 256),
                    'course[title]' => 'Основы Docker',
                    'course[description]' => 'Курс по Docker',
            ],
            [
                'course[symbolCode]' => 'course-without-title',
                'course[title]' => str_repeat('0', 256),
                'course[description]' => 'Курс по PHP',
            ],           
        ];
        foreach ($datas as $data) {
            $crawler = $client->request('GET', '/courses/new');
            $form = $crawler->selectButton('Создать курс')->form($data);
            $client->submit($form);
            self::assertResponseStatusCodeSame(422);
        }
    }
//страница редактирования курса
    public function testEditPageReturnsOk(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginAdminDirectly($client);
        $crawler = $client->request('GET', '/courses/');

        $link=$crawler->selectLink('Открыть курс')->first()->link();
        $crawler=$client->click($link);

        $link=$crawler->selectLink('Редактировать')->link();
        $crawler=$client->click($link);
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'course[symbolCode]' => 'english-basic',
            'course[title]' => 'Новое имя для курса',
            'course[description]' => 'Новое описание курса',
            ]);
        $client->submit($form);

        self::assertResponseRedirects();
        $crawler = $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Новое имя для курса');
    }
//админ пробует редактировать несуществующий курс
    public function testEditReturns404ForMissingCourse(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginAdminDirectly($client);
        $crawler=$client->request('GET', '/courses/999999/edit');

        self::assertResponseStatusCodeSame(404);
    }
//админ удаляет курс
    public function testDeleteCourse(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginAdminDirectly($client);
        $crawler = $client->request('GET', '/courses/');

        $link=$crawler->selectLink('Открыть курс')->first()->link();
        $crawler=$client->click($link);  

        $form=$crawler->selectButton('Удалить курс')->form();
        $client->submit($form);
        self::assertResponseRedirects();
        $crawler=$client->followRedirect();
        self::assertCount(6,$crawler->filter('.card-title a'));
    }

//гость пробует открыть список курсов
    public function testGuestCanOpenCoursesList(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/courses/');

        self::assertResponseIsSuccessful();
    }
//гость пробует открыть курс
    public function testGuestCanOpenCoursePage(): void
    {
        $client = $this->createClientWithBillingMock();

        $crawler = $client->request('GET', '/courses/');
        $link = $crawler->selectLink('Открыть курс')->first()->link();

        $client->click($link);

        self::assertResponseIsSuccessful();
    }
//гость не может открыть страницу создания курса
    public function testGuestCannotOpenNewCoursePage(): void
    {
        $client = $this->createClientWithBillingMock();

        $client->request('GET', '/courses/new');

        self::assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }
//пользователь не может открыть страницу создания курса
    public function testRegularUserCannotOpenNewCoursePage(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginUserDirectly($client);

        $client->request('GET', '/courses/new');

        self::assertResponseStatusCodeSame(403);
    }
//пользователь не видит кнопки управления курсом
    public function testRegularUserDoesNotSeeCourseManagementButtons(): void
    {
        $client = $this->createClientWithBillingMock();
        $this->loginUserDirectly($client);

        $client->request('GET', '/courses/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextNotContains('body', 'Новый курс');
    }
}