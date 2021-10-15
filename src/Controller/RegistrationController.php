<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\Security;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    private $emailVerifier;
    private $flash;

    public function __construct(EmailVerifier $emailVerifier, FlashBagInterface $flash)
    {
        $this->emailVerifier = $emailVerifier;
        $this->flash = $flash;
    }

    #[Route('/register', name: 'auth.register')]
    public function register(Request $request, UserPasswordHasher $passwordEncoder): Response
    {
        if ($this->getUser()) {
            $this->flash->add('warning', 'You are already connected !');

            return $this->redirectToRoute('home.index');
        }
        $user = new User();
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $repo = $this->getDoctrine()->getRepository(User::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user = $form->getData();

            if ($repo->findOneBy(['email' => $user->getEmail()])) {
                $form->get('email')->addError(
                    new FormError('This email is already in use')
                );

                return $this->redirectToRoute('auth.register');
            }
            $user->setPassword(
                $passwordEncoder->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            $em->persist($user);
            $em->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('blog@younes-ziadi.com', 'Snowtricks'))
                    ->to($user->getEmail())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('security/confirmation_email.html.twig')
            );

            $this->flash->add(
                'success',
                'An email has been sent to you. Verify your account in order to use the website'
            );

            return $this->redirectToRoute('home.index');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->get('id');

        if (null === $id) {
            return $this->redirectToRoute('auth.register');
        }

        $user = $userRepository->find($id);

        if (null === $user) {
            return $this->redirectToRoute('auth.register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $exception->getReason());

            return $this->redirectToRoute('auth.register');
        }
        // // / TODO Log user in

        $request->getSession()->set(Security::LAST_USERNAME, $user->getUserIdentifier());
        $this->addFlash('success', 'Your email address has been verified, have fun!');

        return $this->redirectToRoute('home.index');
    }
}
