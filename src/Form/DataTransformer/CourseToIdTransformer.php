<?php

namespace App\Form\DataTransformer;

use App\Entity\Course;
use App\Repository\CourseRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CourseToIdTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly CourseRepository $courseRepository,
    ) {
    }

    public function transform(mixed $value): string
    {
        if (!$value instanceof Course) {
            return '';
        }

        return (string) $value->getId();
    }

    public function reverseTransform(mixed $value): ?Course
    {
        if (!$value) {
            return null;
        }

        $course = $this->courseRepository->find($value);

        if (!$course instanceof Course) {
            throw new TransformationFailedException('Выбранный курс не найден');
        }

        return $course;
    }
}