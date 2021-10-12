<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Trick;
use App\Form\MessageFormType;
use App\Form\TrickFormType;
use App\Repository\TrickRepository;
use App\Service\MessageService;
use App\Service\PaginationService;
use App\Service\TrickService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tricks', name: 'tricks.')]
class TricksController extends AbstractController
{
    private FlashBagInterface $flash;
    private TrickService $trick_service;
    private MessageService $message_service;

    const DATE_FORMAT = '\T\h\e\ d/m/Y \a\t H:i:s';

    public function __construct(
        FlashBagInterface $flash,
        TrickService $trick_service,
        MessageService $message_service,
    ) {
        $this->flash = $flash;
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
            $route = $this->service->update(
                $trick,
                $form->get('thumbnail'),
                $form->get('videos'),
                $form->get('images'),
            );
            $trick->setSlug($this->service->makeSlug($trick->getName()));

            return $this->redirect($route);
        }

        $videos_entities = $trick->getVideos();
        $videos = [];
        foreach ($videos_entities as $video_entity) {
            $videos[] = [
                "id" => $video_entity->getId(),
                "url" => $video_entity->getUrl(),
                "provider" => $video_entity->getProvider()
            ];
        }
        dump($videos);


        return $this->render('tricks/form.html.twig', [
            'form' => $form->createView(),
            'videos' => json_encode($videos)
        ]);
    }

    #[Route('/delete/{slug}', name: 'delete')]
    public function delete(string $slug): Response
    {
        $trick = $this->service->exists($slug);
        $route = $this->service->delete($trick);

        return $this->redirect($route);
    }

    #[Route('/search', name: 'search')]
    public function search(TrickRepository $repo, Request $request, PaginationService $page_service): Response
    {
        $form = $this->createFormBuilder([])
            ->add('search', SearchType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Search for a trick by keywords..',
                ],
            ])
            ->add('category', EntityType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'class' => Category::class,
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $query = [];
            $query['query'] = $data['search'] ? $this->service->makeSlug($data['search'], '+') : null;
            if ($data['category']) {
                $query['category'] = $data['category']->getId();
            } else {
                $query['category'] = null;
            }

            return $this->redirectToRoute('tricks.search', [
                'query' => $query['query'],
                'category' => $query['category'],
            ]);
        }

        $query = $request->get('query');
        $category = $request->get('category');
        $page = $request->get('page', 1);
        $tricks = [];
        if ($query || $category) {
            if (!$category) {
                $category = 0;
            }
            if (intval($page) <= 0) {
                $page = 1;
            }

            $query = preg_replace('/\+{2,}/', '+', $query);
            $query = str_replace('+', ' ', $query);
            $trick_number = $repo->search(
                $query,
                $category,
                0,
                0,
                true
            );
            [$controls, $params] = $page_service->paginate($trick_number, $page, 10);

            $tricks = $repo->search(
                $query,
                $category,
                $params['offset'],
                $params['limit'],
                false
            );
        }

        $tricks_list = [];
        foreach ($tricks as $trick) {
            $tricks_list[] = [
                'id' => $trick->getId(),
                'category' => [
                    'id' => $trick->getCategory()->getId(),
                    'name' => $trick->getCategory()->getName(),
                ],
                'author' => [
                    'id' => $trick->getAuthor()->getId(),
                    'username' => $trick->getAuthor()->getUsername(),
                    'profile_picture' => $trick->getAuthor()->getProfilePicturePath(),
                ],
                'name' => $trick->getName(),
                'overview' => $trick->getOverview(),
                'thumbnail' => $trick->getThumbnail(),
                'slug' => $trick->getSlug(),
                'post_date' => $trick->getPostDate()->format("\\T\h\\e d/m/Y \a\\t h:m:s"),
                'last_update' => $trick->getLastUpdate()->format("\\T\h\\e d/m/Y \a\\t h:m:s"),
            ];
        }

        return $this->render('tricks/search.html.twig', [
            'form' => $form->createView(),
            'tricks' => $tricks_list,
        ]);
    }
}
