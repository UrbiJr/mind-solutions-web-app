<?php
// src/Controller/ProfileController.php
namespace App\Controller;

use App\Entity\User;
use App\Form\Type\UserAboutType;
use App\Form\Type\UserConnectionsType;
use App\Form\Type\UserPasswordType;
use App\Form\Type\UserSettingsType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{

    #[Route('/profile', name: 'profile_show')]
    public function overview(#[CurrentUser] ?User $user, EntityManagerInterface $entityManager)
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $currentTimestamp = time();
        $mysqlTimestamp = $user->getCreatedAt()->getTimestamp();
        $secondsAgo = $currentTimestamp - $mysqlTimestamp;
        $daysAgo = floor($secondsAgo / (60 * 60 * 24));

        if ($daysAgo === 0) {
            $memberSince = "today";
        } elseif ($daysAgo === 1) {
            $memberSince = "yesterday";
        } else {
            $memberSince = "$daysAgo days";
        }

        if (!array_key_exists('discord', $user->getConnections())) {
            // Add discord to user connections
            $user->setConnection('discord', array(
                'username' => $user->getDiscordUsername(),
                'url' => "https://discordapp.com/users/" . $user->getDiscordId(),
                'display' => true,
            ));
            $entityManager->flush();
        }

        return $this->render('profile/overview.html.twig', [
            'user' => $user,
            'displayBanner' => true,
            'bannerTitle' => new TranslatableMessage('This is your profile overview'),
            'bannerSubtitle' => new TranslatableMessage('Have a look at your feed, create your ticket portfolio, and even more.'),
            'memberSince' => $memberSince,
        ]);
    }

    #[Route('/profile/settings', name: 'profile_settings', methods: ['GET', 'POST'])]
    public function settings(#[CurrentUser] ?User $user, EntityManagerInterface $entityManager, Request $request, UserPasswordHasherInterface $userPasswordHasher)
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        if (!array_key_exists('discord', $user->getConnections())) {
            // Add discord to user connections
            $user->setConnection('discord', array(
                'username' => $user->getDiscordUsername(),
                'url' => "https://discordapp.com/users/" . $user->getDiscordId(),
                'display' => true,
            ));
            $entityManager->flush();
        }

        // Handle forms
        $settingsForm = $this->handleSettingsForm($user, $entityManager, $request);
        $passwordForm = $this->handlePasswordForm($user, $entityManager, $userPasswordHasher, $request);
        $connectionsForm = $this->handleConnectionsForm($user, $entityManager, $request);
        $aboutForm = $this->handleAboutForm($user, $entityManager, $request);
        $exportForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('profile_export'))
            ->setMethod('POST')
            ->add('export', ChoiceType::class, [
                'choices' => [
                    'Inventory' => 'inventoryExport',
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => false,
                'data' => 'inventoryExport',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Backup',
            ])
            ->getForm();

        $importForm = $this->createFormBuilder()
            ->setAction($this->generateUrl('profile_import'))
            ->setMethod('POST')
            ->add('inventoryCsvFile', FileType::class, [
                'label' => 'Inventory backup',
                'attr' => [
                    'accept' => '.csv',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Restore',
                'attr' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#confirmRestoreModal',
                ],
            ])
            ->getForm();

        return $this->render('profile/settings.html.twig', [
            'user' => $user,
            'displayBanner' => false,
            'exportForm' => $exportForm,
            'importForm' => $importForm,
            'settingsForm' => $settingsForm,
            'passwordForm' => $passwordForm,
            'connectionsForm' => $connectionsForm,
            'aboutForm' => $aboutForm,
        ]);
    }

    #[Route('/profile/export', name: 'profile_export', methods: ['POST'])]
    public function profileExport(#[CurrentUser] ?User $user, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        [$fileName, $filePath] = $userRepository->exportInventoryToCSV($user);

        $response = new BinaryFileResponse($filePath);

        // Set the Content-Disposition header to attachment to force download
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $fileName
        );

        // Automatically delete the file after sending it to the client
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/profile/import', name: 'profile_import', methods: ['POST'])]
    public function profileImport(#[CurrentUser] ?User $user, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');
    }

    private function handleSettingsForm(User $user, EntityManagerInterface $entityManager, Request $request): FormInterface
    {
        $form = $this->createForm(UserSettingsType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', '💾 Successfully saved changes.');
        }

        return $form;
    }

    private function handlePasswordForm(User $user, EntityManagerInterface $entityManager, UserPasswordHasher $userPasswordHasher, Request $request): FormInterface
    {
        $form = $this->createForm(UserPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            $this->addFlash(
                'success',
                "💾 Successfully saved changes"
            );
        }

        return $form;
    }

    private function handleConnectionsForm(User $user, EntityManagerInterface $entityManager, Request $request): FormInterface
    {
        $defaultData = [
            'twitter' => $user->getConnection('twitter')['username'] ?? '',
            'displayTwitterOnProfile' => $user->getConnection('twitter')['display'] ?? false,
            'threads' => $user->getConnection('threads')['username'] ?? '',
            'displayThreadsOnProfile' => $user->getConnection('threads')['display'] ?? false,
            'instagram' => $user->getConnection('instagram')['username'] ?? '',
            'displayInstagramOnProfile' => $user->getConnection('instagram')['display'] ?? false,
            'youtube' => $user->getConnection('youtube')['username'] ?? '',
            'displayYouTubeOnProfile' => $user->getConnection('youtube')['display'] ?? false,
        ];

        $form = $this->createForm(UserConnectionsType::class, $user, [
            'data' => $defaultData,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $twitter = $form->get('twitter')->getData();
            $displayTwitterOnProfile = $form->get('displayTwitterOnProfile')->getData();
            $threads = $form->get('threads')->getData();
            $displayThreadsOnProfile = $form->get('displayThreadsOnProfile')->getData();
            $instagram = $form->get('instagram')->getData();
            $displayInstagramOnProfile = $form->get('displayInstagramOnProfile')->getData();
            $youtube = $form->get('youtube')->getData();
            $displayYouTubeOnProfile = $form->get('displayYouTubeOnProfile')->getData();

            $user->setConnection('twitter', array(
                'username' => $twitter,
                'url' => "https://twitter.com/" . $twitter,
                'display' => $displayTwitterOnProfile,
            ));
            $user->setConnection('threads', array(
                'username' => $threads,
                'url' => "https://threads.app/" . $threads,
                'display' => $displayThreadsOnProfile,
            ));
            $user->setConnection('instagram', array(
                'username' => $instagram,
                'url' => "https://instagram.com/" . $instagram,
                'display' => $displayInstagramOnProfile,
            ));
            $user->setConnection('youtube', array(
                'username' => $youtube,
                'url' => $youtube,
                'display' => $displayYouTubeOnProfile,
            ));

            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', '💾 Successfully saved changes.');
        }

        return $form;
    }

    private function handleAboutForm(User $user, EntityManagerInterface $entityManager, Request $request): FormInterface
    {
        $form = $this->createForm(UserAboutType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', '💾 Successfully saved changes.');
        }

        return $form;
    }
}
