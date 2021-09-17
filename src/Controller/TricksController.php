<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use App\Repository\TrickRepository;
use App\Entity\Trick;
use App\Entity\TrickImages;
use App\Entity\TrickVideos;
use App\Service\TrickService;
use App\Form\TrickFormType;

use App\Entity\Comment;
use App\Entity\Category;
use App\Services\PaginationService;

#[Route('/tricks', name: 'tricks.')]
class TricksController extends AbstractController
{
    /**
     * TODO: ROUTES:
     * ? - /tricks/search PUBLIC (create animations)
     * ? - /tricks/details/{slug} PUBLIC (add comments form and pagination)
     * ? - /tricks/create PROTECTED (style form page)
     * * - /tricks/edit/{slug} PROTECTED
     * * - /tricks/delete/{slug} PROTECTED
     */

    private $flash;
    private $service;

    public function __construct(FlashBagInterface $flash, EntityManagerInterface $em)
    {
        $this->flash = $flash;
        $this->service = new TrickService($em, $flash);
    }
    
    #[Route("/details/{slug}", name: "details")]
    public function details(string $slug): Response
    {
        $trickRepo = $this->getDoctrine()->getRepository(Trick::class);
        $trick = $trickRepo->findOneBy(['slug' => $slug]);
        if (!$trick) {
            $this->flash->add("warning", "This trick does not exist !");
            return $this->redirectToRoute("home.index");
        }
        $created_at = $trick->getPostDate();
        $last_update = $trick->getLastUpdate();

        return $this->render("tricks/details.html.twig", [
            "trick" => [
                "id" => $trick->getId(),
                "author" => [
                    "id" => $trick->getAuthor()->getId(),
                    "username" => $trick->getAuthor()->getUsername(),
                ],
                "name" => $trick->getName(),
                "category" => $trick->getCategory(),
                "overview" => $trick->getOverview(),
                "description" => $trick->getDescription(),
                "slug" => $trick->getSlug(),
                "thumbnail" => $trick->getThumbnail(),
                "images" => $trick->getImages(),
                "videos" => "[]", /* $trick->getVideos() */
                "created_at" => $created_at->format('Y-m-d H:i:s'),
                "updated_at" => $last_update->format('Y-m-d H:i:s')
            ]
        ]);
    }
    
    #[Route('/create', name: 'create')]
    public function create(Request $request): Response
    {
        if (!$this->getUser()) {
            $this->flash->add("warning", "You must be logged in to access this page !");
            return $this->redirectToRoute('app_login');
        }
        
        $trick = new Trick();
        $em = $this->getDoctrine()->getManager();
        $repo = $this->getDoctrine()->getRepository(Trick::class);

        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trick = $form->getData();
            $this->service->createDir();
            $trick->setAuthor($this->getUser());
            $trick->setSlug($this->service->makeSlug($trick->getName()));
            $trick_uid = $this->service->saveTrick($trick);
            
            // Save Thumbnail
            $thumbnail_data = $form->get('thumbnail')->getData();
            if ($thumbnail_data != null) {
                $path = $this->service->saveFile($thumbnail_data, "/static/uploads/$trick_uid/thumbnail");
                $trick->setThumbnail($path);
            }
            
            // Validate then save illustration images
            $images_data = $form->get('images')->getData();
            if ($images_data != null) {
                if (!$this->service->checkAndSaveImages($images_data, $trick)) {
                    return $this->render("tricks/form.html.twig", [
                        "form" => $form->createView()
                    ]);
                }
            }
            
            // Validate videos, then save them
            $videos_data = $form->get("videos")->getData();
            if ($videos_data != null) {
                if (!$this->service->checkAndSaveVideos($videos_data, $trick)) {
                    return $this->render("tricks/form.html.twig", [
                        "form" => $form->createView()
                    ]);
                }
            }
            
            // Creation is done, redirect user towards new trick's details page
            $em->persist($trick);
            $em->flush();
            return $this->redirectToRoute('tricks.details', ['slug' => $trick_uid]);
        }

        return $this->render("tricks/form.html.twig", [
            "form" => $form->createView(),
            "trick_images" => [
                [
                    "id" => 1,
                    "src" => "/static/assets/header.jpg"
                ],
                [
                    "id" => 2,
                    "src" => "/static/assets/header.jpg"
                ],
                [
                    "id" => 3,
                    "src" => "/static/assets/header.jpg"
                ]
                ],
            "trick_videos" => [
                '<iframe width="480" height="360" src="https://www.youtube.com/embed/GMPjNA_fCj4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
                '<iframe width="480" height="360" src="https://www.youtube.com/embed/GMPjNA_fCj4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>',
                '<iframe width="480" height="360" src="https://www.youtube.com/embed/GMPjNA_fCj4" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                ]
        ]);
    }

