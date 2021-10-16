<?php

namespace App\Service;

use App\Entity\Category;
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
    private PaginationService $page_service;
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
    const SEARCH_ROUTE = 'tricks.search';
    const HOME_ROUTE = 'home.index';
    const MAX_PER_PAGE = 10;

    const SUCCESS_EDIT_MESSAGE = 'This trick has been successfully edited !';
    const SUCCESS_CREATE_MESSAGE = 'Your trick has been successfully created !';

    public function __construct(
        EntityManagerInterface $entityManager,
        TrickRepository $trick_repo,
        RouterInterface $router,
        Security $security,
        FlashBagInterface $flash,
        PaginationService $page_service
    ) {
        $this->em = $entityManager;
        $this->trick_repo = $trick_repo;
        $this->router = $router;
        $this->security = $security;
        $this->flash = $flash;
        $this->page_service = $page_service;
    }

    /**
     * Checks if a trick exists.
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
     * Saves a trick to database.
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

        $this->updateSlug($trick);
        $this->createDir($trick->getSlug());

        $statuses = [
            $this->setThumbnailImage($trick, $thumbnail),
            $this->setVideos($trick, $videos),
            $this->setImages($trick, $images),
        ];

        if (in_array(false, $statuses)) {
            return false;
        }

        $this->flash->add('success', self::SUCCESS_CREATE_MESSAGE);

        return $this->generateRoute($trick->getSlug());
    }

    /**
     * Updates a trick.
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
        $slug = $trick->getSlug();
        $statuses = [
            $this->setThumbnailImage($trick, $thumbnail),
            $this->setVideos($trick, $videos),
            $this->setImages($trick, $images),
        ];

        $this->updateSlug($trick);

        $this->move($slug, $trick->getSlug());

        $trick = $this->updateImages($trick);

        if (in_array(false, $statuses)) {
            return false;
        }

        $this->flash->add('success', self::SUCCESS_EDIT_MESSAGE);

        return $this->generateRoute($trick->getSlug());
    }

    /**
     * Updates a trick's slug.
     *
     * @param Trick $trick
     *
     * @return void
     */
    private function updateSlug(Trick $trick): void
    {
        $trick->setSlug(uniqid());

        $this->em->persist($trick);
        $this->em->flush();

        $slug = $this->makeSlug($trick->getName(), '-');
        $trick->setSlug($trick->getId() . '-' . $slug);

        $this->em->persist($trick);
        $this->em->flush();
    }

    /**
     * Renames a trick's directory.
     *
     * @param string $old_slug
     * @param string $slug
     *
     * @return void
     */
    private function move(string $old_slug, string $slug): void
    {
        $dir = getcwd() . self::UPLOADS_DIR . self::IMAGES_DIR;
        rename($dir . $old_slug, $dir . $slug);
    }

    /**
     * Saves a trick's thumbnail to local storage & 
     * adds it to the trick.
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
        if ($trick->getThumbnail() !== Trick::DEFAULT_THUMBNAIL) {
            $this->removeThumbnail($trick);
        }
        $path = $this->saveImage($thumbnail, $trick->getSlug(), self::THUMBNAIL_NAME);

        $trick->setThumbnail($path);

        $this->em->persist($trick);
        $this->em->flush();

        return true;
    }

    /**
     * Updates the path of a trick's images & thumbnail.
     *
     * @param Trick $trick
     *
     * @return Trick $self
     */
    private function updateImages(Trick $trick): Trick
    {
        $thumbnail = $trick->getThumbnail();
        $slug = $trick->getSlug();

        $trick->setThumbnail(
            $this->generateNewPath(
                $slug,
                $thumbnail
            )
        );

        $images = $trick->getImages();

        foreach ($images as $image) {
            $path = $this->generateNewPath($slug, $image->getPath());
            $trick->removeImages($image);
            $this->em->remove($image);

            $trick_image = (new TrickImages())
                ->setPath($path)
                ->setTrick($trick);
            $this->em->persist($trick_image);
            $trick->addImages($trick_image);
        }

        $this->em->flush();

        return $trick;
    }

    /**
     * Generates a new path for an image and returns it.
     *
     * @param string $slug
     * @param string $path
     *
     * @return string $newPath
     */
    private function generateNewPath(string $slug, string $path): string
    {
        $path = explode('/', $path);
        $path[count($path) - 2] = $slug;

        return join('/', $path);
    }

    /**
     * Creates TrickVideos and add them to the trick.
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
     * Removes all videos from a trick.
     *
     * @param Trick $trick
     *
     * @return void
     */
    private function removeVideos(Trick $trick): void
    {
        $videos = $trick->getVideos();
        foreach ($videos as $video) {
            $this->em->remove($video);
        }
        $this->em->flush();
    }

    /**
     * Deletes all images from a trick.
     *
     * @param Trick $trick
     *
     * @return void
     */
    private function removeImages(Trick $trick): void
    {
        $images = $trick->getImages();
        foreach ($images as $image) {
            unlink(getcwd() . $image->getPath());
            $this->em->remove($image);
        }
        $this->em->flush();
    }

    /**
     * Deletes a trick's thumbnail.
     *
     * @param Trick $trick
     *
     * @return void
     */
    private function removeThumbnail(Trick $trick): void
    {
        $thumbnail = $trick->getThumbnail();
        unlink(getcwd() . $thumbnail);
    }

    /**
     * Takes in a video url and returns either an error or true.
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

        $provider = $provider === 'youtu' ? 'youtube' : $provider;

        return ['provider' => $provider, 'id' => $id];
    }

    /**
     * Creates TrickImages and adds them to the trick.
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

        return 'home.index';
    }

    /**
     * Checks images to make sure they are valid.
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
     * Saves images to trick.
     *
     * @param Trick $trick
     * @param array $images
     *
     * @return string[] $paths
     */
    private function saveImages(Trick $trick, array $images): array
    {
        $this->removeImages($trick);

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
     * Saves an image to it's trick folder.
     *
     * @param UploadedFile  $image
     * @param string        $path
     * @param string?       $name
     *
     * @return string $path
     */
    private function saveImage(UploadedFile $image, string $path, string $name = '')
    {
        $dir = self::UPLOADS_DIR . self::IMAGES_DIR;
        $filename = $name == '' ? uniqid() : $name;
        $filename .= '.' . $image->guessExtension();

        $absolute_path = getcwd() .  $dir . $path . '/';
        $path = $dir . $path . '/';

        if (!file_exists($absolute_path)) {
            mkdir($absolute_path, 0777, true);
        }

        dump($path);
        $image->move($absolute_path, $filename);

        return $path . $filename;
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

                return true;
            }
        }

        return false;
    }

    /**
     * Creates a slug from a string.
     *
     * @param string $name
     * @param string? $separator
     *
     * @return string $slug
     */
    public function makeSlug(string $name, string $separator = '-'): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/\s+|\W/', $separator, $slug);
        $regex = '/\\' . $separator . '{2,}/';
        $slug = preg_replace($regex, $separator, $slug);

        return trim($slug, $separator);
    }

    public function getVideos(Trick $trick): array
    {
        $videos = $trick->getVideos()->toArray();
        return array_map(function ($video) {
            return [
                'id' => $video->getId(),
                'url' => $video->getUrl(),
                'provider' => $video->getProvider(),
            ];
        }, $videos);
    }

    /**
     * Generates a redirect route and returns it.
     *
     * @param string $slug
     *
     * @return string $route
     */
    private function generateRoute(string $slug): string
    {
        return $this->router->generate(
            self::DETAILS_ROUTE,
            ['slug' => $slug]
        );
        /*return $this->router->generate(
            self::HOME_ROUTE
        );*/
    }

    /**
     * Deletes a trick and it's related images.
     *
     * @param Trick $trick
     *
     * @return string $route
     */
    public function delete(Trick $trick): string
    {

        $folder = self::UPLOADS_DIR . $trick->getSlug();
        unlink(getcwd() . $folder);

        $entitiesList = [
            $trick->getImages(),
            $trick->getVideos(),
            $trick->getMessages(),
        ];

        foreach ($entitiesList as $entities) {
            foreach ($entities as $entity) {
                $this->em->remove($entity);
            }
        }

        $this->em->remove($trick);
        $this->em->flush();

        return $this->router->generate(self::HOME_ROUTE);
    }

    /**
     * Creates a trick's directory.
     *
     * @param string $slug
     *
     * @return void
     */
    private function createDir(string $slug): void
    {
        $dir = getcwd() . self::UPLOADS_DIR . self::IMAGES_DIR;
        mkdir($dir . $slug, 0777, true);
    }

    /**
     * Generates a search route after a form submit.
     *
     * @param string   $search
     * @param Category? $category
     *
     * @return string $route
     */
    public function generateSearchRoute(string $search, mixed $category): string
    {
        $params['query'] = $this->makeSlug($search, '+');
        if ($category) {
            $params['category'] = $category->getId();
        }
        return $this->router->generate(self::SEARCH_ROUTE, $params);
    }

    /**
     * Search for tricks and returns them.
     *
     * @param string $query
     * @param int    $category
     * @param int    $page
     *
     * @return array
     */
    public function search(string $query, int $category, int $page): array
    {
        $query = preg_replace('/\+{2,}/', '+', $query);
        $query = str_replace('+', ' ', $query);

        $count = $this->trick_repo->search(
            $query,
            $category,
            0,
            0,
            true
        );
        [$controls, $params] = $this->page_service->paginate($count, $page, self::MAX_PER_PAGE);
        $tricks = $this->trick_repo->search(
            $query,
            $category,
            $params['offset'],
            $params['limit'],
            false
        );

        return [
            'tricks' => $tricks,
            'pagination' => $controls,
        ];
    }
}
