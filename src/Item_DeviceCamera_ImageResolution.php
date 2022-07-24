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

class Item_DeviceCamera_ImageResolution extends CommonDBRelation
{
    public static $itemtype_1 = 'Item_DeviceCamera';
    public static $items_id_1 = 'item_devicecameras_id';

    public static $itemtype_2 = 'ImageResolution';
    public static $items_id_2 = 'imageresolutions_id';

    public static function getTypeName($nb = 0)
    {
        return _nx('camera', 'Resolution', 'Resolutions', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $nb = 0;
        switch ($item->getType()) {
            default:
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = countElementsInTable(
                        self::getTable(),
                        [
                            'item_devicecameras_id' => $item->getID()
                        ]
                    );
                }
                return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        self::showItems($item, $withtemplate);
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
     * @return void
     */
    public static function showItems(DeviceCamera $camera)
    {
        global $DB, $CFG_GLPI;

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
                        self::getTable() => 'imageresolutions_id'
                    ]
                ]
            ],
            'WHERE'  => [
                'item_devicecameras_id' => $camera->getID()
            ]
        ]);
        $link = new self();

        echo "<div>";

        if (!count($items)) {
            echo "<table class='tab_cadre_fixe'><tr><th>" . __('No item found') . "</th></tr>";
            echo "</table>";
        } else {
            Session::initNavigateListItems(
                self::getType(),
                //TRANS : %1$s is the itemtype name,
                //        %2$s is the name of the item (used for headings of a list)
                sprintf(
                    __('%1$s = %2$s'),
                    $camera->getTypeName(1),
                    $camera->getName()
                )
            );

            if ($canedit) {
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = [
                    'num_displayed'   => min($_SESSION['glpilist_limit'], count($items)),
                    'container'       => 'mass' . __CLASS__ . $rand
                ];
                Html::showMassiveActions($massiveactionparams);
            }

            echo "<table class='tab_cadre_fixehov'>";
            $header = "<tr>";
            if ($canedit) {
                $header .= "<th width='10'>";
                $header .= Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
                $header .= "</th>";
            }
            $header .= "<th>" . ImageResolution::getTypeName(1) . "</th>";
            $header .= "<th>" . __('Is Video') . "</th>";
            $header .= "<th>" . __('Is dynamic') . "</th>";
            $header .= "</tr>";

            echo $header;
            foreach ($items as $row) {
                $item = new ImageResolution();
                $item->getFromDB($row['imageresolutions_id']);
                echo "<tr lass='tab_bg_1'>";
                if ($canedit) {
                    echo "<td>";
                    Html::showMassiveActionCheckBox(__CLASS__, $row["id"]);
                    echo "</td>";
                }

                $is_video =  $row['is_video'] ? __('Yes') : __('No');

                echo "<td>" . $item->getLink() . "</td>";
                echo "<td>" . $is_video . "</td>";
                echo "<td>{$row['is_dynamic']}</td>";
                echo "</tr>";
            }
            echo $header;
            echo "</table>";

            if ($canedit && count($items)) {
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
            }
            if ($canedit) {
                Html::closeForm();
            }
        }

        echo "</div>";
    }
}
