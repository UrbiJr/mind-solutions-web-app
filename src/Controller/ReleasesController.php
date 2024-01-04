<?php

namespace App\Controller;

use App\Entity\Release;
use App\Entity\User;
use App\Form\Type\ReleaseType;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;

class ReleasesController extends AbstractController
{
    #[Route('/admin/releases', name: 'releases_show')]
    public function index(#[CurrentUser] ?User $user, Request $request, EntityManagerInterface $em, ReleaseRepository $releaseRepository): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $release = new Release();
        $release->setAuthor($user);
        $addReleaseForm = $this->createForm(ReleaseType::class, $release);

        $addReleaseForm->handleRequest($request);
        if ($addReleaseForm->isSubmitted() && $addReleaseForm->isValid()) {
            // $form->getData() holds the submitted values
            // but, the original `$task` variable has also been updated
            $release = $addReleaseForm->getData();

            $em->persist($release);
            $em->flush();
        }

        return $this->render(
            'releases/overview.html.twig',
            [
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('Add, Update or Delete Releases'),
                'bannerSubtitle' => new TranslatableMessage('Let your members know about the latest release.'),
                'displayBanner' => true,
                'addReleaseForm' => $addReleaseForm,
            ]
        );
    }

    #[Route('/admin/releases/{id}', name: 'release_item_show')]
    public function release_overview(#[CurrentUser] ?User $user, string $id, Request $request, ReleaseRepository $releaseRepository): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        /** @var Release $release */
        $release = $releaseRepository->find($id);

        $editReleaseForm = $this->handleEditRelease( $request, $release, $releaseRepository);

        return $this->render(
            'releases/release_overview.html.twig',
            [
                'user' => $user,
                'release' => $release,
                'bannerTitle' => new TranslatableMessage('Add, Update or Delete Releases'),
                'bannerSubtitle' => new TranslatableMessage('Let your members know about the latest release.'),
                'displayBanner' => true,
                'editReleaseForm' => $editReleaseForm,
            ]
        );
    }

    private function handleEditRelease(Request $request, Release $release, ReleaseRepository $releaseRepository): FormInterface
    {
        $form = $this->createForm(ReleaseType::class, $release);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $release = $form->getData();
            $releaseRepository->edit($release);
            $this->addFlash('success', '💾 Successfully saved changes.');
        }

        return $form;
    }
}
