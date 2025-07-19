<?php

namespace App\Security\Voter;

use App\Entity\Quizz;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class QuizVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, ['QUIZ_EDIT'])
            && $subject instanceof Quizz;
    }

    protected function voteOnAttribute(string $attribute, $quizz, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        if ($attribute === 'QUIZ_EDIT') {
            // Admin recup tout
            if (in_array('ROLE_ADMIN', $user->getRoles())) {
                return true;
            }
            // Sinon, seulement si c'est l'auteur
            return $quizz->getAuthor() === $user;
        }

        return false;
    }
}
