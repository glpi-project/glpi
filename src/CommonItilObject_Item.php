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

/**
 * CommonItilObject_Item Class
 *
 * Relation between CommonItilObject_Item and Items
 */
abstract class CommonItilObject_Item extends CommonDBRelation
{
    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {

        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'items_id':
                if (strpos($values[$field], "_") !== false) {
                    $item_itemtype      = explode("_", $values[$field]);
                    $values['itemtype'] = $item_itemtype[0];
                    $values[$field]     = $item_itemtype[1];
                }

                if (isset($values['itemtype'])) {
                    if (isset($options['comments']) && $options['comments']) {
                        $tmp = Dropdown::getDropdownName(
                            getTableForItemType($values['itemtype']),
                            $values[$field],
                            1
                        );
                         return sprintf(
                             __('%1$s %2$s'),
                             $tmp['name'],
                             Html::showToolTip($tmp['comment'], ['display' => false])
                         );
                    }
                    return Dropdown::getDropdownName(
                        getTableForItemType($values['itemtype']),
                        $values[$field]
                    );
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        $options['display'] = false;
        switch ($field) {
            case 'items_id':
                if (isset($values['itemtype']) && !empty($values['itemtype'])) {
                    $options['name']  = $name;
                    $options['value'] = $values[$field];
                    return Dropdown::show($values['itemtype'], $options);
                } else {
                    static::dropdownAllDevices($name, 0, 0);
                    return ' ';
                }
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    public static function dropdownAllDevices(
        $myname,
        $itemtype,
        $items_id = 0,
        $admin = 0,
        $users_id = 0,
        $entity_restrict = -1,
        $options = []
    ) {
        global $CFG_GLPI;

        $params = [static::$items_id_1 => 0,
            'used'       => [],
            'multiple'   => 0,
            'rand'       => mt_rand()
        ];

        foreach ($options as $key => $val) {
            $params[$key] = $val;
        }

        $rand = $params['rand'];

        if ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] == 0) {
            echo "<input type='hidden' name='$myname' value=''>";
            echo "<input type='hidden' name='items_id' value='0'>";
        } else {
            echo "<div id='tracking_all_devices$rand' class='input-group mb-1'>";
            if (
                $_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(
                    2,
                    Ticket::HELPDESK_ALL_HARDWARE
                )
            ) {
               // Display a message if view my hardware
                if (
                    $users_id
                    && ($_SESSION["glpiactiveprofile"]["helpdesk_hardware"] & pow(
                        2,
                        Ticket::HELPDESK_MY_HARDWARE
                    ))
                ) {
                    echo "<span class='input-group-text'>" . __('Or complete search') . "</span>";
                }

                $types = static::$itemtype_1::getAllTypesForHelpdesk();
                $emptylabel = __('General');
                if ($params[static::$items_id_1] > 0) {
                    $emptylabel = Dropdown::EMPTY_VALUE;
                }
                Dropdown::showItemTypes(
                    $myname,
                    array_keys($types),
                    ['emptylabel' => $emptylabel,
                        'value'      => $itemtype,
                        'rand'       => $rand,
                        'display_emptychoice' => true
                    ]
                );
                $found_type = isset($types[$itemtype]);

                $p = ['itemtype'        => '__VALUE__',
                    'entity_restrict' => $entity_restrict,
                    'admin'           => $admin,
                    'used'            => $params['used'],
                    'multiple'        => $params['multiple'],
                    'rand'            => $rand,
                    'myname'          => "add_items_id"
                ];

                Ajax::updateItemOnSelectEvent(
                    "dropdown_$myname$rand",
                    "results_$myname$rand",
                    $CFG_GLPI["root_doc"] .
                                             "/ajax/dropdownTrackingDeviceType.php",
                    $p
                );
                echo "<span id='results_$myname$rand'>\n";

               // Display default value if itemtype is displayed
                if (
                    $found_type
                    && $itemtype
                ) {
                    if (
                        ($item = getItemForItemtype($itemtype))
                        && $items_id
                    ) {
                        if ($item->getFromDB($items_id)) {
                            Dropdown::showFromArray(
                                'items_id',
                                [$items_id => $item->getName()],
                                ['value' => $items_id]
                            );
                        }
                    } else {
                        $p['itemtype'] = $itemtype;
                        echo "<script type='text/javascript' >\n";
                        echo "$(function() {";
                        Ajax::updateItemJsCode(
                            "results_$myname$rand",
                            $CFG_GLPI["root_doc"] .
                                      "/ajax/dropdownTrackingDeviceType.php",
                            $p
                        );
                        echo '});</script>';
                    }
                }
                echo "</span>\n";
            }
            echo "</div>";
        }
        return $rand;
    }
}
