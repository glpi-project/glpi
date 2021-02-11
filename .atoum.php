<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

if (($coverage_dir = getenv('COVERAGE_DIR')) === false) {
   $coverage_dir = 'code-coverage';
}
$coverage_path = __DIR__ . '/tests/' . $coverage_dir;

if (!file_exists($coverage_path)) {
    mkdir($coverage_path);
}

$coverageField = new atoum\atoum\report\fields\runner\coverage\html(
    'GLPI',
    $coverage_path
);
$coverageField->setRootUrl('file://' . realpath($coverage_path));

$script
    ->addDefaultReport()
    ->addField($coverageField);

$cloverWriter = new atoum\atoum\writers\file($coverage_path . '/clover.xml');
$cloverReport = new atoum\atoum\reports\asynchronous\clover();
$cloverReport->addWriter($cloverWriter);

$runner->addReport($cloverReport);
