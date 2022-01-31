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

/* Remove global_validation field from templates (should not be defined manually). */
foreach (['glpi_tickettemplatemandatoryfields', 'glpi_tickettemplatepredefinedfields'] as $table) {
    $migration->addPostQuery(
        $DB->buildDelete(
            $table,
            [
                'num' => 52, // global_validation
            ]
        )
    );
}
/* /Remove global_validation field from templates (should not be defined manually). */

/* Add dedicated right for ITILFollowupTemplate */
$migration->addRight('itilfollowuptemplate', ALLSTANDARDRIGHT, ['dropdown' => UPDATE]);
/* Add dedicated right for ITILFollowupTemplate */
