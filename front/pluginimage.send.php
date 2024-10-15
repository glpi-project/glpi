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

/**
 * Send image generated by a plugin to browser
 *
 *  Arguments :
 *  - plugin : name of the plugin, also the subdir in files/_plugins
 *  - name : of the image in the files/_plugins/xxxx dir
 *  - clean : delete the image after send it
 */

use Glpi\Event;
use Glpi\Exception\Http\AccessDeniedHttpException;

/** @var array $CFG_GLPI */
global $CFG_GLPI;

if (!isset($_GET["name"]) || !isset($_GET["plugin"]) || !Plugin::isPluginActive($_GET["plugin"])) {
    Event::log(
        0,
        "system",
        2,
        "security",
        //TRANS: %s is user name
        sprintf(__('%s makes a bad usage.'), $_SESSION["glpiname"])
    );
    throw new AccessDeniedHttpException();
}

$dir = GLPI_PLUGIN_DOC_DIR . "/" . $_GET["plugin"] . "/";
if (isset($_GET["folder"])) {
    $dir .= $_GET["folder"] . "/";
}
$filepath = $dir . $_GET["name"];

if (
    (basename($_GET["name"]) != $_GET["name"])
    || (basename($_GET["plugin"]) != $_GET["plugin"])
    || !str_starts_with(realpath($filepath), realpath(GLPI_PLUGIN_DOC_DIR))
    || !Document::isImage($filepath)
) {
    Event::log(
        0,
        "system",
        1,
        "security",
        sprintf(__('%s tries to use a non standard path.'), $_SESSION["glpiname"])
    );
    throw new AccessDeniedHttpException();
}

// Now send the file with header() magic
header("Expires: Sun, 30 Jan 1966 06:30:00 GMT");
header('Pragma: private'); /// IE BUG + SSL
header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
header('Content-disposition: filename="' . $_GET["name"] . '"');

if (file_exists($filepath)) {
    header("Content-type: " . Toolbox::getMime($filepath));
    readfile($filepath);
} else {
    header("Content-type: image/png");
    readfile($CFG_GLPI['root_doc'] . "/pics/warning.png");
}
