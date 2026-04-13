<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LessonControllerTest extends WebTestCase
{
      //проверка что на странице есть уроки
      public function testIndexReturnsOkAndShowsLessons(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Уроки');
        self::assertCount(23, $crawler->filter('.list-group-item'));
    }

    public function testShowReturns404ForMissingLesson(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/999999');

        self::assertResponseStatusCodeSame(404);
    }

   //Добавление урока через курс
    public function testAddLessonToCourse(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/courses/');

        $link=$crawler->selectLink('Открыть')->first()->link();
        $crawler=$client->click($link);

        $link=$crawler->selectLink('Добавить урок')->first()->link();
        $crawler=$client->click($link);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Создание урока');

        $form = $crawler->selectButton('Создать урок')->form([
            'lesson[title]' => 'Новый урок',
            'lesson[content]' => 'Новый урок',
            'lesson[sortOrder]' => 100,
        ]);
        $client->submit($form);
        self::assertResponseRedirects();
        $crawler=$client->followRedirect();
        self::assertSelectorTextContains('body', 'Новый урок');
    }

    //валидация урока по пустым полям
    public function testAddLessonValidationEmpty(): void
    {
      $client=static::createClient();
      $datas=[
            ['lesson[title]' => '', 'lesson[content]' => '', 'lesson[sortOrder]' => 100],
            ['lesson[title]' => 'Новый урок', 'lesson[content]' => '', 'lesson[sortOrder]' => 100],
            ['lesson[title]' => 'Новый урок', 'lesson[content]' => 'Новый урок', 'lesson[sortOrder]' => ''],
      ];
      foreach ($datas as $data) {
        $crawler=$client->request('GET', '/lessons/new');
        $form=$crawler->selectButton('Создать урок')->form($data);
        $client->submit($form);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('form');
      }
    }
    //валидация урока по длинным значениям и отрицательным
    public function testAddLessonValidationLength(): void
    {
        $client=static::createClient();
        $datas=[
            ['lesson[title]' => str_repeat('0', 256), 'lesson[content]' => 'Новый урок', 'lesson[sortOrder]' => 100],
            ['lesson[title]' => 'Новый урок', 'lesson[content]' => 'Новый урок', 'lesson[sortOrder]' => -100],
            ['lesson[title]' => 'Новый урок', 'lesson[content]' => 'Новый урок', 'lesson[sortOrder]' => 'Число'],
        ];
        foreach ($datas as $data) {
            $crawler=$client->request('GET', '/lessons/new');
            $form=$crawler->selectButton('Создать урок')->form($data);
            $client->submit($form);
            self::assertResponseStatusCodeSame(422);
        }
    }

    //редактирование урока
    public function testEditLesson(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/');

        $link=$crawler->selectLink('Открыть')->first()->link();
        $crawler=$client->click($link);

        $link=$crawler->selectLink('Редактировать')->first()->link();
        $crawler=$client->click($link);
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[title]' => 'Новый урок',
            'lesson[content]' => 'Новый урок',
            'lesson[sortOrder]' => 100,
        ]);
        $client->submit($form);

        self::assertResponseRedirects();
        $crawler=$client->followRedirect();
        self::assertSelectorTextContains('h1', 'Новый урок');
    }

    //удаление урока
    public function testDeleteLesson(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/lessons/');

        $link=$crawler->selectLink('Открыть')->first()->link();
        $crawler=$client->click($link);

        $form=$crawler->selectButton('Удалить урок')->form();
        $crawler=$client->submit($form);
        self::assertResponseRedirects();
        $crawler=$client->followRedirect();
    }

}