<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\Question;
use App\Entity\Quizz;
use App\Form\QuizzType;
use App\Repository\QuizzRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

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
    public function create(Request $request, int $nb, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $quizz = new Quizz();

        // Pré-génération de questions vides
        for ($i = 0; $i < $nb; $i++) {
            $question = new Question();
            for ($j = 0; $j < 4; $j++) {
                $question->addAnswer(new Answer());
            }
            $quizz->addQuestion($question);
        }

        $form = $this->createForm(QuizzType::class, $quizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Validation manuelle
            $errors = [];

            // Titre obligatoire
            if (empty(trim($quizz->getTitle()))) {
                $errors[] = "Le titre est obligatoire.";
            }

            // Gestion de l'upload
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('img_url')->getData();

            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('quiz_images_directory'), // à définir dans services.yaml
                        $newFilename
                    );
                    $quizz->setImgUrl('/uploads/quizzes/' . $newFilename);
                } catch (FileException $e) {
                    $errors[] = "Erreur lors de l'upload de l'image.";
                }
            } else {
                // Image par défaut
                $quizz->setImgUrl('assets\images\default_quizz_pic.jpg');
            }

            // Nettoyage + validation des questions
            foreach ($quizz->getQuestions() as $questionKey => $question) {


                if (empty(trim($question->getContent()))) {
                    $quizz->removeQuestion($question);
                    continue;
                }

                $validAnswers = 0;
                $correctAnswers = 0;

                foreach ($question->getAnswers() as $answer) {
                    if (!empty(trim($answer->getContent()))) {
                        //SI REP NON VIDE
                        $validAnswers++;
                    }
                    if ($answer->isCorrect()) {
                        //SI BONNEREP COCHEE
                        $correctAnswers++;
                    }

                    $answer->setQuestion($question); // relation inverse
                }

                if ($validAnswers < 4) {
                    $errors[] = "Les 4 réponses doivent avoir du contenu";
                    $question->removeAnswer($answer);
                    continue;
                }

                if ($correctAnswers !== 1) {
                    $errors[] = "Une réponse et une seule doit etre correcte pour chaque question";
                    $question->removeAnswer($answer);
                    continue;
                }

                $question->setQuizz($quizz);
            }


            if (count($quizz->getQuestions()) < 1) {
                $errors[] = "Il faut au moins une question valide";
            }


            // Retour si erreurs
            if (count($errors) > 0) {
                foreach ($errors as $msg) {
                    $this->addFlash('error', $msg);
                }
                return $this->redirectToRoute('create',['nb'=>$nb]);
                // return $this->render('manage/create.html.twig', [
                //     'form' => $form->createView(),
                //     'nb' => $nb,
                // ]);
            }

            // Si tout est bon

            foreach ($quizz->getQuestions() as $question) {
                $em->persist($question);
                foreach ($question->getAnswers() as $answer) {
                    $answer->setVotes(0);
                    $em->persist($answer);
                }
            }
            $quizz->setAuthor($this->getUser());
            $quizz->setCreatedAt(new DateTimeImmutable());
            $em->persist($quizz);

            $em->flush();

            $this->addFlash('success', 'Le quiz a bien été créé !');

            return $this->redirectToRoute('home');
        }

        return $this->render('manage/create.html.twig', [
            'form' => $form->createView(),
            'nb' => $nb,
        ]);
    }

}
