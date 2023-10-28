<?php
// src/Controller/AuthController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Form\Type\UserType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Service\Whop;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class AuthController extends AbstractController
{
    private Whop $whop;
    private EmailVerifier $emailVerifier;

    public function __construct(Whop $whop, EmailVerifier $emailVerifier)
    {
        $this->whop = $whop;
        $this->emailVerifier = $emailVerifier;
    }

    #[Route('/auth/login', methods: ['GET', 'POST'], name: 'login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager)
    {
        $user = new User();

        $error = null;

        $form = $this->createFormBuilder($user)
            ->add('username', TextType::class, ['label' => 'Username or Email'])
            ->add('password', PasswordType::class)
            ->add('login', SubmitType::class, ['label' => 'Sign In'])
            ->getForm();

        $form->handleRequest($request);

        /*
            When initially loading the page in a browser, the form hasn't been submitted yet
            and $form->isSubmitted() returns false ...
        */
        if ($form->isSubmitted() && $form->isValid()) {
            // get the login error if there is one
            $error = $authenticationUtils->getLastAuthenticationError();

            // last username entered by the user
            $lastUsername = $authenticationUtils->getLastUsername();

            /** @var \App\Entity\User $user */
            $user = $this->getUser(); // Get the logged-in user

            if ($user) {
                try {
                    // Assuming the user entity has a method getLicenseKey() to retrieve their license
                    $licenseData = $this->whop->validateLicenseKey($user->getLicenseKey());

                    if ($licenseData['valid']) {
                        // Grant ROLE_MEMBER if the license is valid
                        $user->addRole('ROLE_MEMBER');
                    } else {
                        // Remove ROLE_MEMBER if the license is not valid
                        $user->removeRole('ROLE_MEMBER');
                    }

                    $entityManager->flush(); // Save changes to user roles

                    // redirect to home
                    return $this->redirectToRoute('home');
                } catch (\Exception $e) {
                    // Handle exceptions accordingly, perhaps log the error or notify the user
                    $error = (object) [
                        'messageKey' => $e->getMessage(),
                        'messageData' => []
                    ];
                }
            } else {
                // Simulate an error object like Symfony's authentication error
                $error = (object) [
                    'messageKey' => 'User not found',
                    'messageData' => []
                ];
            }
        }

        return $this->render('auth.html.twig', array(
            'subview' => 'views/auth/login.html.twig',
            'form' => $form,
            'error'         => $error,
            'showToast' => false,
            'toastClass' => 'bg-primary',
            'toastTitle' => '',
            'toastBody' => '',
            'site_name' => $this->getParameter('site_name'),
        ));
    }

    #[Route('/auth/register', methods: ['GET', 'POST'], name: 'register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();

        $form = $this->createForm(UserType::class, $user, [
            'action' => $this->generateUrl('register'),
        ]);

        $form->handleRequest($request);

        /*
            When initially loading the page in a browser, the form hasn't been submitted yet
            and $form->isSubmitted() returns false ...
        */
        if ($form->isSubmitted() && $form->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$user` variable has also been updated
            $user = $form->getData();

            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            // Save to database
            $entityManager->persist($user);
            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@mindsolutions.app', 'Mind Solutions'))
                    ->to($user->getUsername())
                    ->subject('Please Confirm your Email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash(
                'notice',
                "📩 Please confirm your email address by clicking the link in the email we just sent to: {$user->getUsername()}"
            );

            return $this->redirectToRoute('login');
        }

        // ... So, the form is created and rendered;
        return $this->render(
            'auth.html.twig',
            [
                'subview' => 'views/auth/register.html.twig',
                'form' => $form,
                'showToast' => false,
                'toastClass' => 'bg-primary',
                'toastTitle' => '',
                'toastBody' => '',
                'site_name' => $this->getParameter('site_name'),
            ]
        );
    }

    /* 
        Validates the signed url on confirmation email after registration
    */
    #[Route('/verify', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->query->get('id'); // retrieve the user id from the url

        // Verify the user id exists and is not null
        if (null === $id) {
            $this->addFlash('error', 'missing user identifier in confirmation link');
            return $this->redirectToRoute('register');
        }

        $user = $userRepository->find($id);

        // Ensure the user exists in persistence
        if (null === $user) {
            $this->addFlash('error', 'user does not exist anymore');
            return $this->redirectToRoute('register');
        }

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $exception->getReason());

            return $this->redirectToRoute('register');
        }

        $this->addFlash('success', '🙌 Your email address has been verified! You are good to go now.');

        return $this->redirectToRoute('login');
    }
}
