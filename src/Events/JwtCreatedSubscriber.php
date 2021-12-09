<?php

namespace App\Events;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

class JwtCreatedSubscriber {
    public function onJWTCreated(JWTCreatedEvent $event) {

        // Recupérer l'utilisateur
        $user = $event->getUser();
        // Enrichir le payload
        $data = $event->getData();
        $data["firstName"] = $user->getFirstName();
        $data["lastName"] = $user->getLastName();
        // Remettre à jour les data
        $event->setData($data);
    }
}
