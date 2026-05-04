<?php

namespace App\Controller;

use App\Entity\Course;
use App\Exception\BillingUnavailableException;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\BillingClient;
use App\Service\CourseAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courses')]
final class CourseController extends AbstractController
{
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(
        CourseRepository $courseRepository,
        CourseAccessService $courseAccessService,
    ): Response {
        $courses = $courseRepository->findBy([], ['title' => 'ASC']);

        try {
            $billingCourses = $courseAccessService->getBillingCourseMap();
        } catch (BillingUnavailableException) {
            return $this->render('billing/unavailable.html.twig');
        }

        $payments = [];
        $user = $this->getUser();

        if ($user instanceof \App\Security\User) {
            foreach ($courses as $course) {
                $payments[$course->getSymbolCode()] = $courseAccessService->getUserCoursePayment(
                    $user,
                    $course
                );
            }
        }

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
            'billing_courses' => $billingCourses,
            'payments' => $payments,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', [
                'id' => $course->getId(),
            ]);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(
        Course $course,
        CourseAccessService $courseAccessService,
    ): Response {
        $lessons = $course->getLessons()->toArray();

        usort($lessons, static fn ($left, $right) => $left->getSortOrder() <=> $right->getSortOrder());

        try {
            $billingCourse = $courseAccessService->getCourseBillingInfo($course);
        } catch (BillingUnavailableException) {
            return $this->render('billing/unavailable.html.twig');
        }

        $payment = null;
        $user = $this->getUser();

        if ($user instanceof \App\Security\User) {
            $payment = $courseAccessService->getUserCoursePayment($user, $course);
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'lessons' => $lessons,
            'billing_course' => $billingCourse,
            'payment' => $payment,
            'user_balance' => $user instanceof \App\Security\User ? $user->getBalance() : null,
        ]);
    }

    #[Route('/{id}/pay', name: 'app_course_pay', methods: ['GET'])]
    public function pay(
        Course $course,
        BillingClient $billingClient,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof \App\Security\User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $response = $billingClient->payCourse(
                $course->getSymbolCode(),
                $user->getApiToken()
            );
        } catch (BillingUnavailableException) {
            $this->addFlash('danger', 'Сервис временно недоступен');

            return $this->redirectToRoute('app_course_show', [
                'id' => $course->getId(),
            ]);
        }

        if (($response['code'] ?? 500) !== 200) {
            $this->addFlash(
                'danger',
                $response['data']['message'] ?? 'Ошибка оплаты курса'
            );

            return $this->redirectToRoute('app_course_show', [
                'id' => $course->getId(),
            ]);
        }

        $this->addFlash('success', 'Курс успешно оплачен');

        return $this->redirectToRoute('app_course_show', [
            'id' => $course->getId(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_show', [
                'id' => $course->getId(),
            ]);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index');
    }
}
