<?php

namespace App\DataFixtures;

use App\Entity\Answer;
use App\Entity\Category;
use App\Entity\Question;
use App\Entity\Quizz;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sheet = $this->convertCsv(__DIR__ . '..\Data\Questionssheet.csv');

        $categories = [];
        $quizzes = [];

        $categoriesName = ['CS', 'HG', 'JV', 'MU', 'NS', 'SL'];

        foreach ($categoriesName as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $categories[] = $category;
            $manager->persist($category);
        }

        foreach ($categories as $category) {
            $quizz = new Quizz();
            $picPath =  '../quizzPics/' . $category->getName() . '.jpg';

            $quizz->setCreatedAt(new DateTimeImmutable())
                ->setTitle($this->returnTitle($category->getName()))
                ->setCategory($category)
                ->setImgUrl($picPath);

            $quizzes[] = $quizz;
            $manager->persist($quizz);
        }

        foreach ($sheet as $line) {
            $question = new Question();
            $question->setContent($line["Question"])->setQuizz($this->returnQuizz($line["Category"],$quizzes));
            $manager->persist($question);

            $answer = new Answer();
            $answer->setIsCorrect(true)->setContent($line["Answer"])->setQuestion($question);
            $manager->persist($answer);

            for ($i=1; $i <4 ; $i++) { 
                $wrongAnswer = new Answer();
                $wrongAnswer->setIsCorrect(false)->setContent($line["BadAnswer".$i])->setQuestion($question);
                $manager->persist($wrongAnswer);
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

    private function returnQuizz(string $codeName,$quizzes){
        foreach ($quizzes as  $quizz) {
            if ($quizz->getCategory()->getName() === $codeName) {
                return $quizz;
            }
        }
    }

    private function returnTitle(string $codeName)
    {
        switch ($codeName) {
            case 'CS':
                return 'Cinéma & Series';
            case 'HG':
                return 'Histoire & Géographie';
            case 'JV':
                return 'Jeux Vidéos';
            case 'MU':
                return 'Musique';
            case 'NS':
                return 'Nature & Sciences';
            case 'SL':
                return 'Sports & Loisirs';
        }
    }
}
