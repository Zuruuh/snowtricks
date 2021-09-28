<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use App\Services\PaginationService;

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

    private $flash;

    public function __construct(FlashBagInterface $flash)
    {
        $this->flash = $flash;
    }

    #[Route("/", name: "index")]
    public function index(Request $request, PaginationService $page_service): Response
    {
        $page = $request->get("page", 1);
        if (intval($page) <= 0) {
            $page = 1;
        }
        
        $msg_repo = $this->getDoctrine()->getRepository(Message::class);
        $total = $msg_repo->countPostMessages(0);

        [$controls, $params] = $page_service->paginate(
            $total,
            $page,
            10
        );

        $messages = $msg_repo->getMessages(0, $params["limit"], $params["offset"]);

        $form = $this->createForm(MessageFormType::class);
        $form->handleRequest($request);

        if ($this->getUser()) {
            $message = new Message();
            $form = $this->createForm(MessageFormType::class, $message);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $message->setAuthor($this->getUser());
                $message->setContent($form->get('content')->getData());
                $em = $this->getDoctrine()->getManager();
                $em->persist($message);
                $em->flush();
                return $this->redirectToRoute('chat.index');
            }
        }

        return $this->render('chat/index.html.twig', [
            "form" => $form->createView(),
            "messages" => $messages ?? null,
            "pagination" => $controls ?? null,
        ]);
    }

    #[Route("/edit/{id}", name: "edit")]
    public function edit(Request $request): Response
    {
        $id = $request->attributes->get('id', 0);

        if ((bool) !$id) {
            $this->flash->add('error', 'You must provide your message id in the url');
            return $this->redirectToRoute('chat.index');
        }
        
        $repo = $this->getDoctrine()->getRepository(Message::class);
        $message = $repo->find($id);

        if ((bool) !$message) {
            $this->flash->add('error', 'This message does not exist');
            return $this->redirectToRoute('chat.index');
        }

        if ((int) $message->getAuthor()->getId() !== (int) $this->getUser()->getId()) {
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

        return $this->render('chat/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route("/delete/{id}", name: "delete")]
    public function delete(Request $request): Response
    {
        $id = $request->attributes->get('id', 0);

        if ((bool) !$id) {
            $this->flash->add('error', 'You must provide your message id in the url');
            return $this->redirectToRoute('chat.index');
        }
        
        $repo = $this->getDoctrine()->getRepository(Message::class);
        $message = $repo->find($id);

        if ((bool) !$message) {
            $this->flash->add('error', 'This message does not exist');
            return $this->redirectToRoute('chat.index');
        }
        
        if ((int) $message->getAuthor()->getId() !== (int) $this->getUser()->getId()) {
            $this->flash->add('error', 'You are not allowed to delete this message');
            return $this->redirectToRoute('chat.index');
        }
        
        $em = $this->getDoctrine()->getManager();
        $trick = $message->getPost();
        $em->remove($message);
        $em->flush();
        $this->flash->add('success', 'Your message has been deleted !');
        if ($trick) {
            return $this->redirectToRoute('tricks.details', ['slug' => $trick->getSlug()]);
        }
        return $this->redirectToRoute('chat.index');
    }
}
