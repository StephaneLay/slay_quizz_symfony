<?php

namespace App\Events;

use App\Entity\Quizz;


class QuizzUpdateEvent
{
  public const NAME = 'quizz.update';

  public function __construct(private Quizz $quizz)
  {
  }

  public function getQuizz():Quizz
  {
    return $this->quizz;
  }
}