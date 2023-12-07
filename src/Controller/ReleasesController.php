<?php

namespace App\Controller;

use App\Entity\Release;
use App\Entity\User;
use App\Form\Type\ReleaseType;
use App\Repository\ReleaseRepository;
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
    public function index(#[CurrentUser] ?User $user): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $release = new Release();
        $release->setAuthor($user);
        $addReleaseForm = $this->createForm(ReleaseType::class, $release);

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

        return $this->render(
            'releases/release_overview.html.twig',
            [
                'user' => $user,
                'bannerSubtitle' => $release->getDescription(),
                'displayBanner' => true,
            ]
        );
    }

    private function handleEditRelease(Release $release, User $user, Request $request, array $sections): FormInterface
    {
        $form = $this->createForm(ReleaseType::class, $release);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->addFlash('success', '💾 Successfully saved changes.');
        }

        return $form;
    }
}
