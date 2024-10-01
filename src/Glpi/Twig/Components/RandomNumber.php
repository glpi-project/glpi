<?php

namespace Glpi\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent()]
class RandomNumber
{
    use DefaultActionTrait;

    public function getRandomNumber(): int
    {
        return random_int(0, 1000);
    }
}
