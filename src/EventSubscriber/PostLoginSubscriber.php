<?php

namespace App\EventSubscriber;

use App\Exception\InvalidLicenseKeyException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use App\Service\Whop;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class PostLoginSubscriber implements EventSubscriberInterface
{
    private $whop;
    private $entityManager;
    private $session;

    public function __construct(Whop $whop, EntityManagerInterface $entityManager)
    {
        $this->whop = $whop;
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin',
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $request = $event->getRequest();

        /** @var \App\Entity\User $user */
        $user = $event->getAuthenticationToken()->getUser();

        try {
            $licenseData = $this->whop->validateLicenseKey($user->getLicenseKey());

            $user->addRole('ROLE_MEMBER');
            $user->setWhopManageUrl($licenseData['manage_url']);
            $this->entityManager->flush();
        } catch (InvalidLicenseKeyException $e) {
            $user->removeRole('ROLE_MEMBER');
        } catch (\Exception $e) {
            $exception = new CustomUserMessageAuthenticationException("An error occurred validating license: {$e->getMessage()}");
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
            throw $exception;
        }
    }
}
