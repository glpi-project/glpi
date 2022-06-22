<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
use Glpi\Toolbox\Sanitizer;

/**
 * Process Class
 **/
class Item_Process extends CommonDBChild
{
   // From CommonDBChild
    public static $itemtype = 'itemtype';
    public static $items_id = 'items_id';
    public $dohistory       = true;


    public static function getTypeName($nb = 0)
    {
        return _n('Process', 'Processes', $nb);
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item::canView()) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(
                    self::getTable(),
                    [
                        'items_id'     => $item->getID(),
                        'itemtype'     => $item->getType(),
                    ]
                );
            }
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        self::showForItem($item, $withtemplate);
        return true;
    }


    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $start       = intval(($_GET["start"] ?? 0));
        $filters     = $_GET['filters'] ?? [];
        $is_filtered = count($filters) > 0;
        //$sql_filters = self::convertFiltersValuesToSqlCriteria($filters);

        $raw_processes = self::getItemsAssociatedTo($item::class, $item->getID());
        $processes = [];
        foreach ($raw_processes as $process_object) {
            $process_object->fields['cpuusage'] = floor($process_object->fields['cpuusage'] * 100);
            $process_object->fields['memusage'] = floor($process_object->fields['memusage'] * 100);
            $process_object->fields['virtualmemory'] = $process_object->fields['virtualmemory'] * 1024;
            $processes[$process_object->getID()] = $process_object->fields;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'start' => $start,
            'filters' => Sanitizer::dbEscapeRecursive($filters),
            'columns' => [
                'cmd'           => __("Command"),
                'cpuusage'      => __("CPU Usage"),
                'memusage'      => __("Memory Usage"),
                'started'       => __("Started at"),
                'tty'           => __("TTY"),
                'user'          => __("User"),
                'virtualmemory' => __("Virtual memory"),
            ],
            'formatters' => [
                'cmd'           => 'longtext',
                'cpuusage'      => 'progress',
                'memusage'      => 'progress',
                'started'       => 'datetime',
                'user'          => 'userbadge',
                'virtualmemory' => 'bytesize',
            ],
            'entries' => $processes,
            'total_number' => count($processes),
            'filtered_number' => count($processes),
        ]);
    }


    public static function getIcon()
    {
        return "ti ti-bolt";
    }

}

