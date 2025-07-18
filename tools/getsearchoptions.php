<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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
use Glpi\Kernel\Kernel;
use Glpi\Search\SearchOption;

if (PHP_SAPI != 'cli') {
    echo "This script must be run from command line";
    exit();
}

require dirname(__DIR__) . '/vendor/autoload.php';

$kernel = new Kernel();
$kernel->boot();

if (isset($_SERVER['argv'])) {
    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        $it = explode("=", $_SERVER['argv'][$i], 2);
        $it[0] = preg_replace('/^--/', '', $it[0]);

        $_GET[$it[0]] = ($it[1] ?? true);
    }
}

function help()
{
    echo "\nUsage : php getsearchoptions.php --type=<itemtype> [ --lang=<locale> ]\n\n";
}

if (isset($_GET['help'])) {
    help();
    exit(0);
}

if (!isset($_GET['type'])) {
    help();
    die("** mandatory option 'type' is missing\n");
}
if (!class_exists($_GET['type'])) {
    die("** unknown type\n");
}
if (isset($_GET['lang'])) {
    Session::loadLanguage($_GET['lang']);
}

$opts = SearchOption::getOptionsForItemtype($_GET['type']);
$sort = [];
$group = 'N/A';

foreach ($opts as $ref => $opt) {
    if (isset($opt['field'])) {
        $sort[$ref] = $group . " / " . $opt['name'];
    } else {
        if (is_array($opt)) {
            $group = $opt['name'];
        } else {
            $group = $opt;
        }
    }
}
ksort($sort);
if (!isCommandLine()) {
    header("Content-type: text/plain");
}
print_r($sort);
