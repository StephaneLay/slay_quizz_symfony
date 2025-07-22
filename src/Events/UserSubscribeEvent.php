<?php

namespace App\Events;

use App\Entity\User;

class UserSubscribeEvent
{
    public const NAME = 'user.subscribe';

    public function __construct(private User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }
}