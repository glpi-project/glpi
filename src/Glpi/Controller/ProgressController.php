<?php

namespace Glpi\Controller;

use Glpi\Http\Firewall;
use Glpi\Security\Attribute\SecurityStrategy;
use Symfony\Component\HttpFoundation\JsonResponse;
use Glpi\Progress\ProgressChecker;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProgressController extends AbstractController
{
    public function __construct(
        private readonly ProgressChecker $progressChecker,
    ) {
    }

    #[Route("/progress/check/{key}", methods: 'POST')]
    #[SecurityStrategy(Firewall::STRATEGY_NO_CHECK)]
    public function check_progress(string $key): Response
    {
        if (!$this->progressChecker->hasProgress($key)) {
            return new JsonResponse([], 404);
        }

        return new JsonResponse($this->progressChecker->getCurrentProgress($key));
    }
}
