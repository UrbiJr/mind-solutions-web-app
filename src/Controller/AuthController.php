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
use App\Service\Whop;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class AuthController extends AbstractController
{
    private EmailVerifier $emailVerifier;
    private Whop $whop;

    public function __construct(EmailVerifier $emailVerifier, Whop $whop)
    {
        $this->emailVerifier = $emailVerifier;
        $this->whop = $whop;
    }

    #[Route('/auth/login', methods: ['GET', 'POST'], name: 'login')]
    public function login(#[CurrentUser] ?User $user, Request $request, AuthenticationUtils $authenticationUtils, CsrfTokenManagerInterface $csrfTokenManagerInterface)
    {
        // If the user is already authenticated, redirect them
        if ($user) {
            return $this->redirectToRoute('dashboard');
        }

        // last username entered by the user
        $defaultData = array(
            'username' => $authenticationUtils->getLastUsername(),
        );

        $form = $this->createFormBuilder($defaultData)
            ->add('username', TextType::class, ['label' => 'Username or Email'])
            ->add('password', PasswordType::class)
            ->add('_csrf_token', HiddenType::class, [
                'data' => $csrfTokenManagerInterface->getToken('authenticate')->getValue(),
            ])
            ->add('login', SubmitType::class, ['label' => 'Sign In'])
            ->getForm();

        $form->handleRequest($request);

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        /*
            When initially loading the page in a browser, the form hasn't been submitted yet
            and $form->isSubmitted() returns false ...
        */
        if ($form->isSubmitted() && $form->isValid()) {
            // User is already authenticated, redirect to dashboard
            return $this->redirectToRoute('dashboard');
        }

        return $this->render('auth/login.html.twig', array(
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
    public function register(#[CurrentUser] ?User $user, Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        // If the user is already authenticated, redirect them
        if ($user) {
            return $this->redirectToRoute('dashboard');
        }

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
            'auth/register.html.twig',
            [
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
    #[Route('/auth/verify', name: 'app_verify_email')]
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

    #[Route('/auth/whop-callback', methods: ['GET', 'POST'], name: 'whop_callback')]
    public function whopCallback(#[CurrentUser] ?User $user, Request $request, Security $security, EntityManagerInterface $entityManager): Response
    {
        $code = $request->query->get('code');
        $whopClientId = $this->getParameter('whop_client_id');
        $whopRedirectUri = $this->getParameter('whop_redirect_uri');
        $whopClientSecret = $this->getParameter('whop_client_secret');

        $authToken = $this->whop->getAuthToken(
            $code,
            $whopClientId,
            $whopClientSecret,
            $whopRedirectUri,
        );

        $membership = $this->whop->validateMembership($authToken);

        $userRepository = $entityManager->getRepository(User::class);

        /** @var \App\Entity\User $dbUser */
        $dbUser = $userRepository->findOneBy(['username' => $membership["email"]]);

        if ($user && $dbUser && $user->getUsername() !== $dbUser->getUsername()) {
            $this->addFlash(
                'notice',
                'Login required: use your Whop email to sign in.'
            );

            $security->logout(false);

            return $this->redirectToRoute('login');
        }

        if ($dbUser) {
            $dbUser->setImageUrl($membership["discord"]["image_url"]);
            $dbUser->setDiscordID($membership["discord"]["id"]);

            try {
                $parts = explode('#', $membership["discord"]["username"]);
                $username = $parts[0];
            } catch (\Exception $e) {
                $username = $membership["discord"]["username"];
            }

            $dbUser->setDiscordUsername($username);
            $dbUser->setLicenseKey($membership["license_key"]);

            $entityManager->persist($dbUser);

            $dbUser->addRole('ROLE_MEMBER');
            $dbUser->setWhopManageUrl($membership['manage_url']);
            $entityManager->flush();

            // log the user in on the current firewall
            $redirectResponse = $security->login($dbUser, 'form_login');

            $this->addFlash(
                'success',
                '🍾 Success! You have authenticated and bound your Whop membership to your account.'
            );

            return $this->redirectToRoute('dashboard');
        }

        $this->addFlash(
            'notice',
            'Account required: use your Whop email to sign up.'
        );

        $security->logout(false);

        return $this->redirectToRoute('register', ['email' => $membership["email"]]);
    }

    #[Route('/logout', methods: ['GET'], name: 'logout')]
    public function logout(Security $security)
    {
        $security->logout(false);

        return $this->redirectToRoute('login');
    }
}
