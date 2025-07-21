<?php

namespace App\EventSubscriber;

use App\Entity\Notification;
use App\Repository\ResultsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuizzUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(private Security $security,
    private EntityManagerInterface $em,
    private ResultsRepository $resultsRepository){}
    
    public function onQuizzUpdateEvent($event): void
    {
        $user = $this->security->getUser();
        $quizz = $event->getQuizz();
        
        $userResult = $this->resultsRepository->findOneBy(['user'=>$user,'quizz'=>$quizz]);
        $this->em->remove($userResult);
        
        $notif = new Notification();
        $notif->addUser($user)->setContent("Le quizz " . $quizz->getTitle() . " a été modifié, votre resultat a donc été supprimé. ");
        $this->em->persist($notif);
        $this->em->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'quizz.update' => 'onQuizzUpdateEvent',
        ];
    }
}
