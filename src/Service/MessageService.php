<?php

namespace App\Service;

use App\Controller\MessageController;
use App\Entity\Message;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class MessageService
{
    private PaginationService $page_service;
    private EntityManagerInterface $em;
    private FlashBagInterface $flash;
    private MessageRepository $message_repo;
    private Security $security;
    private RouterInterface $router;

    const MESSAGES_PER_PAGE = 10;
    const UNAUTHORIZED = 'You are not the author of this message !';

    public function __construct(
        PaginationService $page_service,
        EntityManagerInterface $em,
        FlashBagInterface $flash,
        MessageRepository $message_repo,
        Security $security,
        RouterInterface $router
    ) {
        $this->page_service = $page_service;
        $this->em = $em;
        $this->flash = $flash;
        $this->message_repo = $message_repo;
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * Calculates messages pagination and returns it
     *
     * @param int   $page
     * @param int   $trick_id
     * 
     * @return Array [$controls, $params]
     */
    public function paginate(int $page, $trick_id = 0): array
    {
        $total = $this->message_repo->countPostMessages($trick_id);
        return $this->page_service->paginate(
            $total,
            $page,
            self::MESSAGES_PER_PAGE
        );
    }

    /**
     * Returns the current page of messages
     * 
     * @param int        $page
     * @param int        $trick_id
     * 
     * @return Messages[] $messages
     */
    public function get(int $page, $trick_id = 0): array
    {
        $pagination = $this->paginate($page)[1];
        $messages = $this->message_repo->getMessages(
            $trick_id,
            $pagination['limit'],
            $pagination['offset']
        );
        return $messages ?? [];
    }

    /**
     * Returns the pagination and the messages which will be displayed
     * 
     * @param int   $page
     * @param int   $trick_id
     * 
     * @return array [$messages, $pagination]
     */
    public function display(int $page, int $trick_id): array
    {
        $messages = $this->get($page, $trick_id);
        $pagination = $this->paginate($page, $trick_id)[0];

        return [$messages, $pagination];
    }

    /**
     * Creates a new message and save it to database
     * 
     * @param string   $content
     * @param int|null $trick_id
     * 
     * @return string $route
     */
    public function save(string $content, int $trick_id = null): string
    {
        $message = (new Message())
            ->setAuthor($this->security->getUser())
            ->setPost($trick_id)
            ->setContent($content);
        $this->em->persist($message);
        $this->em->flush();

        return $this->generateRoute($message);
    }

    /**
     * Updates a message
     * 
     * @param  Message $message
     * @param  string  $content
     * @return string  $route
     */
    public function update(Message $message, string $content): string
    {
        $message->setLastUpdate(new \DateTime())
            ->setContent($content);

        $this->em->persist($message);
        $this->em->flush();

        $this->flash->add('success', 'Your message has been updated !');
        return $this->generateRoute($message);
    }

    /**
     * Deletes a message
     * 
     * @param Message $message
     * 
     * @return string $route
     */
    public function delete(Message $message): string
    {
        $trick = $message->getPost();
        $this->em->remove($message);
        $this->em->flush();


        $this->flash->add('success', 'Your message has been deleted !');
        if ($trick) {
            return $this->router->generate(
                'tricks.details',
                ['slug' => $trick->getSlug()]
            );
        }
        return $this->router->generate(MessageController::INDEX);
    }

    /**
     * Checks in database if a message exists
     * 
     * @param int $id
     * 
     * @return Message $message
     */
    public function isAuthorized(int $id): Message
    {
        $message = $this->message_repo->find($id);
        $user = $this->security->getUser();
        if (!$message) {
            throw new NotFoundHttpException();
        }
        // @ignore
        if (!$user || $message->getAuthor()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(self::UNAUTHORIZED);
        }
        return $message;
    }

    /**
     * Returns a redirect route towards the chat index page or the trick's page
     * 
     * @param Message $message
     * 
     * @return string $route
     */
    private function generateRoute(Message $message): string
    {
        $trick = $message->getPost();
        if ($trick) {
            return $this->router->generate('tricks.details', ['slug' => $trick->getSlug()]);
        }
        return $this->router->generate(MessageController::INDEX);
    }
}
