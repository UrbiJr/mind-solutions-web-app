<?php

// src/EventListener/ExceptionListener.php
namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExceptionListener
{
    private $urlGenerator;
    private $security;

    public function __construct(UrlGeneratorInterface $urlGenerator, Security $security)
    {
        $this->urlGenerator = $urlGenerator;
        $this->security = $security;
    }

    public function onKernelException(ExceptionEvent $event)
    {
        // You get the exception object from the received event
        $exception = $event->getThrowable();

        // Handle NotFoundHttpException for unauthenticated users
        if ($exception instanceof NotFoundHttpException && !$this->security->getUser()) {
            // Redirect to login page
            $response = new RedirectResponse($this->urlGenerator->generate('login'));
            $event->setResponse($response);
        }
    }
}