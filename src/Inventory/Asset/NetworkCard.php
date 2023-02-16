<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @copyright 2010-2022 by the FusionInventory Development Team.
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

namespace Glpi\Inventory\Asset;

use CommonDBTM;
use Glpi\Inventory\Conf;

class NetworkCard extends Device
{
    use InventoryNetworkPort;

    /** @var Conf */
    private $conf;

    protected $extra_data = ['controllers' => null];
    protected $ignored = ['controllers' => null];
    private array $cards_macs = [];

    public function prepare(): array
    {
        $mapping = [
            'name'          => 'designation',
            'manufacturer'  => 'manufacturers_id',
            'macaddr'       => 'mac'
        ];
        $mapping_ports = [
            'description' => 'name',
            'macaddr'     => 'mac',
            'type'        => 'instantiation_type',
            'ipaddress'   => 'ip',
            'virtualdev'  => 'virtualdev',
            'ipsubnet'    => 'subnet',
            'ssid'        => 'ssid',
            'ipgateway'   => 'gateway',
            'ipmask'      => 'netmask',
            'ipdhcp'      => 'dhcpserver',
            'wwn'         => 'wwn',
            'speed'       => 'speed'
        ];
        $pcivendor = new \PCIVendor();

        foreach ($this->data as $k => &$val) {
            if (
                !property_exists($val, 'description')
                || ($val->virtualdev ?? 0) == 1 && $this->conf->component_networkcardvirtual == 0
            ) {
                unset($this->data[$k]);
                continue;
            }

            $val_port = clone $val;
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }

            if (property_exists($val, 'status')) {
                switch ($val->status) {
                    case 'up':
                        $val_port->ifinternalstatus = '1';
                        break;
                    case 'down':
                        $val_port->ifinternalstatus = '2';
                        break;
                    default:
                        $val_port->ifinternalstatus = null;
                }
            }
            if (property_exists($val, 'speed')) {
                if ((int)$val->speed > 0) {
                    $val_port->ifstatus = '1';  //up
                } else {
                    $val_port->ifstatus = '2';  //down
                }
            }

            if (isset($this->extra_data['controllers'])) {
                $found_controller = false;
               // Search in controller if find NAME = CONTROLLER TYPE
                foreach ($this->extra_data['controllers'] as $controller) {
                    if (
                        property_exists($controller, 'type')
                        && ($val->description == $controller->type
                        || strtolower($val->description . " controller") ==
                              strtolower($controller->type))
                        && !isset($this->ignored['controllers'][$controller->name])
                    ) {
                        $found_controller = $controller;
                        if (property_exists($val, 'macaddr')) {
                            $found_controller->macaddr = $val->macaddr;
                            break; //found, exit loop
                        }
                    }
                }

                if ($found_controller) {
                    if (property_exists($found_controller, 'pciid')) {
                        $exploded = explode(":", $found_controller->pciids);

                       //manufacturer
                        if ($pci_manufacturer = $pcivendor->getManufacturer($exploded[0])) {
                             $val->manufacturers_id = $pci_manufacturer;
                        }

                       //product name
                        if ($pci_product = $pcivendor->getProductName($exploded[0], $exploded[1])) {
                            $val->designation = $pci_product;
                        }
                    } else if (property_exists($found_controller, 'vendorid')) {
                       //manufacturer
                        if ($pci_manufacturer = $pcivendor->getManufacturer($found_controller->vendorid)) {
                            $val->manufacturers_id = $pci_manufacturer;
                        }

                        if (property_exists($found_controller, 'productid')) {
                           //product name
                            if ($pci_product = $pcivendor->getProductName($found_controller->vendorid, $found_controller->productid)) {
                                $val->designation = $pci_product;
                            }
                        }
                    }

                    if (property_exists($val, 'mac')) {
                        $val->mac = strtolower($val->mac);
                        $val->mac_default = $val->mac;
                    }

                    if (property_exists($val, 'name')) {
                        $this->ignored['controllers'][$val->name] = $val->name;
                    }
                } else {
                    unset($this->data[$k]);
                }
            }

            //network ports
            $ports = [];
            foreach ($mapping_ports as $origin => $dest) {
                if (property_exists($val_port, $origin)) {
                    $val_port->$dest = $val_port->$origin;
                }
            }

            if (
                (property_exists($val_port, 'name') && $val_port->name != '')
                || (property_exists($val_port, 'mac') && $val_port->mac != '')
            ) {
                $val_port->logical_number = 1;
                if (property_exists($val_port, 'virtualdev')) {
                    if ($val_port->virtualdev != 1) {
                        $val_port->virtualdev = 0;
                    } else {
                        $val_port->logical_number = 0;
                    }
                }

                if (property_exists($val_port, 'mac')) {
                    $val_port->mac = strtolower($val_port->mac);
                    $portkey = $val_port->name . '-' . $val_port->mac;
                } else {
                    $portkey = $val_port->name;
                }

                if (isset($this->ports[$portkey])) {
                    if (property_exists($val_port, 'ip') && $val_port->ip != '') {
                        if (!in_array($val_port->ip, $this->ports[$portkey]->ipaddress)) {
                             $this->ports[$portkey]->ipaddress[] = $val_port->ip;
                        }
                    }
                    if (property_exists($val_port, 'ipaddress6') && $val_port->ipaddress6 != '') {
                        if (!in_array($val_port->ipaddress6, $this->ports[$portkey]->ipaddress)) {
                            $this->ports[$portkey]->ipaddress[] = $val_port->ipaddress6;
                        }
                    }
                } else {
                    if (property_exists($val_port, 'ip')) {
                        if ($val_port->ip != '') {
                             $val_port->ipaddress = [$val_port->ip];
                        }
                        unset($val_port->ip);
                    } else if (property_exists($val_port, 'ipaddress6') && $val_port->ipaddress6 != '') {
                        $val_port->ipaddress = [$val_port->ipaddress6];
                    } else {
                        $val_port->ipaddress = [];
                    }

                    if (property_exists($val_port, 'instantiation_type')) {
                        switch ($val_port->instantiation_type) {
                            case 'Ethernet':
                                $val_port->instantiation_type = 'NetworkPortEthernet';
                                break;
                            case 'wifi':
                                $val_port->instantiation_type = 'NetworkPortWifi';
                                break;
                            case 'fibrechannel':
                            case 'fiberchannel':
                                $val_port->instantiation_type = 'NetworkPortFiberchannel';
                                break;
                            default:
                                if (property_exists($val_port, 'wwn') && !empty($val_port->wwn)) {
                                    $val_port->instantiation_type = 'NetworkPortFiberchannel';
                                } else if (property_exists($val_port, 'mac') && $val_port->mac != '') {
                                    $val_port->instantiation_type = 'NetworkPortEthernet';
                                } else {
                                    $val_port->instantiation_type = 'NetworkPortLocal';
                                }
                                break;
                        }
                    }

                  // Test if the provided network speed is an integer number
                    if (
                        property_exists($val_port, 'speed')
                        && ctype_digit(strval($val_port->speed))
                    ) {
                       // Old agent version have speed in b/s instead Mb/s
                        if ($val_port->speed > 100000) {
                            $val_port->speed = $val_port->speed / 1000000;
                        }
                    } else {
                        $val_port->speed = 0;
                    }

                    $uniq = '';
                    if (property_exists($val_port, 'mac') && !empty($val_port->mac)) {
                        $uniq = $val_port->mac;
                    } else if (property_exists($val_port, 'wwn') && !empty($val_port->wwn)) {
                        $uniq = $val_port->wwn;
                    }
                    $ports[$val_port->name . '-' . $uniq] = $val_port;
                }
                $this->ports += $ports;
            }

            //check for mac unicity among cards
            if (property_exists($val, 'mac')) {
                if (in_array($val->mac, $this->cards_macs)) {
                    unset($this->data[$k]);
                    continue;
                } else {
                    $this->cards_macs[] = $val->mac;
                }
            }
        }
        return $this->data;
    }

    public function checkConf(Conf $conf): bool
    {
        $this->conf = $conf;
        return $conf->component_networkcard == 1;
    }

    public function handlePorts($itemtype = null, $items_id = null)
    {
       //ports are handled from main asset in NetworkCard case
        return;
    }

    public function getItemtype(): string
    {
        return \Item_DeviceNetworkCard::class;
    }
}
