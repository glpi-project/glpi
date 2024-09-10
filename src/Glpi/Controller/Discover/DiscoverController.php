<?php

namespace Glpi\Controller\Discover;

use Glpi\Controller\AbstractController;
use Glpi\Discover\Discover;
use Html;
use Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

final class DiscoverController extends AbstractController
{
    #[Route(
        "/Discover",
        name: "glpi_discover",
        methods: "GET"
    )]
    public function __invoke(Request $request): Response
    {
        return new StreamedResponse(fn() => $this->loadDiscover());
    }

    private function loadDiscover(): void
    {
        if (Session::getCurrentInterface() == "central") {
            Html::header(Discover::getTypeName(1), $_SERVER['PHP_SELF'], 'discover');
        } else {
            Html::helpHeader(Discover::getTypeName(1));
        }

        $discover = new Discover();
        $discover->display(['main_class' => 'tab_cadre_fixe']);

        if (Session::getCurrentInterface() == "central") {
            Html::footer();
        } else {
            Html::helpFooter();
        }
    }
}
