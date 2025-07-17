<?php

namespace App\CustomServices;

use App\Entity\Quizz;
use App\Repository\QuestionRepository;

class QuestionService{
    public function __construct(private QuestionRepository $questionRepository) {
    }

    public function getQuestionByTracker(int $trackerValue,Quizz $quizz){
        $questions = $this->questionRepository->findBy(['quizz'=>$quizz,],['id'=>'ASC'],1,$trackerValue);
        if (empty($questions)) {
            return null;
        }
        return $questions[0];
    }
}