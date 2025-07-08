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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$itemtype = $_POST['itemtype'];
$ent = new Entity();
// Get suffix for entity config fields. For backwards compatibility, ticket values have no suffix.
$config_suffix = $itemtype::getType() === 'Ticket' ? '' : ('_' . strtolower($itemtype::getType()));

if (isset($_POST['inquest_config' . $config_suffix], $_POST['entities_id'])) {
    if ($ent->getFromDB($_POST['entities_id'])) {
        if (!$ent->canViewItem()) {
            throw new AccessDeniedHttpException();
        }
        $inquest_delay             = $ent->getfield('inquest_delay' . $config_suffix);
        $inquest_rate              = $ent->getfield('inquest_rate' . $config_suffix);
        $inquest_duration          = $ent->getfield('inquest_duration' . $config_suffix);
        $inquest_max_rate          = $ent->getfield('inquest_max_rate' . $config_suffix);
        $inquest_default_rate      = $ent->getfield('inquest_default_rate' . $config_suffix);
        $inquest_mandatory_comment = $ent->getfield('inquest_mandatory_comment' . $config_suffix);
        $max_closedate             = $ent->getfield('max_closedate' . $config_suffix);
    } else {
        $inquest_delay             = -1;
        $inquest_rate              = -1;
        $inquest_duration          = 0;
        $inquest_default_rate      = 3;
        $inquest_max_rate          = 5;
        $inquest_mandatory_comment = 0;
        $max_closedate             = '';
    }

    if ((int) $_POST['inquest_config' . $config_suffix] > 0) {
        TemplateRenderer::getInstance()->display('pages/admin/entity/survey_config.html.twig', [
            'itemtype' => $itemtype,
            'inquest_config' => $_POST['inquest_config' . $config_suffix],
            'config_suffix' => $config_suffix,
            'inquest_delay' => $inquest_delay,
            'inquest_rate' => $inquest_rate,
            'inquest_duration' => $inquest_duration,
            'inquest_default_rate' => $inquest_default_rate,
            'inquest_max_rate' => $inquest_max_rate,
            'inquest_mandatory_comment' => $inquest_mandatory_comment,
            'max_closedate' => $max_closedate,
            'inquest_URL' => $ent->fields['inquest_URL' . $config_suffix] ?? '',
        ]);
    }
}
