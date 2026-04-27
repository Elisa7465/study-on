<?php

namespace App\Controller;

use App\Dto\RegisterDto;
use App\Exception\BillingUnavailableException;
use App\Form\RegisterType;
use App\Security\BillingAuthenticator;
use App\Security\User;
use App\Service\BillingClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        BillingClient $billingClient,
        Security $security
    ): Response {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $registerDto = new RegisterDto();
        $form = $this->createForm(RegisterType::class, $registerDto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $registerResponse = $billingClient->register(
                    (string) $registerDto->getEmail(),
                    (string) $registerDto->getPassword()
                );
            } catch (BillingUnavailableException) {
                $this->addFlash('danger', 'Сервис временно недоступен. Попробуйте зарегистрироваться позднее');

                return $this->render('register/index.html.twig', [
                    'registration_form' => $form,
                ]);
            }

            if (
                !in_array($registerResponse['code'], [Response::HTTP_OK, Response::HTTP_CREATED], true)
                || !isset($registerResponse['data']['token'])
            ) {
                $this->addFlash('danger', $this->getBillingErrorMessage($registerResponse['data']));

                return $this->render('register/index.html.twig', [
                    'registration_form' => $form,
                ]);
            }

            $token = (string) $registerResponse['data']['token'];

            try {
                $currentUserResponse = $billingClient->getCurrentUser($token);
            } catch (BillingUnavailableException) {
                $this->addFlash('danger', 'Сервис временно недоступен. Попробуйте авторизоваться позднее');

                return $this->redirectToRoute('app_login');
            }

            if (Response::HTTP_OK !== $currentUserResponse['code']) {
                $this->addFlash('danger', 'Пользователь создан, но не удалось выполнить авторизацию');

                return $this->redirectToRoute('app_login');
            }

            $data = $currentUserResponse['data'];

            $user = new User();
            $user
                ->setEmail((string) $data['username'])
                ->setRoles($data['roles'] ?? [])
                ->setApiToken($token);

            if (isset($data['balance'])) {
                $user->setBalance((float) $data['balance']);
            }

            $security->login($user, BillingAuthenticator::class, 'main');

            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('register/index.html.twig', [
            'registration_form' => $form,
        ]);
    }

    private function getBillingErrorMessage(array $data): string
    {
        if (isset($data['message'])) {
            return (string) $data['message'];
        }

        if (isset($data['error'])) {
            return (string) $data['error'];
        }

        if (isset($data['errors']) && is_array($data['errors'])) {
            return implode(', ', array_map('strval', $data['errors']));
        }

        return 'Не удалось зарегистрироваться';
    }
}