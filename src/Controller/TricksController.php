<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Trick;
use App\Service\TrickService;

use App\Entity\Comment;
use App\Entity\Category;
use App\Form\TrickType;

#[Route('/tricks', name: 'tricks.')]
class TricksController extends AbstractController
{
    /** 
     * TODO: ROUTES:
     * - /tricks/search PUBLIC
     * - /tricks/search/{query} PUBLIC
     * - /tricks/details/{slug} PUBLIC
     * * - /tricks/create PROTECTED 
     * - /tricks/edit/{slug} PROTECTED
     * - /tricks/edit/{slug} PROTECTED
     * - /tricks/delete/{slug} PROTECTED
     */

     private $flash;

    public function __construct(FlashBagInterface $flash, EntityManagerInterface $em)
    {
        $this->flash = $flash;
        $this->service = new TrickService($em, $flash);
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('tricks/index.html.twig', []);
    }
    
    #[Route('/search', name: 'search')]
    public function search(): Response
    {
        return $this->render("tricks/search.html.twig", []);
    }
    
    #[Route('/search/{query}', name: 'search.query')]
    public function searchQuery(): Response
    {
        return $this->render("tricks/search.query.html.twig", []);
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
        return $this->render("tricks/details.html.twig", [
            "slug" => $slug
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
        $trick_uid = uniqid();
        $em = $this->getDoctrine()->getManager();
        $repo = $this->getDoctrine()->getRepository(Trick::class);

        $form = $this->createForm(TrickType::class, $trick);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trick = $form->getData();
            $this->service->createDir();
            $trick->setAuthor($this->getUser());
            $trick->setSlug($this->service->makeSlug($trick->getName()));
            
            // Save Thumbnail
            $thumbnail_data = $form->get('thumbnail')->getData();
            if ($thumbnail_data != null) {
                $path = $this->service->saveFile($thumbnail_data, "/static/uploads/$trick_uid/thumbnail/");
                $trick->setThumbnailPath($path);
            }

            // Validate then save illustration images
            $images_data = $form->get('images')->getData();
            if ($images_data != null) {
                $path = $this->service->checkAndSaveImages($images_data);
                if (!$path) {
                    return $this->render("tricks/create.html.twig", [
                        "form" => $form->createView()
                    ]);
                }
                $trick->setImagesPath($path);
            }
            
            // Validate videos, then save them
            $videos_data = $form->get("videos")->getData();
            if ($videos_data != null) {
                $videos = $this->service->checkAndSaveVideos($videos_data);
                if (!$videos) {
                    return $this->render("tricks/create.html.twig", [
                        "form" => $form->createView()
                    ]);
                }
                $trick->setVideos($videos);
            }
            
            // Creation is done, redirect user towards new trick's details page
            return $this->redirectToRoute('tricks.details', ['slug' => $this->service->saveTrick($trick)]);
        }

        return $this->render("tricks/create.html.twig", [
            "form" => $form->createView()
        ]);
    }
}
