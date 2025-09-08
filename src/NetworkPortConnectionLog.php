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

/**
 * Store ports connections log
 *
 * FIXME This class should inherit from CommonDBRelation, as it is linked
 * to both 'networkports_id_source' and 'networkports_id_destination'
 */
class NetworkPortConnectionLog extends CommonDBChild
{
    public static $itemtype        = 'NetworkPort';
    public static $items_id        = 'networkports_id';
    public $dohistory              = false;

    public static function getTypeName($nb = 0)
    {
        return __('Port connection history');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $array_ret = [];

        if ($item::class === NetworkPort::class) {
            $cnt = countElementsInTable([static::getTable()], $this->getCriteria($item));
            $array_ret[] = self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $cnt, $item::class);
        }
        return $array_ret;
    }

    public function getCriteria(NetworkPort $netport)
    {
        return [
            'OR' => [
                'networkports_id_source'      => $netport->fields['id'],
                'networkports_id_destination' => $netport->fields['id'],
            ],
        ];
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item::class === NetworkPort::class && $item->getID() > 0) {
            $connectionlog = new self();
            $connectionlog->showForItem($item);
            return true;
        }
        return false;
    }

    public function showForItem(NetworkPort $netport, $user_filters = [])
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'   => static::getTable(),
            'WHERE'  => $this->getCriteria($netport),
        ]);

        $entries = [];
        foreach ($iterator as $row) {
            if ($row['connected'] === 1) {
                $co_class = 'ti-link netport text-success';
                $title = __s('Connected');
            } else {
                $co_class = 'ti-unlink netport text-danger';
                $title = __s('Not connected');
            }

            $is_source = $netport->fields['id'] === $row['networkports_id_source'];
            $netports_id = $row[($is_source ? 'networkports_id_destination' : 'networkports_id_source')];

            $cport = new NetworkPort();
            if ($cport->getFromDB($netports_id)) {
                $citem = getItemForItemtype($cport->fields["itemtype"]);
                $citem->getFromDB($cport->fields["items_id"]);

                $cport_link = sprintf(
                    '<a href="%1$s">%2$s</a>',
                    htmlescape($cport::getFormURLWithID($cport->fields['id'])),
                    htmlescape(trim($cport->fields['name']) === '' ? __('Without name') : $cport->fields['name'])
                );

                $entries = [
                    'status' => '<i class="ti ' . $co_class . '" title="' . $title . '"></i>',
                    'date' => $row['date'],
                    'connected_item' => sprintf(
                        __s('%1$s on %2$s'),
                        $cport_link,
                        $citem->getLink()
                    ),
                ];
            } elseif ($row['connected'] === 1) {
                $entries[] = [
                    'status' => __s('No longer exists in database'),
                    'date' => $row['date'],
                    'connected_item' => __s('Unknown'),
                ];
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'status' => _n('State', 'States', 1),
                'date' => _n('Date', 'Dates', 1),
                'connected_item' => __('Connected item'),
            ],
            'formatters' => [
                'status' => 'raw_html',
                'date' => 'datetime',
                'connected_item' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    public static function getIcon()
    {
        return 'ti ti-history';
    }
}
