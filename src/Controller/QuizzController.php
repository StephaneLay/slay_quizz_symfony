<?php

namespace App\Controller;

use App\CustomServices\QuestionService;
use App\Entity\Category;
use App\Entity\Question;
use App\Entity\Quizz;
use App\Entity\Results;
use App\Repository\QuestionRepository;
use App\Repository\ResultsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/quizz/{id}/play', name: 'playquizz',methods: ['GET'])]
    public function play(Quizz $quizz,
     ResultsRepository $resultsRepository,
     QuestionService $questionService,
     EntityManagerInterface $em
     ): Response
    {
        $user = $this->getUser();
        $result = $resultsRepository->findOneBy(
            [
                'user' => $user,
                'quizz' => $quizz
            ]
        );
        if ($result) {
            $question = $questionService->getQuestionByTracker($result->getQuestionTracker(),$quizz);

        } else {
            $newResult = new Results();
            $newResult->setUser($user)
                ->setQuizz($quizz)
                ->setScore(0)
                ->setQuestionTracker(0);
            
                $em->persist($newResult);
                $em->flush();

            $question = $questionService->getQuestionByTracker(0,$quizz);
        }


        return $this->render('quizz/playquizz.html.twig', [
            'controller_name' => 'QuizzController',
            'question' => $question

        ]);
    }

     #[Route('/quizz/{id}/play', name: 'submit',methods: ['POST'])]
    public function submit(Quizz $quizz,
    ResultsRepository $resultsRepository,
    Request $request,
    EntityManagerInterface $em
     ): Response
    {
       $user = $this->getUser();
       $result = $resultsRepository->findOneBy(
            [
                'user' => $user,
                'quizz' => $quizz
            ]
        );

        $result->setQuestionTracker($result->getQuestionTracker()+1);
        $answer = $request->request->get('answer');
        
        //ANSWER EST UN BOOLEEN , DONC GAGNE UN POINT QUE SI LA REPONSE EST JUSTE
        $result->setScore($result->getScore()+ intval($answer));

        $em->flush();
        
        //PREVOIR 

        return $this->redirectToRoute('playquizz', ['id' => $quizz->getId()]);
    }
   
}
