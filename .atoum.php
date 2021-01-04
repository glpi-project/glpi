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

$tests_dir = __DIR__ . '/tests/';
$coverage_dir = $tests_dir . 'code-coverage/';

if (!file_exists($coverage_dir)) {
    mkdir($coverage_dir);
}

$coverageField = new atoum\atoum\report\fields\runner\coverage\html(
    'GLPI',
    $coverage_dir
);
$coverageField->setRootUrl('file://' . realpath($coverage_dir));

$script
    ->addDefaultReport()
    ->addField($coverageField);

//$script->addDefaultReport();

$cloverWriter = new atoum\atoum\writers\file($coverage_dir . 'clover.xml');
$cloverReport = new atoum\atoum\reports\asynchronous\clover();
$cloverReport->addWriter($cloverWriter);

$runner->addReport($cloverReport);
