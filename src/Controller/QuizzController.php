<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Quizz;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuizzController extends AbstractController
{
    #[Route('/quizz/{id}', name: 'quizz')]
    public function index(Quizz $quizz): Response
    {
        return $this->render('quizz/quizz.html.twig', [
            'controller_name' => 'QuizzController',
            'quizz' => $quizz
            
        ]);
    }

    #[Route('/quizz/{id}/play', name: 'playquizz')]
    public function play(Quizz $quizz): Response
    {
        return $this->render('quizz/playquizz.html.twig', [
            'controller_name' => 'QuizzController',
            'quizz' => $quizz
            
        ]);
    }
}
