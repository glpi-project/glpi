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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * @var DB $DB
 * @var Migration $migration
 */

$default_charset = DBConnection::getDefaultCharset();
$default_collation = DBConnection::getDefaultCollation();

/** Create registration_number field */
if (!$DB->fieldExists("glpi_entities", "registration_number")) {
    $migration->addField(
        "glpi_entities",
        "registration_number",
        "string",
        [
         'after'     => "ancestors_cache",
        ]
    );
}
/** /Create registration_number field */

/** Replace -1 value for entities_id field */
$migration->changeField('glpi_entities', 'entities_id', 'entities_id', "int DEFAULT '0'"); // allow null value
$migration->addPostQuery(
    $DB->buildUpdate(
        'glpi_entities',
        ['entities_id' => 'NULL'],
        ['entities_id' => '-1']
    )
);
/** /Replace -1 value for entities_id field */
