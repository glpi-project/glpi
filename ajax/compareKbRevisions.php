<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
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
 * @since 9.1
 */

include ('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (!isset($_POST['kbid']) || !isset($_POST['oldid']) || !isset($_POST['diffid'])) {
   throw new \RuntimeException('Required argument missing!');
}

$oldid = $_POST['oldid'];
$diffid = $_POST['diffid'];
$kbid = $_POST['kbid'];

$revision = new KnowbaseItem_Revision();
$revision->getFromDB($oldid);
$old = [
   'name'   => $revision->fields['name'],
   'answer' => Toolbox::unclean_html_cross_side_scripting_deep($revision->fields['answer'])
];

$revision = $diffid == 0 ? new KnowbaseItem() : new KnowbaseItem_Revision();
$revision->getFromDB($diffid == 0 ? $kbid : $diffid);
$diff = [
   'name'   => $revision->fields['name'],
   'answer' => Toolbox::unclean_html_cross_side_scripting_deep($revision->fields['answer'])
];

echo json_encode([
   'old'  => $old,
   'diff' => $diff
]);
