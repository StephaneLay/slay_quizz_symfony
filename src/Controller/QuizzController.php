<?php

namespace App\Controller;

use App\CustomServices\QuestionService;
use App\Entity\Answer;
use App\Entity\Category;
use App\Entity\Question;
use App\Entity\Quizz;
use App\Entity\Results;
use App\Repository\AnswerRepository;
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

    #[Route('/quizz/{id}/play', name: 'playquizz', methods: ['GET'])]
    public function play(
        Quizz $quizz,
        ResultsRepository $resultsRepository,
        QuestionService $questionService,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser();
        $result = $resultsRepository->findOneBy(
            [
                'user' => $user,
                'quizz' => $quizz
            ]
        );
        if ($result) {
            $question = $questionService->getQuestionByTracker($result->getQuestionTracker(), $quizz);

        } else {
            $newResult = new Results();
            $newResult->setUser($user)
                ->setQuizz($quizz)
                ->setScore(0)
                ->setQuestionTracker(0);

            $em->persist($newResult);
            $em->flush();

            $question = $questionService->getQuestionByTracker(0, $quizz);
        }


        return $this->render('quizz/playquizz.html.twig', [
            'controller_name' => 'QuizzController',
            'question' => $question

        ]);
    }

    #[Route('/quizz/{id}/play', name: 'submit', methods: ['POST'])]
    public function submit(
        Quizz $quizz,
        ResultsRepository $resultsRepository,
        Request $request,
        EntityManagerInterface $em,
        QuestionService $questionService,
        AnswerRepository $answerRepository
    ): Response {
        $user = $this->getUser();
        $result = $resultsRepository->findOneBy(
            [
                'user' => $user,
                'quizz' => $quizz
            ]
        );
        //ON VEUT RECUPRER LA QUESTION PUR DISPLAY LE REULTAT AVANT DE L'INCREMENTER
        $question = $questionService->getQuestionByTracker($result->getQuestionTracker(), $quizz);
        $answerId = $request->request->get('answer');
        $userAnswer = $answerRepository->findOneBy(['id' => $answerId]);
        $userAnswer->setVotes($userAnswer->getVotes() + 1);






        $result->setQuestionTracker($result->getQuestionTracker() + 1);
        $message = "Mauvaise réponse ";

        if ($userAnswer->isCorrect()) {
            $result->setScore($result->getScore() + 1);
            $message = "Bonne réponse !";
        }


        $em->flush();
        



        return $this->redirectToRoute('answerresult', [
            'id' => $quizz->getId(),
            'questionId' => $question->getId(),
            'answerId' => $userAnswer->getId(),
        ]);


    }

    #[Route(
        '/quizz/{id}/answer-result/{questionId}/{answerId}',
        name: 'answerresult',
        methods: ['GET']
    )]
    public function answerresult(
        int $id,
        int $questionId,
        int $answerId,
        QuestionRepository $questionRepository,
        AnswerRepository $answerRepository
    ): Response {
        $question = $questionRepository->find($questionId);
        $userAnswer = $answerRepository->find($answerId);
        $totalVotes = $answerRepository->getSumVotesByQuestion($question->getId());

        return $this->render('quizz/displayanswer.html.twig', [
            'controller_name' => 'QuizzController',
            'question' => $question,
            'userAnswer' => $userAnswer,
            'totalVotes' => $totalVotes,
            'message' => 'test'
        ]);
    }
}
