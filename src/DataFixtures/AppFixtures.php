<?php

namespace App\DataFixtures;

use App\Entity\Answer;
use App\Entity\Category;
use App\Entity\Question;
use App\Entity\Quizz;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    const CATEGORIES = [
        'CS' => 'Cinéma & Series',
        'HG' => 'Histoire & Géographie',
        'JV' => 'Jeux Vidéos',
        'MU' => 'Musique',
        'NS' => 'Nature & Sciences',
        'SL' => 'Sports & Loisirs'
    ];
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }
    public function load(ObjectManager $manager): void
    {
        $sheet = $this->convertCsv(__DIR__ . '..\Data\Questionssheet.csv');

        $categories = [];
        $quizzes = [];
        $answers = [];

        $adminUser = new User();
        $adminUser->setEmail("admin@admin.fr")
            ->setName("admin")
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->hasher->hashPassword($adminUser, 'admin'));
        $manager->persist($adminUser);

        foreach (self::CATEGORIES as $categoryCode => $categoryName) {
            $category = new Category();
            $category->setName($categoryCode);
            $categories[] = $category;
            $manager->persist($category);
        }

        foreach ($categories as $category) {
            $quizz = new Quizz();
            $picPath = 'images/' . $category->getName() . '.jpg';

            $quizz->setCreatedAt(new DateTimeImmutable())
                ->setTitle(self::CATEGORIES[$category->getName()])
                ->setCategory($category)
                ->setImgUrl($picPath)
                ->setDescription("Voici un super quiz de la catégorie " . $category->getName())
                ->setAuthor($adminUser);

            $quizzes[] = $quizz;
            $manager->persist($quizz);
        }

        foreach ($sheet as $line) {
            $question = new Question();
            $question->setContent($line["Question"])
                ->setQuizz($this->
                    returnQuizz($line["Category"], $quizzes));
            $manager->persist($question);

            $answer = new Answer();
            $answer->setIsCorrect(true)->setContent($line["Answer"])->setVotes(0)->setQuestion($question);
            $answers[] = $answer;

            for ($i = 1; $i < 4; $i++) {
                $wrongAnswer = new Answer();
                $wrongAnswer->setIsCorrect(false)->setContent($line["BadAnswer" . $i])->setVotes(0)->setQuestion($question);
                $answers[] = $wrongAnswer;
            }

            //ON VEUT RANDOMISER L'ORDRE DES REPONSES
            shuffle($answers);

            foreach ($answers as $answer) {
                $manager->persist($answer);
            }

        }


        $manager->flush();
    }

    private function convertCsv(string $filepath)
    {
        $data = [];

        if (($handle = fopen($filepath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }

        return $data;
    }

    private function returnQuizz(string $codeName, $quizzes)
    {
        foreach ($quizzes as $quizz) {
            if ($quizz->getCategory()->getName() === $codeName) {
                return $quizz;
            }
        }
    }


}
