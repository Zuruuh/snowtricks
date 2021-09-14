<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Filesystem\Filesystem;

use App\Entity\Trick;
use App\Service\FileService;

class TrickService
{
    private $em;
    private FileService $fileService;
    private FlashBagInterface $flash;
    private Filesystem $filesystem;

    public function __construct($entityManager, FlashBagInterface $flash)
    {
        $this->em = $entityManager;
        $this->flash = $flash;
        $this->filesystem = new Filesystem();
        $this->fileService = new FileService();
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
        $slug = preg_replace('/\-{2,}/', $separator, $slug);
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

    public function checkAndSaveImages(array $images_data, string $trick_uid): array
    {
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
        return $images_path;
    }

    public function checkAndSaveVideos($videos_data)
    {
        $videos_data = json_decode($videos_data, true);
        // 1 - Verify data integrity
        if (!is_array($videos_data)) {
            $this->flash->add("warning", "Please only upload valid videos data");
            return false;
        }
        // 2 - Check if user sent more than 3 videos
        if (sizeof($videos_data) > 3) {
            $this->flash->add("warning", "You can only add up to 3 videos !");
            return false;
        }
        // 3 - Validate each video individually
        $videos = [];

        foreach ($videos_data as $video) {
            // 3.1 - Check if array has more than 2 properties (id & service)
            if (sizeof($video) > 2) {
                $this->flash->add("warning", "Please only upload valid videos data");
                return false;
            }
            
            // 3.2 - Check if the 2 existing properties are "service" and "id"
            if (!array_key_exists("id", $video) || !array_key_exists("service", $video)) {
                $this->flash->add("warning", "Please only upload valid videos data");
                return false;
            }

            // 3.3 - Check if id is valid
            if (strlen($video["id"]) > 16 || strlen($video["id"]) == 0) {
                $this->flash->add("warning", "Please only upload valid videos data");
                return false;
            }

            // 3.4 - Check if service is valid
            switch (strtolower($video["service"])) {
                case "youtube":
                case "dailymotion":
                case "vimeo":
                    break;
                default:
                    $this->flash->add("warning", "Please only upload valid videos data");
                    return false;
                    break;
            }

            // 3.5 - Add video to array
            $videos[] = ["service" => $video["service"], "id" => $video["id"]];
        }
            // 4 - Verifications are done, save videos
        return json_encode($videos);
    }

    public function deleteTrick(Trick $trick): void
    {
        $this->fileService->deleteFolder("/static/uploads/" . $trick->getSlug());
    }

    public function deleteFile(string $path): void
    {
        dump($path);
        $this->fileService->deleteFolder("/static/uploads/" . $path);
    }
}
