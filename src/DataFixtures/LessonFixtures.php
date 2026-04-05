<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LessonFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $lessonsData = [
            [
                'course_reference' => CourseFixtures::COURSE_PHP_BASIC,
                'title' => 'Переменные, типы данных и операторы',
                'content' => 'В этом уроке рассматриваются базовые типы данных PHP, объявление переменных, арифметические и логические операторы.',
                'sortOrder' => 100,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_PHP_BASIC,
                'title' => 'Условия и циклы',
                'content' => 'Урок посвящён условным конструкциям if, else, switch, а также циклам for, while и foreach.',
                'sortOrder' => 200,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_PHP_BASIC,
                'title' => 'Функции и области видимости',
                'content' => 'Рассматривается создание функций, передача аргументов, возвращаемые значения и области видимости переменных.',
                'sortOrder' => 300,
            ],

            [
                'course_reference' => CourseFixtures::COURSE_SYMFONY_START,
                'title' => 'Структура Symfony-проекта',
                'content' => 'Изучаем назначение основных директорий проекта: src, config, templates, public и migrations.',
                'sortOrder' => 100,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_SYMFONY_START,
                'title' => 'Маршруты и контроллеры',
                'content' => 'В уроке рассматривается создание маршрутов, атрибуты Route и написание простых контроллеров.',
                'sortOrder' => 200,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_SYMFONY_START,
                'title' => 'Шаблоны Twig',
                'content' => 'Разбираются основы Twig: вывод переменных, условия, циклы и наследование шаблонов.',
                'sortOrder' => 300,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_SYMFONY_START,
                'title' => 'Подключение Doctrine',
                'content' => 'Урок показывает, как подключить ORM, описать сущности и начать работу с базой данных.',
                'sortOrder' => 400,
            ],

            [
                'course_reference' => CourseFixtures::COURSE_SQL_POSTGRESQL,
                'title' => 'SELECT, INSERT, UPDATE и DELETE',
                'content' => 'Изучаем основные SQL-операции для выборки, добавления, изменения и удаления данных.',
                'sortOrder' => 100,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_SQL_POSTGRESQL,
                'title' => 'Связи между таблицами',
                'content' => 'Рассматриваются первичные и внешние ключи, а также построение связей один-ко-многим.',
                'sortOrder' => 200,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_SQL_POSTGRESQL,
                'title' => 'JOIN и объединение данных',
                'content' => 'Урок посвящён INNER JOIN, LEFT JOIN и практическим примерам выборки связанных данных.',
                'sortOrder' => 300,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_SQL_POSTGRESQL,
                'title' => 'Индексы и оптимизация запросов',
                'content' => 'Разбираем, как индексы влияют на производительность и как анализировать планы выполнения запросов.',
                'sortOrder' => 400,
            ],

            [
                'course_reference' => CourseFixtures::COURSE_WEB_DESIGN,
                'title' => 'Принципы хорошего интерфейса',
                'content' => 'В этом уроке рассматриваются понятность интерфейса, визуальная иерархия и удобство взаимодействия.',
                'sortOrder' => 100,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_WEB_DESIGN,
                'title' => 'Цвет, контраст и типографика',
                'content' => 'Изучаем основы подбора цветов, читаемости текста, размеров шрифтов и оформления заголовков.',
                'sortOrder' => 200,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_WEB_DESIGN,
                'title' => 'Адаптивная верстка',
                'content' => 'Разбираем, как проектировать страницы, которые корректно выглядят на телефоне, планшете и компьютере.',
                'sortOrder' => 300,
            ],

            [
                'course_reference' => CourseFixtures::COURSE_PERSONAL_FINANCE,
                'title' => 'Доходы и расходы',
                'content' => 'Урок посвящён учёту доходов и расходов, анализу трат и поиску лишних финансовых потерь.',
                'sortOrder' => 100,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_PERSONAL_FINANCE,
                'title' => 'Планирование личного бюджета',
                'content' => 'Рассматриваются простые методы ведения бюджета и распределения денег по основным категориям.',
                'sortOrder' => 200,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_PERSONAL_FINANCE,
                'title' => 'Финансовая подушка безопасности',
                'content' => 'Объясняется, зачем нужен резервный фонд, как определить его размер и как начать его формировать.',
                'sortOrder' => 300,
            ],

            [
                'course_reference' => CourseFixtures::COURSE_TIME_MANAGEMENT,
                'title' => 'Постановка целей',
                'content' => 'В этом уроке рассматриваются способы формулирования понятных и достижимых целей.',
                'sortOrder' => 100,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_TIME_MANAGEMENT,
                'title' => 'Приоритизация задач',
                'content' => 'Разбираем методы выделения важных задач и планирования рабочего дня без перегрузки.',
                'sortOrder' => 200,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_TIME_MANAGEMENT,
                'title' => 'Борьба с прокрастинацией',
                'content' => 'Урок посвящён причинам откладывания дел и практическим способам начать действовать вовремя.',
                'sortOrder' => 300,
            ],

            [
                'course_reference' => CourseFixtures::COURSE_ENGLISH_BASIC,
                'title' => 'Present Simple',
                'content' => 'Изучаем, как строятся утверждения, отрицания и вопросы в Present Simple.',
                'sortOrder' => 100,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_ENGLISH_BASIC,
                'title' => 'Основные глаголы и выражения',
                'content' => 'Рассматриваются часто используемые глаголы и простые выражения для повседневного общения.',
                'sortOrder' => 200,
            ],
            [
                'course_reference' => CourseFixtures::COURSE_ENGLISH_BASIC,
                'title' => 'Порядок слов в предложении',
                'content' => 'Урок посвящён базовому построению английских предложений и типичным ошибкам начинающих.',
                'sortOrder' => 300,
            ],
        ];

        foreach ($lessonsData as $lessonData) {
            $lesson = new Lesson();
            $lesson->setCourse($this->getReference($lessonData['course_reference'], Course::class));
            $lesson->setTitle($lessonData['title']);
            $lesson->setContent($lessonData['content']);
            $lesson->setSortOrder($lessonData['sortOrder']);

            $manager->persist($lesson);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CourseFixtures::class,
        ];
    }
}