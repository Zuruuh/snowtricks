<?php

namespace App\Controller;

use App\Form\TrickFormType;
use App\Form\MessageFormType;
use App\Form\TrickSearchFormType;
use App\Service\TrickService;
use App\Service\MessageService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/tricks', name: 'tricks.')]
class TricksController extends AbstractController
{
    private TrickService $trick_service;
    private MessageService $message_service;

    const DATE_FORMAT = '\T\h\e\ d/m/Y \a\t H:i:s';

    public function __construct(
        TrickService $trick_service,
        MessageService $message_service,
    ) {
        $this->trick_service = $trick_service;
        $this->message_service = $message_service;
    }

    #[Route('/details/{slug}', name: 'details')]
    public function details(string $slug, Request $request): Response
    {
        $trick = $this->trick_service->exists($slug);
        $page = $request->query->get('page', 1);

        [$messages, $pagination] = $this->message_service->display(
            $page,
            $trick->getId()
        );

        $created_at = $trick->getPostDate()->format(self::DATE_FORMAT);
        $last_update = $trick->getLastUpdate()->format(self::DATE_FORMAT);

        $return_params = [
            'trick' => $trick,
            'created_at' => $created_at,
            'updated_at' => $last_update,
            'messages' => $messages,
            'pagination' => $pagination
        ];

        if (!$this->getUser()) {
            return $this->render('tricks/details.html.twig', $return_params);
        }

        $form = $this->createForm(MessageFormType::class);
        if ($form->isSubmitted() && $form->isValid()) {
            $route = $this->message_service->save(
                $form->get('content')->getData(),
                null
            );

            return $this->redirectToRoute($route);
        }
        $return_params['form'] = $form->createView();
        return $this->render(
            'tricks/details.html.twig',
            $return_params,
        );
    }

    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(TrickFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $route = $this->trick_service->save(
                $form->getData(),
                $form->get('thumbnail'),
                $form->get('videos'),
                $form->get('images'),
            );
            if ($route) {
                return $this->redirect($route);
            }
        }

        return $this->render('tricks/form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/edit/{slug}', name: 'edit')]
    public function edit(Request $request, string $slug): Response
    {
        $trick = $this->trick_service->exists($slug);

        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trick = $form->getData();
            $route = $this->trick_service->update(
                $trick,
                $form->get('thumbnail'),
                $form->get('videos'),
                $form->get('images'),
            );
            return $this->redirect($route);
        }
        $videos = $this->trick_service->getVideos($trick);

        return $this->render('tricks/form.html.twig', [
            'form' => $form->createView(),
            'videos' => json_encode($videos)
        ]);
    }

    #[Route('/delete/{slug}', name: 'delete')]
    public function delete(string $slug): Response
    {
        $trick = $this->trick_service->exists($slug);
        $route = $this->trick_service->delete($trick);

        return $this->redirect($route);
    }

    #[Route('/search', name: 'search')]
    public function search(Request $request): Response
    {
        $form = $this->createForm(TrickSearchFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $route = $this->trick_service->generateSearchRoute(
                $data['search'] ?? '',
                $data['category'] ?? 0
            );

            return $this->redirect($route);
        }

        $query = $request->query->get('query', '');
        $category = $request->query->getInt('category', 0);
        $page = $request->query->getInt('page', 1);

        $search = $this->trick_service->search(
            $query,
            $category,
            $page
        );

        return $this->render('tricks/search.html.twig', [
            'form' => $form->createView(),
            'tricks' => $search['tricks'],
            'pagination' => $search['pagination']
        ]);
    }
}
