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

include ('../../inc/includes.php');

use Glpi\ContentTemplates\TemplateManager;
use Michelf\MarkdownExtra;

// Check mandatory parameter
$preset = $_GET['preset'] ?? null;
if (is_null($preset)) {
   Toolbox::throwError(400, "Missing mandatory 'preset' parameter", "string");
}

echo "<div id='page'>";
echo Html::includeHeader(__("Template variables documentation"));
echo "<div class='documentation documentation-large'>";

// Parse markdown
$md = new MarkdownExtra();
$md->header_id_func = function($headerName) {
   $headerName = str_replace(['(', ')'], '', $headerName);
   return rawurlencode(strtolower(strtr($headerName, [' ' => '-'])));
};
echo $md->transform(TemplateManager::generateMarkdownDocumentation($preset));

echo "</div>";
echo "</div>";

echo "<div class='documentation-footer'>";
echo "<div>";
Html::nullFooter();
echo "</div>";
