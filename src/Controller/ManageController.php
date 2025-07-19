<?php

namespace App\Controller;

use App\Repository\QuizzRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ManageController extends AbstractController
{
    #[Route('/manage', name: 'manage')]
    public function index(QuizzRepository $quizRepository): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $quizzes = $quizRepository->findAll();
        } else {
            $quizzes = $quizRepository->findBy(['author' => $user]);
        }

        return $this->render('manage/index.html.twig', [
            'controller_name' => 'ManageController',
            'quizzes' => $quizzes,
        ]);
    }
}
