<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;
use App\Entity\TrickImages;
use App\Entity\TrickVideos;

use App\Entity\Trick;
use App\Service\FileService;

class TrickService
{
    private $em;
    private FileService $fileService;
    private FlashBagInterface $flash;
    private Filesystem $filesystem;
    private $providers;

    public function __construct($entityManager, FlashBagInterface $flash)
    {
        $this->em = $entityManager;
        $this->flash = $flash;
        $this->filesystem = new Filesystem();
        $this->fileService = new FileService();
        $this->providers = [
            "youtube",
            "youtu",
            "vimeo",
        ];
    }

    public function saveTrick(Trick $trick): string
    {
        $this->em->persist($trick);
        $this->em->flush();
        $trick->setSlug($trick->getId() . '-' . $trick->getSlug());
        $this->em->flush();
        $this->flash->add("success", "Trick created successfully !");
        return $trick->getSlug();
    }

    public function makeSlug(string $name, string $separator = "-"): string
    {
        $slug = strtolower($name);
        $slug = preg_replace('/\s+|\W/', $separator, $slug);
        $regex = "/\\" . $separator . "{2,}/";
        $slug = preg_replace($regex, $separator, $slug);
        return trim($slug, $separator);
    }

    public function createDir(): void
    {
        $this->fileService->createFolder("/static/");
        $this->fileService->createFolder("/static/uploads/");
    }

    public function saveFile(UploadedFile $file, string $path): string
    {
        $file_name = uniqid() . '.' . $file->guessExtension();

        $this->fileService->createFolder($path);

        return $this->fileService->move($file, $path, $file_name);
    }

    public function checkAndSaveImages(array $images_data, Trick $trick): bool
    {
        $trick_uid = $trick->getSlug();

        if (!is_array($images_data)) {
            $images_data = [$images_data];
        }

        if (sizeof($images_data) > 3) {
            $this->flash->add("warning", "You can only upload 3 images !");
            return false;
        }
        $images_path = [];
        foreach ($images_data as $image) {
            // 1 - Check if the image is a valid image
            $correct_format = false;
            $supported_format = ['image/jpeg', 'image/png', 'image/gif'];
            foreach ($supported_format as $format) {
                if ($image->getMimeType() === $format) {
                    $correct_format = true;
                }
            }
            if ($correct_format === false) {
                $this->flash->add("warning", "You can only upload images with the following formats: jpeg, png, gif !");
                return false;
            }
            
            // 2 - Check if the image's size is less than 4mb
            
            if ($image->getSize() > 4000000) {
                $this->flash->add("warning", "Your images' size must be less than 4mb per image!");
                return false;
            }
            // 3 - Images are valid, save them
            $images_path[] = $this->saveFile($image, "/static/uploads/$trick_uid/images");
        }
        foreach ($images_path as $image) {
            $trick_images = new TrickImages();
            $trick_images->setTrick($trick);
            $trick_images->setPath($image);
            $this->em->persist($trick_images);
            $trick->addImages($trick_images);
        }
        $this->em->flush();
        return true;
    }

    public function checkAndSaveVideos($videos_data, Trick $trick): bool
    {
        // 2 - Check if user sent more than 3 videos
        if (sizeof($videos_data) > 3) {
            $this->flash->add("warning", "You can only add up to 3 videos !");
            return false;
        }

        // 3 - Validate each video individually
        foreach ($videos_data as $video_data) {
            if (gettype($video_data) !== "string") {
                $this->flash->add("warning", "Please upload valid data");
            }

            $url = preg_replace("/https:\/*|www\.|\.\w*|video\/|embed\/|player\.|watch\?v=/mix", "", $video_data);
            [$provider, $id] = explode("/", $url);
            
            if (!in_array($provider, $this->providers)) {
                $this->flash->add("warning", "Please only upload videos from Youtube or Vimeo");
                return false;
            }

            if ($provider === "youtu") {
                $provider = "youtube";
            }

            // 4 - Verifications are done, save videos
            $video = (new TrickVideos())
                ->setTrick($trick)
                ->setProvider($provider)
                ->setUrl($id);
            $this->em->persist($video);
            $trick->addVideo($video);
        }
        $this->em->flush();
        return true;
    }

    public function deleteTrick(Trick $trick): void
    {
        $this->fileService->deleteFolder("/static/uploads/" . $trick->getSlug());
        foreach ($trick->getImages() as $image) {
            $this->em->remove($image);
        }
        foreach ($trick->getVideos() as $video) {
            $this->em->remove($video);
        }
        foreach ($trick->getMessages() as $message) {
            $this->em->remove($message);
        }
    }

    public function deleteFile(string $path): void
    {
        $this->fileService->deleteFolder("/static/uploads/" . $path);
    }
}
