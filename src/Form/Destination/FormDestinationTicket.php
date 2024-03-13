<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

namespace Glpi\Form\Destination;

use Exception;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Form\AnswersSet;
use Glpi\Form\Form;
use Item_Ticket;
use Override;
use Ticket;

// TODO: we may need an abstract FormDestinationCommonITIL base class
final class FormDestinationTicket extends AbstractFormDestinationType
{
    #[Override]
    public static function getTargetItemtype(): string
    {
        return Ticket::class;
    }

    #[Override]
    public function createDestinationItems(
        Form $form,
        AnswersSet $answers_set
    ): array {
        // Create a simple ticket for now
        $input = [
            'name'    => 'Ticket from form: ' . $form->fields['name'],
            'content' => 'Answers: ' . json_encode($answers_set->fields['answers']),
        ];

        $ticket = new Ticket();
        if (!$ticket->add($input)) {
            throw new Exception(
                "Failed to create ticket: " . json_encode($input)
            );
        }

        // We will also link the answers directly to the ticket
        // This allow users to see it an an associated item and known where the
        // ticket come from
        $item_ticket = new Item_Ticket();
        $input = [
            'tickets_id' => $ticket->getID(),
            'itemtype'   => $answers_set::class,
            'items_id'   => $answers_set->getID(),
        ];
        if (!$item_ticket->add($input)) {
            throw new Exception(
                "Failed to create item ticket: " . json_encode($input)
            );
        }

        return [$ticket];
    }

    #[Override]
    public function renderConfigForm(): string
    {
        $twig = TemplateRenderer::getInstance();
        return $twig->render(
            'pages/admin/form/form_destination_commonitil_config.html.twig',
            [
                'item' => $this,
            ]
        );
    }

    #[Override]
    public static function getTypeName($nb = 0)
    {
        return Ticket::getTypeName($nb);
    }

    #[Override]
    public static function getFilterByAnswsersSetSearchOptionID(): int
    {
        return 120;
    }
}
