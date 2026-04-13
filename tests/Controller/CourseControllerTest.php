<?php

namespace App\Tests\Controller;

use App\Entity\Course;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CourseControllerTest extends WebTestCase
{
      //проверка что на странице есть курсы
    public function testIndexReturnsOkAndShowsCourses(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Курсы');
        self::assertCount(7, $crawler->filter('.card-title a'));
    }
//проверка странички курса 
    public function testShowReturnsOkAndShowsCorrectLessonsCount(): void
    {
        $client = static::createClient();
        $crawler=$client->request('GET', '/courses/');

        $link=$crawler->selectLink('Открыть курс')->first()->link();
        $crawler=$client->click($link);

        self::assertResponseIsSuccessful();
        self::assertCount(3, $crawler->filter('.list-group-item'));

    }
//ошибка курса которого нет
    public function testShowReturns404ForMissingCourse(): void
    {
        $client = static::createClient();
        $crawler=$client->request('GET', '/courses/999999');

        self::assertResponseStatusCodeSame(404);
    }
//новый курс
    public function testNewPageReturnsOk(): void
    {
        $client = static::createClient();
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
        $client = static::createClient();

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
        $client = static::createClient();
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
//страница редактирования курса
    public function testEditPageReturnsOk(): void
    {
        $client = static::createClient();
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

    public function testEditReturns404ForMissingCourse(): void
    {
        $client = static::createClient();
        $crawler=$client->request('GET', '/courses/999999/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteCourse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/');

        $link=$crawler->selectLink('Открыть курс')->first()->link();
        $crawler=$client->click($link);  

        $form=$crawler->selectButton('Удалить курс')->form();
        $client->submit($form);
        self::assertResponseRedirects();
        $crawler=$client->followRedirect();
        self::assertCount(6,$crawler->filter('.card-title a'));
    }
}