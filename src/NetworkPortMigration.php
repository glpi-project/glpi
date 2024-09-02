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

/// NetworkPortMigration class : class of unknown objects defined inside the NetworkPort before 0.84
/// @since 0.84
class NetworkPortMigration extends CommonDBChild
{
   // From CommonDBChild
    public static $itemtype        = 'itemtype';
    public static $items_id        = 'items_id';
    public static $mustBeAttached  = true;

    public static $rightname              = 'networking';



    public static function getTypeName($nb = 0)
    {
        return __('Network port migration');
    }


    public static function canCreate()
    {
        return false;
    }


    private function cleanDatabase()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $networkport = new NetworkPort();

        if ($networkport->getFromDB($this->getID())) {
            if (
                !in_array(
                    $networkport->fields['instantiation_type'],
                    NetworkPort::getNetworkPortInstantiations()
                )
            ) {
                $networkport->delete($networkport->fields);
            }
        }

        if (countElementsInTable($this->getTable()) == 0) {
            $DB->dropTable($this->getTable());
        }
    }


    public function post_purgeItem()
    {

        $this->cleanDatabase();
        parent::post_purgeItem();
    }


    public function post_deleteItem()
    {

        $this->cleanDatabase();
        parent::post_deleteItem();
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);
        return $ong;
    }


    public static function getMotives()
    {

        return [ 'unknown_interface_type'
                              => __('Undefined interface'),
            'invalid_network'
                              => __('Invalid network (already defined or with invalid addresses)'),
            'invalid_gateway'
                              => __('Gateway not include inside the network'),
            'invalid_address'
                              => __('Invalid IP address')
        ];
    }


    public function showForm($ID, array $options = [])
    {
        /** @var \DBmysql $DB */
        global $DB;

        if (!self::canView()) {
            return false;
        }

        $this->check($ID, READ);

        $recursiveItems = $this->recursivelyGetItems();
        if (count($recursiveItems) > 0) {
            $lastItem             = $recursiveItems[count($recursiveItems) - 1];
            $lastItem_entities_id = $lastItem->getField('entities_id');
        } else {
            $lastItem_entities_id = $_SESSION['glpiactive_entity'];
        }

        $options['entities_id'] = $lastItem_entities_id;
        $this->showFormHeader($options);

        $options['canedit'] = false;
        $options['candel'] = false;

        $number_errors = 0;
        foreach (self::getMotives() as $key => $name) {
            if ($this->fields[$key] == 1) {
                $number_errors++;
            }
        }

        $motives = self::getMotives();

        $interface_cell = "td";
        $address_cell   = "td";
        $network_cell   = "td";
        $gateway_cell   = "td";

        $address = new IPAddress();
        $netmask = new IPNetmask();
        $network = new IPNetwork();

        $number_real_errors = 0;


        if (
            (!$address->setAddressFromString($this->fields['ip']))
            || (!$netmask->setNetmaskFromString(
                $this->fields['netmask'],
                $address->getVersion()
            ))
        ) {
            $address = null;
            $netmask = null;
            $network = null;
        } else {
            $params = ["address" => $address,
                "netmask" => $netmask
            ];
            if (isset($this->fields["address"])) {
                $params["exclude IDs"] = $this->fields["address"];
            }

            if (isset($this->fields["entities_id"])) {
                $entity = $this->fields["entities_id"];
            } else {
                $entity = -1;
            }
            $networkports_ids = IPNetwork::searchNetworks("equals", $params, $entity, false);

            if (count($networkports_ids) == 0) {
                $network = null;
            } else {
                $network->getFromDB($networkports_ids[0]);
            }
        }

        if ($this->fields['unknown_interface_type'] == 1) {
            $options['canedit'] = true;
            $number_real_errors++;
            $interface_cell = "th";

            echo "<tr class='tab_bg_1'><th>" . $motives['unknown_interface_type'] . "</th>\n" .
              "<td>" . __('Transform this network port to');
            echo "</td><td colspan=2>";
            Dropdown::showItemTypes(
                'transform_to',
                NetworkPort::getNetworkPortInstantiations(),
                ['value' => "NetworkPortEthernet"]
            );

            echo "</td></tr>\n";
        }

        if ($this->fields['invalid_network'] == 1) {
            $number_real_errors++;
            $network_cell = "th";
            $address_cell = "th";
            echo "<tr class='tab_bg_1'><th>" . $motives['invalid_network'] . "</th>\n<td colspan=3>";
            if ($network !== null) {
                printf(__('Network port information conflicting with %s'), $network->getLink());
            } else {
                if ($address === null || $netmask === null) {
                    echo __('Invalid address or netmask');
                } else {
                    echo __('No conflicting network');
                }
                echo "&nbsp;<a href='" . Toolbox::getItemTypeFormURL('IPNetwork') . "'>" .
                  __('you may have to add a network') . "</a>";
            }
            echo "</td></tr>\n";
        }

        if ($this->fields['invalid_gateway'] == 1) {
            $number_real_errors++;
            $gateway_cell = "th";
            echo "<tr class='tab_bg_1'><th>" . $motives['invalid_gateway'] . "</th>\n<td colspan=3>";
            if ($network !== null) {
                printf(__('Append a correct gateway to the network %s'), $network->getLink());
            } else {
                printf(
                    __('%1$s: %2$s'),
                    __('Unknown network'),
                    "<a href='" . Toolbox::getItemTypeFormURL('IPNetwork') . "'>" . __('Add a network') . "
                    </a>"
                );
            }
            echo "</td></tr>\n";
        }

        if ($this->fields['invalid_address'] == 1) {
            $number_real_errors++;
            $address_cell = "th";
            echo "<tr class='tab_bg_1'><th>" . $motives['invalid_address'] . "</th>\n<td colspan=3>";
            $networkPort = new NetworkPort();
            if ($networkPort->getFromDB($this->getID())) {
                $number_real_errors++;
                echo "<a href='" . $networkPort->getLinkURL() . "'>" .
                   __('Add a correct IP to the network port') . "</a>";
            } else {
                echo __('Unknown network port');
            }
            echo "</td></tr>\n";
        }

        if ($number_real_errors == 0) {
            echo "<tr class='tab_bg_1'><th colspan='3'>" .
              __('I don\'t understand why this migration error is not deleted.');
            echo "</th><th>";
            Html::showSimpleForm(
                $this->getFormURL(),
                'delete',
                __('You can delete this migration error'),
                ['id' => $this->getID()]
            );
            echo "</th></tr>\n";
        } else {
            echo "<tr class='tab_bg_1'><th>" . __('At all events') . "</th>\n";
            echo "<td colspan='3'>";
            Html::showSimpleForm(
                $this->getFormURL(),
                'delete',
                __('You can delete this migration error'),
                ['id' => $this->getID()]
            );

            echo "</td></tr>\n";
        }

        echo "<tr class='tab_bg_1'><td colspan='4'>&nbsp;</td></tr>\n";

        echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Original network port information') . "</th>" .
           "</tr>\n";

        echo "<tr class='tab_bg_1'><td>";
        $this->displayRecursiveItems($recursiveItems, 'Type');
        echo "</td>\n<td>";
        $this->displayRecursiveItems($recursiveItems, "Link");
        echo "</td>\n";

        echo "<td>" . __('Comments') . "</td>";
        echo "<td class='middle'>" . $this->fields["comment"] . "</td></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . __('Network address') . "</td>\n";
        echo "<$network_cell>" . $this->fields['subnet'] . "</$network_cell>\n";

        echo "<td>" . IPNetmask::getTypeName(1) . "</td>\n";
        echo "<$network_cell>" . $this->fields['netmask'] . "</$network_cell></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . IPAddress::getTypeName(1) . "</td>\n";
        echo "<$address_cell>" . $this->fields['ip'] . "</$address_cell>\n";

        echo "<td>" . __('Gateway') . "</td>\n";
        echo "<$gateway_cell>" . $this->fields['gateway'] . "</$gateway_cell></tr>\n";

        echo "<tr class='tab_bg_1'><td>" . NetworkInterface::getTypeName(1) . "</td><$interface_cell>\n";
        $iterator = $DB->request([
            'SELECT' => 'name',
            'FROM'   => 'glpi_networkinterfaces',
            'WHERE'  => ['id' => $this->fields['networkinterfaces_id']]
        ]);
        if (count($iterator)) {
            $row = $iterator->current();
            echo $row['name'];
        } else {
            echo __('Unknown interface');
        }
        echo "</$interface_cell>";
        echo "<$interface_cell></$interface_cell>";
        echo "<$interface_cell></$interface_cell></tr>\n";

        $this->showFormButtons($options);

        return true;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);
        if ($isadmin) {
            $actions[__CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'transform_to']
            = __('Transform this network port to');
        }
        return $actions;
    }


    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'transform_to':
                Dropdown::showItemTypes(
                    'transform_to',
                    NetworkPort::getNetworkPortInstantiations(),
                    ['value' => 'NetworkPortEthernet']
                );
                echo "<br><br>";
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']) . "</span>";
                return true;
        }
        return parent::showMassiveActionsSubForm($ma);
    }


    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {
        switch ($ma->getAction()) {
            case 'transform_to':
                $input = $ma->getInput();
                if (isset($input["transform_to"]) && !empty($input["transform_to"])) {
                    $networkport = new NetworkPort();
                    foreach ($ids as $id) {
                        if (
                            $networkport->canEdit($id)
                            && $item->can($id, DELETE)
                        ) {
                            if (empty($networkport->fields['instantiation_type'])) {
                                if ($networkport->switchInstantiationType($input['transform_to']) !== false) {
                                       $instantiation             = $networkport->getInstantiation();
                                       $input2                    = $item->fields;
                                       $input2['networkports_id'] = $input2['id'];
                                       unset($input2['id']);
                                    if ($instantiation->add($input2)) {
                                        $item->delete(['id' => $id]);
                                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_OK);
                                    } else {
                                        $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                        $ma->addMessage($networkport->getErrorMessage(ERROR_ON_ACTION));
                                    }
                                } else {
                                     $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                     $ma->addMessage($networkport->getErrorMessage(ERROR_ON_ACTION));
                                }
                            } else {
                                $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_KO);
                                $ma->addMessage($networkport->getErrorMessage(ERROR_ON_ACTION));
                            }
                        } else {
                            $ma->itemDone($item->getType(), $id, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($networkport->getErrorMessage(ERROR_RIGHT));
                        }
                    }
                } else {
                    $ma->itemDone($item->getType(), $ids, MassiveAction::ACTION_KO);
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $optionIndex = 10;
       // From 10 to 14
        foreach (self::getMotives() as $motive => $name) {
            $tab[] = [
                'id'                 => $optionIndex,
                'table'              => $this->getTable(),
                'field'              => $motive,
                'name'               => $name,
                'datatype'           => 'bool'
            ];

            $optionIndex++;
        }

        $tab[] = [
            'id'                 => '20',
            'table'              => $this->getTable(),
            'field'              => 'ip',
            'datatype'           => 'ip',
            'name'               => IPAddress::getTypeName(1)
        ];

        $tab[] = [
            'id'                 => '21',
            'table'              => $this->getTable(),
            'field'              => 'netmask',
            'datatype'           => 'string',
            'name'               => IPNetmask::getTypeName(1)
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => $this->getTable(),
            'field'              => 'subnet',
            'datatype'           => 'string',
            'name'               => __('Network address')
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => $this->getTable(),
            'field'              => 'gateway',
            'datatype'           => 'string',
            'name'               => IPAddress::getTypeName(1)
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_networkinterfaces',
            'field'              => 'name',
            'datatype'           => 'dropdown',
            'name'               => NetworkInterface::getTypeName(1)
        ];

        return $tab;
    }


    /**
     * @param $group           HTMLTableGroup object
     * @param $super           HTMLTableSuperHeader object
     * @param $options   array
     * @param $internet_super
     * @param $father
     **/
    public static function getMigrationInstantiationHTMLTableHeaders(
        HTMLTableGroup $group,
        HTMLTableSuperHeader $super,
        ?HTMLTableSuperHeader $internet_super = null,
        ?HTMLTableHeader $father = null,
        array $options = []
    ) {
       // TODO : study to display the correct information for this undefined NetworkPort
        return null;
    }
}
