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

/**
 * @since 9.1
 */

use Glpi\RichText\RichText;

use function Safe\json_encode;

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkCentralAccess();

if (!isset($_POST['kbid']) || !isset($_POST['oldid']) || !isset($_POST['diffid'])) {
    throw new RuntimeException('Required argument missing!');
}

$item = new KnowbaseItem();
if (!$item->getFromDB($_POST['kbid']) || !$item->can($_POST['kbid'], READ)) {
    return;
}

$oldid = $_POST['oldid'];
$diffid = $_POST['diffid'];
$kbid = $_POST['kbid'];

$revision = new KnowbaseItem_Revision();
$revision->getFromDB($oldid);
$old = [
    'name'   => $revision->fields['name'],
    'answer' => RichText::getSafeHtml($revision->fields['answer']),
];

$revision = $diffid == 0 ? new KnowbaseItem() : new KnowbaseItem_Revision();
$revision->getFromDB($diffid == 0 ? $kbid : $diffid);
$diff = [
    'name'   => $revision->fields['name'],
    'answer' => RichText::getSafeHtml($revision->fields['answer']),
];

echo json_encode([
    'old'  => $old,
    'diff' => $diff,
]);
