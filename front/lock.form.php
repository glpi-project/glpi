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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Plugin\Hooks;

global $CFG_GLPI;

/**
 * @since 0.84
 */

if (isset($_POST['itemtype'])) {
    $source_item = getItemForItemtype($_POST['itemtype']);
    if ($source_item->can($_POST['id'], UPDATE)) {
        $devices = Item_Devices::getDeviceTypes();
        $actions = array_merge($CFG_GLPI['inventory_lockable_objects'], array_values($devices));

        if (isset($_POST["unlock"])) {
            foreach ($actions as $type) {
                if (isset($_POST[$type]) && count($_POST[$type])) {
                    $item = getItemForItemtype($type);
                    foreach (array_keys($_POST[$type]) as $key) {
                        if (!$item->can($key, UPDATE)) {
                            Session::addMessageAfterRedirect(
                                htmlescape(sprintf(
                                    __('You do not have rights to restore %s item.'),
                                    $type
                                )),
                                true,
                                ERROR
                            );
                            continue;
                        }

                        //Force unlock
                        $item->restore(['id' => $key]);
                    }
                }
            }

            //Execute hook to unlock fields managed by a plugin, if needed
            Plugin::doHookFunction(Hooks::UNLOCK_FIELDS, $_POST);
        } elseif (isset($_POST["purge"])) {
            foreach ($actions as $type) {
                if (isset($_POST[$type]) && count($_POST[$type])) {
                    $item = getItemForItemtype($type);
                    foreach (array_keys($_POST[$type]) as $key) {
                        if (!$item->can($key, PURGE)) {
                            Session::addMessageAfterRedirect(
                                htmlescape(sprintf(
                                    __('You do not have rights to delete %s item.'),
                                    $type
                                )),
                                true,
                                ERROR
                            );
                            continue;
                        }

                        //Force unlock
                        $item->delete(['id' => $key], true);
                    }
                }
            }
        }
    }
}

Html::back();
