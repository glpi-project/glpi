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

namespace Glpi\Search\Output;

use Glpi\Toolbox\URL;
use Toolbox;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class MapSearchOutput extends HTMLSearchOutput
{
    public static function prepareInputParams(string $itemtype, array $params): array
    {
        $params = parent::prepareInputParams($itemtype, $params);

        if ($itemtype === 'Location') {
            $latitude = 21;
            $longitude = 20;
        } elseif ($itemtype === 'Entity') {
            $latitude = 67;
            $longitude = 68;
        } else {
            $latitude = 998;
            $longitude = 999;
        }

        $params['criteria'][] = [
            'link'         => 'AND NOT',
            'field'        => $latitude,
            'searchtype'   => 'contains',
            'value'        => 'NULL',
            '_hidden'      => true,
        ];
        $params['criteria'][] = [
            'link'         => 'AND NOT',
            'field'        => $longitude,
            'searchtype'   => 'contains',
            'value'        => 'NULL',
            '_hidden'      => true,
        ];

        return $params;
    }

    public function displayData(array $data, array $params = []): void
    {
        global $CFG_GLPI;

        $itemtype = $data['itemtype'];
        if (isset($data['data']['totalcount']) && $data['data']['totalcount'] > 0) {
            $target = URL::sanitizeURL($data['search']['target']);
            $criteria = $data['search']['criteria'];
            array_pop($criteria);
            array_pop($criteria);
            $criteria[] = [
                'link'         => 'AND',
                'field'        => ($itemtype === 'Location' || $itemtype === 'Entity') ? 1 : (($itemtype === 'Ticket') ? 83 : 3),
                'searchtype'   => 'equals',
                'value'        => 'CURLOCATION',
            ];

            $parameters = Toolbox::append_params(
                [
                    'as_map'       => 0,
                    'criteria'     => $criteria,
                    'metacriteria' => $data['search']['metacriteria'],
                    'sort'         => $data['search']['sort'],
                    'order'        => $data['search']['order'],
                ]
            );
            if (!str_contains($target, '?')) {
                $fulltarget = $target . "?" . $parameters;
            } else {
                $fulltarget = $target . "&" . $parameters;
            }

            $typename = class_exists($itemtype) ? $itemtype::getTypeName($data['data']['totalcount']) : $itemtype;

            $twig_params = [
                'ajax_url' => $CFG_GLPI['root_doc'] . '/ajax/map.php',
                'params'   => $params,
                'fulltarget' => $fulltarget,
                'typename' => $typename,
                'itemtype' => $itemtype,
            ];
        }
        parent::displayData($data, $params + ['extra_twig_params' => $twig_params ?? []]);
    }
}
