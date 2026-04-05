<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public const COURSE_PHP_BASIC = 'course_php_basic';
    public const COURSE_SYMFONY_START = 'course_symfony_start';
    public const COURSE_SQL_POSTGRESQL = 'course_sql_postgresql';
    public const COURSE_WEB_DESIGN = 'course_web_design';
    public const COURSE_PERSONAL_FINANCE = 'course_personal_finance';
    public const COURSE_TIME_MANAGEMENT = 'course_time_management';
    public const COURSE_ENGLISH_BASIC = 'course_english_basic';

    public function load(ObjectManager $manager): void
    {
        $coursesData = [
            [
                'reference' => self::COURSE_PHP_BASIC,
                'symbolCode' => 'php-basic',
                'title' => 'Основы PHP',
                'description' => 'Введение в серверную разработку на PHP: синтаксис, типы данных, функции и базовая работа с массивами.',
            ],
            [
                'reference' => self::COURSE_SYMFONY_START,
                'symbolCode' => 'symfony-start',
                'title' => 'Введение в Symfony',
                'description' => 'Практический курс по основам Symfony: структура проекта, маршруты, контроллеры, шаблоны и Doctrine.',
            ],
            [
                'reference' => self::COURSE_SQL_POSTGRESQL,
                'symbolCode' => 'sql-postgresql',
                'title' => 'Работа с PostgreSQL',
                'description' => 'Курс по базовым SQL-запросам, связям между таблицами, объединению данных и оптимизации запросов.',
            ],
            [
                'reference' => self::COURSE_WEB_DESIGN,
                'symbolCode' => 'web-design-basic',
                'title' => 'Основы веб-дизайна',
                'description' => 'Знакомство с принципами визуального оформления интерфейсов, типографикой, цветом и адаптивной версткой.',
            ],
            [
                'reference' => self::COURSE_PERSONAL_FINANCE,
                'symbolCode' => 'personal-finance',
                'title' => 'Личные финансы и бюджет',
                'description' => 'Курс о том, как вести личный бюджет, контролировать расходы, формировать накопления и планировать финансы.',
            ],
            [
                'reference' => self::COURSE_TIME_MANAGEMENT,
                'symbolCode' => 'time-management',
                'title' => 'Тайм-менеджмент',
                'description' => 'Практический курс по планированию времени, приоритизации задач и повышению личной эффективности.',
            ],
            [
                'reference' => self::COURSE_ENGLISH_BASIC,
                'symbolCode' => 'english-basic',
                'title' => 'Английский для начинающих',
                'description' => 'Базовый курс английского языка: простые времена, словарный запас, построение предложений и повседневное общение.',
            ],
        ];

        foreach ($coursesData as $courseData) {
            $course = new Course();
            $course->setSymbolCode($courseData['symbolCode']);
            $course->setTitle($courseData['title']);
            $course->setDescription($courseData['description']);

            $manager->persist($course);
            $this->addReference($courseData['reference'], $course);
        }

        $manager->flush();
    }
}