<?php

namespace App\Controller;

use App\Exception\BillingUnavailableException;
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
            $this->addFlash('danger', 'Сервис временно недоступен');

            return $this->redirectToRoute('app_course_index');
        }

        if (Response::HTTP_OK !== $response['code']) {
            throw $this->createAccessDeniedException();
        }

        $data = $response['data'];
        $roles = $data['roles'] ?? [];

        return $this->render('profile/index.html.twig', [
            'email' => $data['username'] ?? $user->getEmail(),
            'role' => in_array('ROLE_SUPER_ADMIN', $roles, true) ? 'Администратор' : 'Пользователь',
            'balance' => $data['balance'] ?? 0,
        ]);
    }
}