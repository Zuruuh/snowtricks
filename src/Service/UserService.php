<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\MessageRepository;
use App\Repository\TrickRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;

class UserService
{
    private UserRepository $user_repo;
    private TrickRepository $trick_repo;
    private MessageRepository $message_repo;
    private RouterInterface $router;
    private EntityManagerInterface $em;
    private FlashBagInterface $flash;

    const USER_NOT_EXISTS = 'This user does not exists';
    const PROFILE_UPDATED = 'Your profile has been updated';

    const AVATARS_DIR = 'avatars/';
    const SELF_ROUTE = 'user.me';

    public function __construct(
        UserRepository $user_repo,
        TrickRepository $trick_repo,
        MessageRepository $message_repo,
        RouterInterface $router,
        EntityManagerInterface $em,
        FlashBagInterface $flash
    ) {
        $this->user_repo = $user_repo;
        $this->trick_repo = $trick_repo;
        $this->message_repo = $message_repo;
        $this->router = $router;
        $this->em = $em;
        $this->flash = $flash;
    }

    /**
     * Checks if a user exists
     *
     * @param int $id
     *
     * @return User $user
     */
    public function exists(int $id): User
    {
        $user = $this->user_repo->find($id);

        if (!$user) {
            throw new NotFoundHttpException(self::USER_NOT_EXISTS);
        }

        return $user;
    }

    /**
     * Returns a user's statistics
     *
     * @param User $user
     *
     * @return array ['tricks', 'messages']
     */
    public function getStats(User $user): array
    {
        $tricks_count = $this->trick_repo->countUserTricks($user);
        $messages_count = $this->message_repo->countUserMessages($user);
        return [
            'tricks' => $tricks_count,
            'messages' => $messages_count
        ];
    }

    /**
     * Updates an user's profile
     *
     * @param User          $user
     * @param UploadedFile $image_form
     *
     * @return void
     */
    public function update(User $user, UploadedFile $image): string
    {
        dump($image);
        $this->updateProfilePicture($user, $image);

        $this->flash->add('success', self::PROFILE_UPDATED);

        return $this->generateRoute();
    }

    /**
     * Updates an user's profile picture
     *
     * @param User          $user
     * @param UploadedFile $image
     *
     * @return void
     */
    public function updateProfilePicture(User $user, UploadedFile $image): void
    {
        $current_picture = $user->getProfilePicturePath();

        $image_name = $user->getId() . '.' . $image->guessExtension();

        if ($current_picture !== User::DEFAULT_PROFILE_PICTURE) {
            $this->deleteProfilePicture($current_picture);
        }
        $path = $this->saveProfilePicture($image, $image_name);
        $user->setProfilePicturePath($path);

        $this->em->persist($user);
        $this->em->flush();
    }

    /**
     * Deletes an image
     *
     * @param string $path
     *
     * @return void
     */
    private function deleteProfilePicture(string $path): void
    {
        $path = getcwd() . $path;

        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Saves an image
     *
     * @param UploadedFile $image
     * @param string       $name
     *
     * @return string $path
     */
    private function saveProfilePicture(UploadedFile $image, string $name): string
    {
        $path = TrickService::UPLOADS_DIR . self::AVATARS_DIR;
        $image->move(getcwd() . $path, $name);
        return $path . $name;
    }

    /**
     * Generates a return route
     *
     * @return string
     */
    private function generateRoute(): string
    {
        return $this->router->generate(self::SELF_ROUTE);
    }
}
