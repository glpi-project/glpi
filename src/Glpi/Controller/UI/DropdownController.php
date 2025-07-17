<?php

namespace Glpi\Controller\UI;

use Glpi\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DropdownController extends AbstractController
{
    #[Route(
        path: "/dropdown",
        name: "glpi_ui_dropdown",
    )]
    public function __invoke(Request $request): Response
    {
        $itemtype = $request->query->getString('itemtype');
        $fieldName = $request->query->getString('fieldname');
        $selectedValue = $request->query->getInt('value', 0);
        //        $label = $request->query->getString('label');
        //        $options = json_decode($request->query->getString('options')); // @todoseb safecheck

        return $this->render('components/dropdown/dropdown.html.twig', [
            'itemtype'  => $itemtype,
            'fieldname' => $fieldName,
            'selected_value' => $selectedValue,
//            'label' => $label,
            // @todo il faut passer une option pour ne pas avoir un field horizontal (
            'options' => [
                'full_width' => true,
                'no_label' => true,
                'include_field' => false,
            ],
        ]);
    }
}
