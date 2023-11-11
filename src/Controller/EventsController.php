<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Type\EventFilterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Translation\TranslatableMessage;

class EventsController extends AbstractController
{
    #[Route('/events/calendar', name: 'events_calendar')]
    public function calendar(#[CurrentUser] ?User $user): Response
    {
        return $this->render('events/calendar.html.twig',
            [
                'user' => $user,
                'bannerTitle' => new TranslatableMessage('calendar_title', ['siteName' => $this->getParameter('site_name')]),
                'bannerSubtitle' => new TranslatableMessage('The only calendar that really matters.'),
                'displayBanner' => true,
            ]
        );
    }

    #[Route('/events/filter', name: 'events_filter')]
    public function filter(#[CurrentUser] ?User $user): Response
    {
        // The second parameter is used to specify on what object the role is tested.
        $this->denyAccessUnlessGranted('ROLE_MEMBER', null, 'Unable to access this page!');

        $form = $this->createForm(EventFilterType::class);

        return $this->render('events/filter.html.twig',
            [
                'user' => $user,
                'eventFilterForm' => $form,
                'bannerTitle' => new TranslatableMessage('Viagogo Events at a glance'),
                'bannerSubtitle' => new TranslatableMessage('Filter, search and focus on your preferred events. Blazingly fast and effortlessly.'),
                'displayBanner' => true,
            ]
        );
    }

    #[Route('/events/{id}', name: 'event_show')]
    public function showEvent(#[CurrentUser] ?User $user, string $id): Response
    {

        return $this->render('events/overview.html.twig',
            [
                'user' => $user,
                'eventId' => $id,
                'bannerTitle' => new TranslatableMessage('Viagogo Events at a glance'),
                'bannerSubtitle' => new TranslatableMessage('Filter, search and focus on your preferred events. Blazingly fast and effortlessly.'),
                'displayBanner' => true,
                'bannerBtnIcon' => '<i class="fa-solid fa-arrow-up-right-from-square"></i>',
                'bannerBtnText' => new TranslatableMessage('View on Viagogo'),
                'bannerBtnId' => 'viewOnViagogo',
                'bannerBtnTarget' => '_blank',
            ]
        );
    }
}
