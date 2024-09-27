<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

namespace Glpi\Form\SelfService;

use Glpi\Form\Form;

final class TilesManager
{
    /** @return TileInterface[] */
    public function getTiles(): array
    {
        $tiles = [];

        $tiles[] = new Tile(
            title: __("Browse help articles"),
            description: __("See all available help articles and our FAQ."),
            illustration: "Knowledge",
            link: "/front/helpdesk.faq.php"
        );

        $incident_form = $this->getIncidentForm();
        if ($incident_form !== null) {
            $tiles[] = new FormTile(
                form: $incident_form,
                // Overriding the form's title and description to get a more
                // verbose tile for the home page.
                title: __("Report an issue"),
                description: __("Ask for support from our helpdesk team."),
                // Override illustration as forms are still using tabler icons
                // instead of the new illustrations.
                illustration: "Key points",
            );
        }

        $tiles[] = new Tile(
            title: __("Request a service"),
            description: __("Ask for a service to be provided by our team."),
            illustration: "Services",
            link: "/ServiceCatalog"
        );

        $tiles[] = new Tile(
            title: __("Make a reservation"),
            description: __("Pick an available asset and reserve it for a given date."),
            illustration: "Schedule",
            link: "/front/reservationitem.php"
        );

        $tiles[] = new Tile(
            title: __("View approval requests"),
            description: __("View all tickets waiting for your validation."),
            illustration: "Confirmation",
            // TODO: apply correct search filter
            link: "/front/ticket.php"
        );

        $tiles[] = new Tile(
            title: __("View RSS feeds"),
            description: __("See all our available helpdesk forms and create a ticket."),
            illustration: "New entries",
            // TODO: create dedicated RSS page, the only place they are
            // visible is on the dashboard of the legacy home page.
            link: "/front/helpdesk.public.php"
        );

        return $tiles;
    }

    private function getIncidentForm(): ?Form
    {
        // TODO: form will be loaded using its id later once default tiles are
        // created during GLPI's installation
        $rows = (new Form())->find(['name' => 'Incident']);

        // TODO: once tile are saved to database, deleting a form should also
        // delete the associated tile.
        if (count($rows) === 0) {
            return null;
        }

        $row = array_pop($rows);
        return Form::getById($row['id']);
    }
}
