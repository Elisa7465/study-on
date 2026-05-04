<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
use App\Repository\CourseRepository;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(BillingClient $billingClient): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || null === $user->getApiToken()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $response = $billingClient->getCurrentUser($user->getApiToken());
        } catch (BillingUnavailableException) {
            return $this->render('billing/unavailable.html.twig');
        }

        if (Response::HTTP_OK !== $response['code']) {
            return $this->render('billing/unavailable.html.twig');
        }

        $data = $response['data'];
        $roles = $data['roles'] ?? [];

        return $this->render('profile/index.html.twig', [
            'email' => $data['username'] ?? $user->getEmail(),
            'role' => in_array('ROLE_SUPER_ADMIN', $roles, true) ? 'Администратор' : 'Пользователь',
            'balance' => $data['balance'] ?? 0,
        ]);
    }

    #[Route('/profile/transactions', name: 'app_profile_transactions', methods: ['GET'])]
    public function transactions(
        BillingClient $billingClient,
        CourseRepository $courseRepository,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User || null === $user->getApiToken()) {
            throw $this->createAccessDeniedException();
        }

        try {
            $response = $billingClient->getTransactions($user->getApiToken());
        } catch (BillingUnavailableException) {
            return $this->render('billing/unavailable.html.twig');
        }

        if (Response::HTTP_OK !== ($response['code'] ?? 500)) {
            return $this->render('billing/unavailable.html.twig');
        }

        $transactions = $response['data'] ?? [];
        $courses = [];

        foreach ($transactions as $transaction) {
            if (!isset($transaction['course_code'])) {
                continue;
            }

            $course = $courseRepository->findOneBy([
                'symbolCode' => $transaction['course_code'],
            ]);

            if (null !== $course) {
                $courses[$transaction['course_code']] = $course;
            }
        }

        return $this->render('profile/transactions.html.twig', [
            'transactions' => $transactions,
            'courses' => $courses,
        ]);
    }
}