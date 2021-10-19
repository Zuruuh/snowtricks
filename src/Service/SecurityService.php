<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityService
{
    private FlashBagInterface $flash;
    private UserPasswordHasherInterface $passwordHasher;
    private UserRepository $user_repo;

    const INVALID_TOKEN = 'Invalid token';
    const EXPIRED_TOKEN = 'Invalid token';

    public function __construct(
        FlashBagInterface $flash,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $user_repo,
    ) {
        $this->flash = $flash;
        $this->passwordHasher = $passwordHasher;
        $this->user_repo = $user_repo;
    }

    /**
     * Validates a token
     * 
     * @param string $token
     * 
     * @return bool|string
     */
    public function checkToken(string $token): bool|User
    {
        // 1 Verify if token exists
        if ($token == "") {
            $this->flash->add('warning', self::INVALID_TOKEN);

            return false;
        }
        $token = base64_decode($token);
        // 2 Verify if token contains separator
        if (!str_contains($token, ';')) {
            $this->flash->add('warning', self::INVALID_TOKEN);

            return false;
        }

        $token = explode(';', $token);
        // 3 Verify if token is still valid after array separation
        if (sizeof($token) !== 3) {
            $this->flash->add('warning', self::INVALID_TOKEN);

            return false;
        }

        // 4 Verify if token is still valid
        if (time() - $token[1] > 3600) {
            $this->flash->add('warning', self::EXPIRED_TOKEN);

            return false;
        }

        // 5 Verify if user exists
        $user = $this->user_repo->findOneBy(['id' => $token[2]]);
        if (!$user) {
            $this->flash->add('warning', self::INVALID_TOKEN);

            return false;
        }

        // 6 Verify if password is correct
        if ($user->getPassword() !== $token[0]) {
            $this->flash->add('warning', self::INVALID_TOKEN);

            return false;
        }

        return $user;
    }

    public function savePassword($user, string $password, $em): void
    {
        $user->setPassword(
            $this->passwordHasher->hashPassword(
                $user,
                $password
            )
        );
        $em->persist($user);
        $em->flush();
        $this->flash->add('success', 'Your password was successfully updated, you can now login !');
    }
}
