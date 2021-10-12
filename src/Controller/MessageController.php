<?php

namespace App\Controller;

use App\Form\MessageFormType;
use App\Service\MessageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

#[Route('/chat', name: 'chat.')]
class MessageController extends AbstractController
{
    private MessageService $message_service;

    const INDEX = "chat.index";

    public function __construct(FlashBagInterface $flash, MessageService $message_service)
    {
        $this->flash = $flash;
        $this->message_service = $message_service;
    }

    #[Route('/', name: 'index')]
    public function index(Request $request): Response
    {
        $page = $request->get('page', 1);
        [$messages, $pagination] = $this->message_service->display($page, 0);

        if (!$this->getUser()) {
            return $this->render('chat/index.html.twig', [
                'messages' => $messages,
                'pagination' => $pagination
            ]);
        }

        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $route = $this->message_service->save(
                $form->get('content')->getData(),
                null
            );

            return $this->redirect($route);
        }

        return $this->render('chat/index.html.twig', [
            'messages' => $messages,
            'pagination' => $pagination,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(Request $request): Response
    {
        $id = $request->attributes->get('id', 0);
        $message = $this->message_service->isAuthorized($id);

        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $route = $this->message_service->update($message, $form->get('content')->getData());

            return $this->redirectToRoute($route);
        }

        return $this->render('chat/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Request $request): Response
    {
        $id = $request->attributes->get('id', 0);
        $message = $this->message_service->isAuthorized($id);

        $route = $this->message_service->delete($message);

        return $this->redirectToRoute($route);
    }
}
