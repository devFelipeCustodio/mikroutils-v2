<?php

namespace App\EventListener;

use App\Entity\UserSession;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class SessionLoggingListener
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $session = new UserSession();
        $user = $event->getUser();
        $session->setUserId($user->getId());
        $request = $event->getRequest();
        $session->setSessionId($request->getSession()->getId());
        $session->setUserAgent($request->headers->get("User-Agent"));
        $session->setCreatedAt(new DateTimeImmutable());
        $session->setIp($request->getClientIp());
        $this->entityManager->persist($session);
        $this->entityManager->flush();
    }
}
