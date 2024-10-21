<?php

namespace App\EventListener;

use App\Entity\Session;
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
        $session = new Session();
        /** @var User $user */
        $user = $event->getUser();
        $session->setUserId($user->getId());
        $request = $event->getRequest();
        $session->setUserAgent($request->headers->get("User-Agent"));
        $session->setCreatedAt(new DateTimeImmutable());
        $session->setIp($request->getClientIp());
        $this->entityManager->persist($session);
        $this->entityManager->flush();
    }
}
