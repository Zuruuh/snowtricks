<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class SecurityService
{
    private FlashBagInterface $flash;
    private PasswordHasherInterface $passwordHasher;

    public function __construct(
        FlashBagInterface $flash,
        PasswordHasherInterface $passwordHasher
    ) {
        $this->flash = $flash;
        $this->passwordHasher = $passwordHasher;
    }

    public function checkToken(string $token, $repo): mixed
    {
        // 1 Verify if token exists
        if (!$token) {
            $this->flash->add('warning', 'Invalid token');

            return false;
        }
        $token = base64_decode($token);
        // 2 Verify if token contains separator
        if (!str_contains($token, ';')) {
            $this->flash->add('warning', 'Invalid token');

            return false;
        }

        $token = explode(';', $token);
        // 3 Verify if token is still valid after array separation
        if (3 !== (int) sizeof($token)) {
            $this->flash->add('warning', 'Invalid token');

            return false;
        }

        // 4 Verify if token is still valid
        if (time() - $token[1] > 3600) {
            $this->flash->add('warning', 'Token expired');

            return false;
        }

        // 5 Verify if user exists
        $user = $repo->findOneBy(['id' => $token[2]]);
        if ((bool) !$user) {
            $this->flash->add('warning', 'Invalid token');

            return false;
        }

        // 6 Verify if password is correct
        if ((string) $user->getPassword() !== (string) $token[0]) {
            $this->flash->add('warning', 'Invalid token');

            return false;
        }

        return $user;
    }

    public function savePassword($user, string $password, $em): void
    {
        $user->setPassword(
            $this->passwordHasher->hash(
                $password
            )
        );
        $em->persist($user);
        $em->flush();
        $this->flash->add('success', 'Your password was successfully updated, you can now login !');
    }
}
