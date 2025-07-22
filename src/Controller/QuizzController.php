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
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class QuizzController extends AbstractController
{
    const ENDQUIZZ_MESSAGES = [
        0.33 => "Mouais bin c'est pas foufou comme résultat y a pas de quoi flamber",
        0.66 => "Franchement pas mal du tout j'ai envie de dire pas mal du tout",
        1 => "Bah bravo t'es juste un.e monstre, ca m'épate ce talent, j'ai meme envie de dire ca m'emeut"
    ];

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

        $result = $resultsRepository->findOneBy([
            'user' => $user,
            'quizz' => $quizz,
        ]);
        
        if (!$result) {
            $result = (new Results())
                ->setUser($user)
                ->setQuizz($quizz)
                ->setScore(0)
                ->setQuestionTracker(0);

            $em->persist($result);
            $em->flush();
        }

        $question = $questionService->getQuestionByTracker($result->getQuestionTracker(), $quizz);

        if (!$question) {
            return $this->redirectToRoute('endquizz', ['id' => $quizz->getId()]);
        }

        return $this->render('quizz/playquizz.html.twig', [
            'question' => $question,
            'quizz' => $quizz,
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
        $question = $questionService->getQuestionByTracker($result->getQuestionTracker(), $quizz);
        $answerId = $request->request->get('answer');
        $userAnswer = $answerRepository->findOneBy(['id' => $answerId]);

        $userAnswer->addVote();
        $result->addQuestionTracker();

        //BOOLEEN DONC INCREMENTE QUE SI REPONSE CORRECTE
        $result->setScore($result->getScore() + intval($userAnswer->isCorrect()));

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
            'message' => $userAnswer->isCorrect() ? 'Bonne réponse !' : 'Mauvaise réponse'
        ]);
    }

    #[Route('/quizz/{id}/end', name: 'endquizz')]
    public function endquizz(
        Quizz $quizz,
        ResultsRepository $resultsRepository,
        EntityManagerInterface $em
    ) {
        $user = $this->getUser();
        $result = $resultsRepository->findOneBy(
            [
                'user' => $user,
                'quizz' => $quizz
            ]
        );

        $result->setCompletedAt(new DateTimeImmutable());
        $em->flush();

        $topResults = $resultsRepository->findTopResultsForQuizz($quizz->getId());

        $message = "Un sans faute ! Impressionant";
        $ratio = round($result->getScore() / count($quizz->getQuestions()), 2);

        foreach (self::ENDQUIZZ_MESSAGES as $compareRatio => $endMessage) {
            if ($ratio < $compareRatio) {
                $message = $endMessage;
                break;
            }
        }

        return $this->render('quizz/endquizz.html.twig', [
            'controller_name' => 'QuizzController',
            'quizz' => $quizz,
            'message' => $message,
            'userResult' => $result,
            'topResults' => $topResults
        ]);
    }
}
