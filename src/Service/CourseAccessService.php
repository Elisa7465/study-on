<?php

namespace App\Service;

use App\Entity\Course;
use App\Security\User;

final readonly class CourseAccessService
{
    public function __construct(
        private BillingClient $billingClient,
    ) {
    }

    public function getBillingCourseMap(): array
    {
        $response = $this->billingClient->getCourses();

        if (($response['code'] ?? 500) !== 200) {
            return [];
        }

        $courses = $response['data'] ?? [];
        $map = [];

        foreach ($courses as $course) {
            if (!isset($course['code'])) {
                continue;
            }

            $map[$course['code']] = $course;
        }

        return $map;
    }

    public function getCourseBillingInfo(Course $course): array
    {
        $billingCourses = $this->getBillingCourseMap();
        $code = $course->getSymbolCode();

        return $billingCourses[$code] ?? [
            'code' => $code,
            'type' => 'free',
        ];
    }

    public function isFree(Course $course): bool
    {
        $billingInfo = $this->getCourseBillingInfo($course);

        return !isset($billingInfo['type']) || 'free' === $billingInfo['type'];
    }

    public function getUserCoursePayment(User $user, Course $course): ?array
    {
        $response = $this->billingClient->getTransactions($user->getApiToken(), [
            'type' => 'payment',
            'course_code' => $course->getSymbolCode(),
            'skip_expired' => true,
        ]);

        if (($response['code'] ?? 500) !== 200) {
            return null;
        }

        $transactions = $response['data'] ?? [];

        return $transactions[0] ?? null;
    }

    public function hasAccessToCourse(?User $user, Course $course): bool
    {
        if ($this->isFree($course)) {
            return true;
        }

        if (null === $user) {
            return false;
        }

        return null !== $this->getUserCoursePayment($user, $course);
    }
}