    #[Route("/edit/{slug}", name: "edit")]
    public function edit(Request $request, string $slug): Response
    {
        if (!$this->getUser()) {
            $this->flash->add("warning", "You must be logged in to access this page !");
            return $this->redirectToRoute('app_login');
        }
        
        $trickRepo = $this->getDoctrine()->getRepository(Trick::class);
        $trick = $trickRepo->findOneBy(['slug' => $slug]);
        if (!$trick) {
            $this->flash->add("warning", "This trick does not exist !");
            return $this->redirectToRoute("home.index");
        }

        $form = $this->createForm(TrickFormType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trick = $form->getData();
            $this->service->createDir();
            $trick->setSlug($this->service->makeSlug($trick->getName()));
            
            // Save Thumbnail
            $thumbnail_data = $form->get('thumbnail')->getData();
            if ($thumbnail_data != null) {
                $this->service->deleteFile($slug . "/thumbnail");
                $path = $this->service->saveFile($thumbnail_data, "/static/uploads/$slug/thumbnail");
                $trick->setThumbnailPath($path);
            }
            
            // Validate then save illustration images
            $images_data = $form->get('images')->getData();
            if ($images_data != null) {
                $this->service->deleteFile($slug . "/images");
                $path = $this->service->checkAndSaveImages($images_data, $trick);
                if (!$path) {
                    return $this->render("tricks/form.html.twig", [
                        "form" => $form->createView()
                    ]);
                }
                $trick->setImagesPath($path);
            }
            
            // Validate videos, then save them
            $videos_data = $form->get("videos")->getData();
            if ($videos_data != null) {
                $videos = $this->service->checkAndSaveVideos($videos_data, $trick);
                if (!$videos) {
                    return $this->render("tricks/form.html.twig", [
                        "form" => $form->createView()
                    ]);
                }
                $trick->setVideos($videos);
            }
            
            // Creation is done, redirect user towards new trick's details page
            return $this->redirectToRoute('tricks.details', ['slug' => $this->service->saveTrick($trick)]);
        }

        return $this->render("tricks/form.html.twig", [
            "form" => $form->createView(),
            "videos" => $trick->getVideos(),
        ]);
    }

    #[Route("/delete/{slug}", name: "delete")]
    public function delete(string $slug): Response
    {
        if (!$this->getUser()) {
            $this->flash->add("warning", "You must be logged in to access this page !");
            return $this->redirectToRoute('app_login');
        }

        $trickRepo = $this->getDoctrine()->getRepository(Trick::class);
        $trick = $trickRepo->findOneBy(['slug' => $slug]);
        
        if (!$trick) {
            $this->flash->add("warning", "This trick does not exist !");
            return $this->redirectToRoute("home.index");
        }

        $em = $this->getDoctrine()->getManager();
        $this->service->deleteTrick($trick);
        $em->remove($trick);
        $em->flush();

        $this->flash->add("success", "Trick successfully deleted !");
        return $this->redirectToRoute("home.index");
    }
    
    #[Route('/search', name: 'search')]
    public function search(TrickRepository $repo, Request $request): Response
    {
        $form = $this->createFormBuilder([])
        ->add('search', SearchType::class, [
            "required" => false,
            "attr" => [
                "class" => "form-control",
                "placeholder" => "Search for a trick by keywords.."
            ]
        ])
        ->add('category', EntityType::class, [
            "required" => false,
            "attr" => [
                "class" => "form-control",
            ],
            "class" => Category::class,
            
        ])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $query = [];
            $query["query"] = $data["search"] ? $this->service->makeSlug($data["search"], "+") : null;
            if ($data["category"]) {
                $query["category"] = $data["category"]->getId();
            } else {
                $query["category"] = null;
            }
            return $this->redirectToRoute('tricks.search', [
                "query" => $query["query"],
                "category" => $query["category"]
            ]);
        }

        $query = $request->get("query");
        $category = $request->get("category");
        $page = $request->get("page") ?? 1;
        $tricks = [];
        if ($query || $category) {
            if (!$category) {
                $category = 0;
            }
            if (intval($page) <= 0) {
                $page = 1;
            }
            
            $query = preg_replace('/\+{2,}/', "+", $query);
            $query = str_replace("+", " ", $query);
            $trick_number = $repo->count(
                $query,
                $category,
                0,
                0,
                true
            );
            $page_service = new PaginationService();
            $pagination = $page_service->pagination($trick_number, $page, 10);
            $pagination_params = $pagination["params"];
            $pagination_controls = $pagination["controls"];

            $tricks = $repo->search($query, $category, $pagination_params["offset"], $pagination_params["limit"], false);
        }

        $tricks_list = [];
        foreach ($tricks as $trick) {
            $tricks_list[] = [
                "id" => $trick->getId(),
                "category" => [
                    "id" => $trick->getCategory()->getId(),
                    "name" => $trick->getCategory()->getName(),
                ],
                "author" => [
                    "id" => $trick->getAuthor()->getId(),
                    "username" => $trick->getAuthor()->getUsername(),
                    "profile_picture" => "/static/assets/avatars/default.png",
                    /* $trick->getAuthor()->getProfilePicture()
                    TODO Add users profile pictures*/
                ],
                "name" => $trick->getName(),
                "overview" => $trick->getOverview(),
                "thumbnail" => $trick->getThumbnailPath(),
                "slug" => $trick->getSlug(),
                "post_date" => $trick->getPostDate()->format("\\t\h\\e d/m/Y \a\\t h:m:s"),
                "last_update" => $trick->getLastUpdate()->format("\\t\h\\e d/m/Y \a\\t h:m:s"),
            ];
        }

        return $this->render("tricks/search.html.twig", [
            "form" => $form->createView(),
            "tricks" => $tricks_list
        ]);
    }
}
