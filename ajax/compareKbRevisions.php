<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use function Safe\preg_replace;

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

$normalize_html = static function (string $html): string {
    if ($html === '') {
        return $html;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $dom->loadHTML('<html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>');
    libxml_clear_errors();

    $xpath = new DOMXPath($dom);
    $tables = $xpath->query('//table');
    if ($tables !== false) {
        foreach ($tables as $table) {
            /** @var DOMElement $table */
            $table->removeAttribute('width');
            $style = $table->getAttribute('style');
            $style = preg_replace('/\b(?:min-)?width\s*:[^;]+;?\s*/i', '', $style);
            $style = rtrim($style, '; ') . '; max-width: 100%; box-sizing: border-box;';
            $table->setAttribute('style', ltrim($style, '; '));
        }
    }

    $imgs = $xpath->query('//img');
    if ($imgs !== false) {
        foreach ($imgs as $img) {
            /** @var DOMElement $img */
            $style = $img->getAttribute('style');
            $style = preg_replace('/\bmax-width\s*:[^;]+;?\s*/i', '', $style);
            $style = rtrim($style, '; ') . '; max-width: 100%; height: auto;';
            $img->setAttribute('style', ltrim($style, '; '));
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!($body instanceof DOMElement)) {
        return $html;
    }

    $result = '';
    foreach ($body->childNodes as $node) {
        $result .= $dom->saveHTML($node);
    }
    return $result;
};

$oldid = $_POST['oldid'];
$diffid = $_POST['diffid'];
$kbid = $_POST['kbid'];

$revision = new KnowbaseItem_Revision();
$revision->getFromDB($oldid);
$old = [
    'name'   => $revision->fields['name'],
    'answer' => $normalize_html(RichText::getSafeHtml($revision->fields['answer'])),
];

$revision = $diffid == 0 ? new KnowbaseItem() : new KnowbaseItem_Revision();
$revision->getFromDB($diffid == 0 ? $kbid : $diffid);
$diff = [
    'name'   => $revision->fields['name'],
    'answer' => $normalize_html(RichText::getSafeHtml($revision->fields['answer'])),
];

echo json_encode([
    'old'  => $old,
    'diff' => $diff,
]);
