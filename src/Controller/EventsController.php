<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventsController extends AbstractController
{
    #[Route('/events/calendar', name: 'events_calendar')]
    public function calendar(): Response
    {
        // Your code for the /events/calendar route goes here

        return $this->render('events/calendar.html.twig');
    }

    #[Route('/events/filter', name: 'events_filter')]
    public function filter(): Response
    {
        // Your code for the /events/filter route goes here

        return $this->render('events/filter.html.twig');
    }
}
