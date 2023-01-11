<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

use Glpi\Application\ErrorHandler;
use Glpi\UI\ThemeManager;

include('../inc/includes.php');

// Ensure warnings will not break image output.
ErrorHandler::getInstance()->disableOutput();

$theme = ThemeManager::getInstance()->getTheme($_GET['key']);
$preview = $theme !== null ? $theme->getPreviewPath(false) : null;

header_remove('Pragma');
header(sprintf('Content-Disposition: attachment; filename="%s.png"', basename($theme->getKey())));
header('Content-type: image/png');

if ($preview === null) {
    header('Cache-Control: no-cache');
    // Return blank PNG to prevent "broken image" display.
    $blank = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
    header(sprintf('Content-Length: %s', strlen($blank)));
    echo $blank;
    exit();
}

header('Cache-Control: public, max-age=2592000, must-revalidate'); // 1 month cache
header(sprintf('Content-Length: %s', filesize($preview)));
readfile($preview);
