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

class Item_DeviceCamera_ImageResolution extends CommonDBRelation
{
    public static $itemtype_1 = 'Item_DeviceCamera';
    public static $items_id_1 = 'items_devicecameras_id';

    public static $itemtype_2 = 'ImageResolution';
    public static $items_id_2 = 'imageresolutions_id';

    public static function getTypeName($nb = 0)
    {
        return _nx('camera', 'Resolution', 'Resolutions', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        if ($item instanceof CommonDBTM && $_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(
                self::getTable(),
                [
                    'items_devicecameras_id' => $item->getID(),
                ]
            );
        }
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof DeviceCamera) {
            return false;
        }
        return self::showItems($item);
    }

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'MassiveAction:update';
        $forbidden[] = 'CommonDBConnexity:affect';
        $forbidden[] = 'CommonDBConnexity:unaffect';

        return $forbidden;
    }

    /**
     * Print items
     * @param  DeviceCamera $camera the current camera instance
     * @return bool
     */
    public static function showItems(DeviceCamera $camera): bool
    {
        global $DB;

        $ID = $camera->getID();
        $rand = mt_rand();

        if (
            !$camera->getFromDB($ID)
            || !$camera->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $camera->canEdit($ID);

        $items = $DB->request([
            'SELECT' => [self::getTable() . '.*', ImageResolution::getTable() . '.is_video'],
            'FROM'   => self::getTable(),
            'LEFT JOIN' => [
                ImageResolution::getTable() => [
                    'ON' => [
                        ImageResolution::getTable() => 'id',
                        self::getTable() => 'imageresolutions_id',
                    ],
                ],
            ],
            'WHERE'  => [
                'items_devicecameras_id' => $camera->getID(),
            ],
        ]);

        $entries = [];
        foreach ($items as $row) {
            $item = new ImageResolution();
            $item->getFromDB($row['imageresolutions_id']);

            $entries[] = [
                'itemtype' => self::class,
                'id' => $row['id'],
                'imageresolutions_id' => $item->getLink(),
                'is_video' => Dropdown::getYesNo($row['is_video']),
                'is_dynamic' => $row['is_dynamic'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'imageresolutions_id' => ImageResolution::getTypeName(1),
                'is_video' => __('Is Video'),
                'is_dynamic' => __('Is dynamic'),
            ],
            'formatters' => [
                'imageresolutions_id' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);

        return true;
    }
}
