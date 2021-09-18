<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use App\Form\MessageFormType;
use App\Entity\Message;

#[Route('/chat', name: 'chat.')]
class MessageController extends AbstractController
{

    /**
     * - /chat/
     * - /chat/edit/{id}
     * - /chat/delete/{id}
     */

    public function __construct(FlashBagInterface $flash)
    {
        $this->flash = $flash;
    }

    #[Route("/", name: "index")]
    public function index(Request $request): Response
    {
        //TODO: Implement global chat route here
    }

    #[Route("/edit/{id}", name: "edit")]
    public function edit(Request $request): Response
    {
        $id = $request->attributes->get('id');

        if (!$id) {
            $this->flash->add('error', 'You must provide your message id in the url');
            return $this->redirectToRoute('chat.index');
        }
        
        $repo = $this->getDoctrine()->getRepository(Message::class);
        $message = $repo->find($id);

        if (!$message) {
            $this->flash->add('error', 'This message does not exist');
            return $this->redirectToRoute('chat.index');
        }

        if ($message->getAuthor() !== $this->getUser()) {
            $this->flash->add('error', 'You are not allowed to edit this message');
            return $this->redirectToRoute('chat.index');
        }
        $form = $this->createForm(MessageFormType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message = $form->getData();
            $message->setLastUpdate(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush();
            $this->flash->add('success', 'Your message has been updated !');
            if ($message->getPost()) {
                return $this->redirectToRoute('tricks.details', ['slug' => $message->getPost()->getSlug()]);
            } else {
                return $this->redirectToRoute('chat.index');
            }
        }

        return $this->render('message/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
