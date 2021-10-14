<?php

namespace App\Controller;

use App\Form\UserFormType;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user', name: 'user.')]
class UserController extends AbstractController
{
    private UserService $user_service;

    public function __construct(UserService $user_service)
    {
        $this->user_service = $user_service;
    }

    #[Route('/me', name: 'me')]
    public function me(): Response
    {
        $user = $this->getUser();

        $stats = $this->user_service->getStats($user);

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'profile_picture' => $user->getProfilePicturePath(),
            'messages_count' => $stats['messages'],
            'tricks_count' => $stats['tricks'],
        ]);
    }

    #[Route('/profile/{user_id}', name: 'profile')]
    public function profile(int $user_id,): Response
    {
        $user = $this->user_service->exists($user_id);

        $stats = $this->user_service->getStats($user);

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'profile_picture' => $user->getProfilePicturePath(),
            'messages_count' => $stats['messages'],
            'tricks_count' => $stats['tricks'],
        ]);
    }

    #[Route('/edit', name: 'edit')]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(UserFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $route = $this->user_service->update(
                $user,
                $form->get('profile_picture')->getData()
            );

            return $this->redirect($route);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
