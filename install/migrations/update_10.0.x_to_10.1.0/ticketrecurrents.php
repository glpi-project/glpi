<?php

/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

/**
 * @var DB $DB
 * @var Migration $migration
 */

   // Change Ticket recurent items
   // Add glpi_items_ticketrecurrents table for associated elements
if (!$DB->fieldExists('glpi_items_ticketrecurrents', 'ticket_per_item')) {
    $query = "ALTER TABLE glpi_ticketrecurrents
              ADD COLUMN ticket_per_item TINYINT NOT NULL DEFAULT 0,
              ADD KEY `ticket_per_item` (`ticket_per_item`)";
    $DB->queryOrDie($query, "10.1.0 add column  ticket_per_item in glpi_items_ticketrecurrents");
}
