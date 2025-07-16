<?php

namespace App\Controller;

use App\Repository\QuizzRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/play', name: 'play')]
    public function showquizz(QuizzRepository $quizzRepository): Response
    {
        $quizzes = $quizzRepository->findAll();
        return $this->render('home/showquizz.html.twig', [
            'controller_name' => 'HomeController',
            'quizzes' => $quizzes
        ]);
    }
}
