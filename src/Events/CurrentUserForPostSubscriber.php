<?php

namespace App\Events;


use App\Entity\Post;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

use Symfony\Component\Security\Core\Security;

class CurrentUserForPostSubscriber implements EventSubscriberInterface {

    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getSubscribedEvents()
    {
       return [
           Events::prePersist
       ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Post) {
            return;
        }

        $user = $this->security->getUser();
        $entity->setUser($user);
    }
}