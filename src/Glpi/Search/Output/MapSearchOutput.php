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

namespace Glpi\Search\Output;

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
        } else if ($itemtype === 'Entity') {
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
            '_hidden'      => true
        ];
        $params['criteria'][] = [
            'link'         => 'AND NOT',
            'field'        => $longitude,
            'searchtype'   => 'contains',
            'value'        => 'NULL',
            '_hidden'      => true
        ];

        return $params;
    }

    public function displayData(array $data, array $params = []): void
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $itemtype = $data['itemtype'];
        if (isset($data['data']['totalcount']) && $data['data']['totalcount'] > 0) {
            $target = $data['search']['target'];
            $criteria = $data['search']['criteria'];
            array_pop($criteria);
            array_pop($criteria);
            $criteria[] = [
                'link'         => 'AND',
                'field'        => ($itemtype === 'Location' || $itemtype === 'Entity') ? 1 : (($itemtype === 'Ticket') ? 83 : 3),
                'searchtype'   => 'equals',
                'value'        => 'CURLOCATION'
            ];
            $globallinkto = \Toolbox::append_params(
                [
                    'criteria'     => $criteria,
                    'metacriteria' => $data['search']['metacriteria'],
                ],
                '&amp;'
            );
            $sort_params = \Toolbox::append_params([
                'sort'   => $data['search']['sort'],
                'order'  => $data['search']['order']
            ], '&amp;');
            $parameters = "as_map=0&amp;" . $sort_params . '&amp;' .
                $globallinkto;

            if (strpos($target, '?') == false) {
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
