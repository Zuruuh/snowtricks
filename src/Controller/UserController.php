<?php

namespace App\Controller;

use App\Form\UserFormType;
use App\Repository\MessageRepository;
use App\Repository\TrickRepository;
use App\Repository\UserRepository;
use App\Service\FileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/user', name: 'user.')]
class UserController extends AbstractController
{
    private $flash;

    public function __construct(FlashBagInterface $flash)
    {
        $this->flash = $flash;
    }

    #[Route('/me', name: 'me')]
    public function me(TrickRepository $trick_repo, MessageRepository $message_repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->flash->add('warning', 'You must be logged in to view this page');

            return $this->redirectToRoute('auth.login');
        }
        $tricks_count = $trick_repo->countUserTricks($user);
        $messages_count = $message_repo->countUserMessages($user);

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'profile_picture' => $user->getProfilePicturePath(),
            'messages_count' => $messages_count,
            'tricks_count' => $tricks_count,
        ]);
    }

    #[Route('/profile/{user_id}', name: 'profile')]
    public function profile(
        Request $request,
        int $user_id,
        TrickRepository $trick_repo,
        UserRepository $user_repo,
        MessageRepository $message_repo
    ): Response {
        $user = $user_repo->findOneBy(['id' => intval($user_id)]);
        if (!$user) {
            $this->flash->add('warning', 'This user does not exists');

            return $this->redirectToRoute('home.index');
        }
        $tricks_count = $trick_repo->countUserTricks($user);
        $messages_count = $message_repo->countUserMessages($user);

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'profile_picture' => $user->getProfilePicturePath(),
            'messages_count' => $messages_count,
            'tricks_count' => $tricks_count,
        ]);
    }

    #[Route('/edit', name: 'edit')]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(UserFormType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('profile_picture')->getData();
            if ($image) {
                $file_service = new FileService();
                $image_name = $user->getId().'.'.$image->guessExtension();

                if ('/static/assets/avatars/default.png' !== $user->getProfilePicturePath()) {
                    $file_service->deleteFolder($user->getProfilePicturePath());
                }
                $path = $file_service->move($image, '/static/uploads/avatars', $image_name);
                $user->setProfilePicturePath($path);
                $em->persist($user);
                $em->flush();
            }
            $this->flash->add('success', 'Your profile has been updated');

            return $this->redirectToRoute('user.me');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
