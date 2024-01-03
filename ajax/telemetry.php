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

// Must be available during installation. This script already checks for permissions when the flag usually set by the installer is missing.
$SECURITY_STRATEGY = 'no_check';

include('../inc/includes.php');

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!($_SESSION['telemetry_from_install'] ?? false)) {
    Session::checkRight("config", READ);
}

echo Html::css("public/lib/prismjs.css");
echo Html::script("public/lib/prismjs.js");

$infos = Telemetry::getTelemetryInfos();
echo "<p>" . __("We only collect the following data: plugins usage, performance and responsiveness statistics about user interface features, memory, and hardware configuration.") . "</p>";
echo "<pre><code class='language-json'>";
echo json_encode($infos, JSON_PRETTY_PRINT);
echo "</code></pre>";
