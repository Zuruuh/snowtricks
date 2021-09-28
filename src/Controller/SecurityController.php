<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Entity\User;
use App\Service\SecurityService;

class SecurityController extends AbstractController
{

    private $flash;
    private MailerInterface $mailer;

    public function __construct(FlashBagInterface $flash, MailerInterface $mailer)
    {
        $this->flash = $flash;
        $this->mailer = $mailer;
    }

    
    #[Route("/login", name: "auth.login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            $this->flash->add("warning", "You are already connected !");
            return $this->redirectToRoute('home.index');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    
    #[Route("/logout", name: "auth.logout")]
    public function logout()
    {
        throw new \LogicException('This method can be blank');
    }

    #[Route("/forgot-password", name: "auth.forgot_password")]
    public function forgotPassword(Request $request): Response
    {
        if ($this->getUser()) {
            $this->flash->add("warning", "You are already connected !");
            return $this->redirectToRoute('home.index');
        }
        
        $form = $this->createFormBuilder([])
        ->add('username', TextType::class, [
            "required" => true,
            "label" => "Username",
            "attr" => [
                "class" => "form-control",
                "placeholder" => "Your username.."
            ]
        ])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->getData()["username"];
            $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(["username" => $username]);
            if ((bool) !$user) {
                $this->flash->add("warning", "This username is not in use");
                return $this->redirectToRoute('auth.forgot_password');
            }

            $time = time();
            $token = "{$user->getPassword()};$time;{$user->getId()}";
            $token = base64_encode($token);

            $reset_password_link = $this->generateUrl(
                'auth.reset_password',
                [
                    "token" => $token
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            
            $email = (new TemplatedEmail())
                ->from("blog@younes-ziadi.com")
                ->to($user->getEmail())
                ->subject("Password reset")
                ->htmlTemplate("security/password_reset_email.html.twig")
                ->context([
                    "username" => $user->getUsername(),
                    "link" => $reset_password_link,
                    "expires" => "2 hours"
                ]);
            $this->mailer->send($email);
            $this->flash->add("success", "An email has been sent to you !");
            return $this->redirectToRoute('home.index');
        }
        return $this->render('security/forgot_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route("/reset-password", name: "auth.reset_password")]
    public function resetPassword(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        if ($this->getUser()) {
            $this->flash->add("warning", "You are already connected !");
            return $this->redirectToRoute('home.index');
        }

        $token = $request->query->get("token");
        $user_repo = $this->getDoctrine()->getRepository(User::class);
        $em = $this->getDoctrine()->getManager();

        $service = new \App\Service\SecurityService($this->flash, $passwordEncoder);
        $user = $service->checkToken($token, $user_repo);
        
        if ((bool) !$user) {
            return $this->redirectToRoute('auth.forgot_password');
        }

        $form = $this->createFormBuilder([])
        ->add('password', RepeatedType::class, [
            "type" => PasswordType::class,
            "invalid_message" => "Your passwords do not match !",
            "options" => [
                "attr" => [
                    "class" => "form-control",
                    ]
                ],
            "required" => true,
            'constraints' => [
                new NotBlank([
                    'message' => 'Please enter a password',
                ]),
                new Length([
                    'min' => 6,
                    'minMessage' => 'Your password should be at least {{ limit }} characters',
                    'max' => 4096,
                ]),
            ],
            'first_options'  => ['label' => 'Password'],
            'second_options' => ['label' => 'Repeat Password'],
        ])
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $service->savePassword($user, $form->getData()["password"], $em);
            return $this->redirectToRoute('auth.login');
        }

        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
