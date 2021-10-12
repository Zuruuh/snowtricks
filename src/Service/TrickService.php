<?php

namespace App\Service;

use App\Entity\Trick;
use App\Entity\TrickImages;
use App\Entity\TrickVideos;
use App\Repository\TrickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class TrickService
{
    private EntityManagerInterface $em;
    private FileService $fileService;
    private TrickRepository $trick_repo;
    private RouterInterface $router;
    private Security $security;
    private FlashBagInterface $flash;

    const PROVIDERS = [
        'youtube',
        'youtu',
        'vimeo'
    ];
    const URL_REGEX = '/https:\/*|www\.|\.\w*|video\/|embed\/|player\.|watch\?v=/mix';
    const MAX_VIDEO = 3;
    const MAX_VIDEO_MESSAGE = 'You can only add up to 3 videos';
    const INVALID_VIDEO = 'Please only upload valid video data';
    const INVALID_PROVIDER = 'Please only upload videos from either Vimeo or Youtube';

    const MAX_IMAGES = 3;
    const SUPPORTED_FORMATS = ['image/jpeg', 'image/png', 'image/gif'];
    const MAX_IMAGE_MESSAGE = 'You can only add up to 3 images';
    const INVALID_FORMAT = 'Please only upload images using the .jpeg, .png, or .gif extension';
    const MAX_IMAGE_SIZE = 4000000;
    const INVALID_IMAGE_SIZE = 'Your images\' size must be less than 4mb per image!';
    const THUMBNAIL_NAME = 'thumbnail';

    const UPLOADS_DIR = '/static/uploads/';
    const IMAGES_DIR = 'images/';

    const DETAILS_ROUTE = 'tricks.details';
    const HOME_ROUTE = 'home.index';
    const SUCCESS_EDIT_MESSAGE = "This trick has been successfully edited !";
    const SUCCESS_CREATE_MESSAGE = "Your trick has been successfully created !";

    public function __construct(
        EntityManagerInterface $entityManager,
        FileService $file_service,
        TrickRepository $trick_repo,
        RouterInterface $router,
        Security $security,
        FlashBagInterface $flash,
    ) {
        $this->em = $entityManager;
        $this->fileService = $file_service;
        $this->trick_repo = $trick_repo;
        $this->router = $router;
        $this->security = $security;
        $this->flash = $flash;
    }

    /**
     * Checks if a trick exists
     * 
     * @param string $slug
     * 
     * @return Trick  $trick
     */
    public function exists(string $slug): Trick
    {
        $trick = $this->trick_repo->findOneBy(['slug' => $slug]);

        if (!$trick) {
            throw new NotFoundHttpException();
        }
        return $trick;
    }

    /**
     * Saves a trick to database
     * 
     * @param Trick         $trick
     * @param FormInterface $videos
     * @param FormInterface $images
     *  
     * @return string|bool $route
     */
    public function save(
        Trick $trick,
        FormInterface $thumbnail,
        FormInterface $videos,
        FormInterface $images
    ): string|bool {
        $trick->setAuthor($this->security->getUser());

        $trick = $this->updateSlug($trick);

        $statuses = [
            $this->setThumbnailImage($trick, $thumbnail),
            $this->setVideos($trick, $videos),
            $this->setImages($trick, $images)
        ];

        if (in_array(false, $statuses)) {
            return false;
        }

        $this->flash->add('success', self::SUCCESS_CREATE_MESSAGE);

        return $this->generateRoute($trick->getSlug());
    }

    /**
     * Updates a trick
     * 
     * @param Trick         $trick
     * @param FormInterface $thumbnail
     * @param FormInterface $videos
     * @param FormInterface $images
     * 
     * @return string|bool $route
     */
    public function update(
        Trick $trick,
        FormInterface $thumbnail,
        FormInterface $videos,
        FormInterface $images
    ): string|bool {
        $statuses = [
            $this->setThumbnailImage($trick, $thumbnail),
            $this->setVideos($trick, $videos),
            $this->setImages($trick, $images),
        ];

        if (in_array(false, $statuses)) {
            return false;
        }

        $this->flash->add('success', self::SUCCESS_EDIT_MESSAGE);
        return $this->generateRoute($trick->getSlug());
    }

    /**
     * Updates a trick's slug
     * 
     * @param Trick $trick
     * 
     * @return Trick
     */
    private function updateSlug(Trick $trick): Trick
    {
        $trick->setSlug(uniqid());

        $this->em->persist($trick);
        $this->em->flush();

        $slug = $this->makeSlug($trick->getName(), "-");
        $trick->setSlug($trick->getId() . "-" . $slug);

        $this->em->persist($trick);
        $this->em->flush();

        return $trick;
    }

    /**
     * Saves a trick's thumbnail to local storage & adds it to the trick
     * 
     * @param Trick         $trick
     * @param FormInterface $form_thumbnail
     * 
     * @return bool $status
     */
    private function setThumbnailImage(Trick $trick, FormInterface $form_thumbnail): bool
    {
        $thumbnail = $form_thumbnail->getData();
        if (!$thumbnail) {
            return true;
        }
        $path = $this->saveImage($thumbnail, $trick->getSlug(), self::THUMBNAIL_NAME);

        $trick->setThumbnail($path);

        $this->em->persist($trick);
        $this->em->flush();

        return true;
    }

    /**
     * Creates TrickVideos and add them to the trick
     * 
     * @param Trick         $trick
     * @param FormInterface $formVideos
     * 
     * @return bool $status
     */
    private function setVideos(Trick $trick, FormInterface $form_videos): bool
    {
        $videos = $form_videos->getData();
        if (!$videos) {
            return true;
        }
        $this->removeVideos($trick);
        if (sizeof($videos) > self::MAX_VIDEO) {
            $form_videos->addError(new FormError(self::MAX_VIDEO_MESSAGE));
            return false;
        }
        $data = array_map(function ($video) {
            return $this->checkVideo((string) $video);
        }, $videos);

        if ($this->validateErrors($form_videos, $data)) {
            return false;
        }

        foreach ($data as $video_data) {
            $video = (new TrickVideos())
                ->setTrick($trick)
                ->setProvider($video_data['provider'])
                ->setUrl($video_data['id']);
            $this->em->persist($video);
            $trick->addVideo($video);
        }
        return true;
    }

    /**
     * Removes all videos from a trick
     * 
     * @param Trick $trick
     * 
     * @return void
     */
    private function removeVideos(Trick $trick): void
    {
    }

    private function removeImages(Trick $trick): void
    {
    }

    /**
     * Takes in a video url and returns either an error or true
     * 
     * @param string $video
     * 
     * @return FormError|array $status
     */
    private function checkVideo(string $video): FormError|array
    {
        if (!$video) {
            return new FormError(self::INVALID_VIDEO);
        }
        $url = preg_replace(self::URL_REGEX, '', $video);
        [$provider, $id] = explode('/', $url);

        if (!in_array($provider, self::PROVIDERS)) {
            return new FormError(self::INVALID_PROVIDER);
        }

        $provider = $provider === "youtu" ? "youtube" : $provider;

        return ["provider" => $provider, "id" => $id];
    }

    /**
     * Creates TrickImages and adds them to the trick
     * 
     * @param Trick         $trick
     * @param FormInterface $form_images
     * 
     * @return bool
     */
    private function setImages(Trick $trick, FormInterface $form_images): bool
    {
        $images = $form_images->getData();
        if (!$images) {
            return true;
        }
        if (sizeof($images) > self::MAX_IMAGES) {
            $form_images->addError(new FormError(self::MAX_IMAGE_MESSAGE));
            return false;
        }

        $data = array_map(function ($image) {
            return $this->checkImage($image);
        }, $images);

        if ($this->validateErrors($form_images, $data)) {
            return false;
        }

        $this->saveImages($trick, $images);

        return "home.index";
    }

    /**
     * Checks images to make sure they are valid
     * 
     * @param mixed $image
     * 
     * @return bool|FormError
     */
    private function checkImage(mixed $image): bool|FormError
    {
        if (!in_array($image->getMimeType(), self::SUPPORTED_FORMATS)) {
            return new FormError(self::INVALID_FORMAT);
        }

        if ($image->getSize() > self::MAX_IMAGE_SIZE) {
            return new FormError(self::INVALID_IMAGE_SIZE);
        }

        return true;
    }

    /**
     * Saves images to trick
     * 
     * @param Trick $trick
     * @param array $images
     * 
     * @return string[] $paths
     */
    private function saveImages(Trick $trick, array $images): array
    {
        return array_map(function ($image) use ($trick) {
            $path = $this->saveImage($image, $trick->getSlug());

            $videoImage = (new TrickImages())
                ->setTrick($trick)
                ->setPath($path);

            $this->em->persist($videoImage);
            $this->em->flush();

            $trick->addImages($videoImage);
        }, $images);
    }

    /**
     * Saves an image to it's trick folder
     * 
     * @param UploadedFile  $image
     * @param string        $path
     * @param string?       $name
     * 
     * @return string $path
     */
    private function saveImage(UploadedFile $image, string $path, string $name = '')
    {
        $dir = getcwd() . self::UPLOADS_DIR . self::IMAGES_DIR;

        $name = $name ?? uniqid();
        $name = $name . '.' . $image->guessExtension();

        $path = $path . '/';

        mkdir($dir . $path, 0777, true);
        $image->move($path, $name);

        return $path . $name;
    }
    /**
     * Adds error to form and returns false if there was at least one
     * 
     * @param FormInterface $form
     * @param array         $datas
     * 
     * @return bool
     */
    private function validateErrors(FormInterface $form, array $datas): bool
    {
        foreach ($datas as $data) {
            if ($data instanceof FormError) {
                $form->addError($data);
            }
        }

        return in_array(FormError::class, $datas);
    }

    public function makeSlug(string $name, string $separator = '-'): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/\s+|\W/', $separator, $slug);
        $regex = '/\\' . $separator . '{2,}/';
        $slug = preg_replace($regex, $separator, $slug);

        return trim($slug, $separator);
    }

    /**
     * Generates a redirect route and returns it
     * 
     * @param string $slug
     * 
     * @return string $route
     */
    private function generateRoute(string $slug): string
    {
        $slug;
        return $this->router->generate(
            self::DETAILS_ROUTE,
            ['slug' => $slug]
        );
        /*return $this->router->generate(
            self::HOME_ROUTE
        );*/
    }

    /**
     * Deletes a trick and it's related images
     * 
     * @param Trick $trick
     * 
     * @return string $route
     */
    public function delete(Trick $trick): string
    {

        $folder = self::UPLOADS_DIR . $trick->getSlug();
        $this->fileSystem->remove(getcwd() . $folder);

        foreach ($trick->getImages() as $image) {
            $this->em->remove($image);
        }

        foreach ($trick->getVideos() as $video) {
            $this->em->remove($video);
        }

        foreach ($trick->getMessages() as $message) {
            $this->em->remove($message);
        }

        $this->em->remove($trick);
        $this->em->flush();

        return $this->router->generate(self::HOME_ROUTE);
    }
}
