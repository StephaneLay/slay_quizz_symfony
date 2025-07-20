<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quizz;
use App\Form\QuizzType;
use App\Repository\QuizzRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/create', name: 'choosesize')]
    public function choosesize(): Response
    {
        return $this->render('manage/choosesize.html.twig', [
            'controller_name' => 'ManageController',

        ]);
    }

    #[Route('/quiz/create/{nb}', name: 'create')]
    public function create(Request $request, int $nb,EntityManagerInterface $em): Response
    {
        $quizz = new Quizz();

        // Pré-génération de questions vides
        for ($i = 0; $i < $nb; $i++) {
            $question = new Question();

            // Pré-générer 4 réponses par question
            for ($j = 0; $j < 4; $j++) {
                $question->addAnswer(new Answer());
            }

            $quizz->addQuestion($question);
        }

        $form = $this->createForm(QuizzType::class, $quizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Filtrage des questions non remplies
            foreach ($quizz->getQuestions() as $question) {
                if (empty(trim($question->getContent()))) {
                    $quizz->removeQuestion($question);
                } else {
                    foreach ($question->getAnswers() as $answer) {
                        if (empty(trim($answer->getContent()))) {
                            $question->removeAnswer($answer);
                        }
                    }
                }
            }

            // Attribuer l’auteur
            $quizz->setAuthor($this->getUser());

            // Enregistrer le quiz
            $em->persist($quizz);
            $em->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('manage/create.html.twig', [
            'form' => $form,
        ]);
    }
}
