<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;

class SessionDeletedListener
{
    private $entityManager;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(BeforeEntityDeletedEvent $event): void
    {
        try {
            $sid = $event->getEntityInstance()->getSession();
            $this->entityManager->getConnection()
                ->executeStatement('DELETE FROM public.sessions WHERE sessions.sess_id = :sid', ["sid" => $sid]);
        } catch (\Exception $e){}
    }
}
