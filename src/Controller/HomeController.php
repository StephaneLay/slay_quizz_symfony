<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Repository\QuizzRepository;
use App\Repository\ResultsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        //Menu principal => 3 pages possibles
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/play', name: 'play')]
    public function showquizz(
        QuizzRepository $quizzRepository,
        ResultsRepository $resultsRepository
    ): Response {

        //Recuperer les quizzs et les historiques de l'user avec ces quizzs
        $user = $this->getUser();
        $quizzes = $quizzRepository->findAll();
        $userResults = $resultsRepository->findBy(['user' => $user]);

        return $this->render('home/showquizz.html.twig', [
            'controller_name' => 'HomeController',
            'quizzes' => $quizzes,
            'userResults' => $userResults
        ]);
    }

    #[Route('/notifications', name: 'notifications')]
    public function shownotifications(NotificationRepository $notificationRepository)
    {
        //Recuperer les notifs concernant l'user(Query Builder)
        $notifications = $notificationRepository->findByUser($this->getUser());

        return $this->render('home/shownotifications.html.twig', [
            'controller_name' => 'HomeController',
            'notifications' => $notifications
        ]);
    }

    #[Route('/notification/delete/{id}', name: 'notification_delete', methods: ['POST'])]
    public function delete(Notification $userNotification, EntityManagerInterface $em, Request $request): Response
    {
        //Supprimer notif en post puis redirect
        if ($this->isCsrfTokenValid('delete_notification_' . $userNotification->getId(), $request->request->get('_token'))) {
            $em->remove($userNotification);
            $em->flush();
            $this->addFlash('success', 'Notification supprimée.');
        }

        return $this->redirectToRoute('notifications');
    }
}