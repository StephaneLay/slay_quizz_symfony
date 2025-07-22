<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Events\UserSubscribeEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSubscribeListenerSubscriber implements EventSubscriberInterface
{   public function __construct(private EntityManagerInterface $em) {}
    public function onUserSubscribeEvent(UserSubscribeEvent $event): void
    {
        $notif = new Notification();
        $user = $event->getUser();
        $notif->addUser($user)->setContent("Bonjour, " .$user->getName()." , merci de vous etes inscrit sur cette merveilleuse éval!");
        $this->em->persist($notif);
        $this->em->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'user.subscribe' => 'onUserSubscribeEvent',
        ];
    }
}
