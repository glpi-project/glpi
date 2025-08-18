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

namespace Glpi\Inventory\MainAsset;

use CommonDBTM;
use PrinterLog;
use PrinterModel;
use PrinterType;
use RuleDictionnaryPrinterCollection;
use stdClass;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\strtotime;

class Printer extends NetworkEquipment
{
    private $counters;

    public function __construct(CommonDBTM $item, $data)
    {
        $this->extra_data['pagecounters'] = null;
        parent::__construct($item, $data);
    }

    protected function getModelsFieldName(): string
    {
        return PrinterModel::getForeignKeyField();
    }

    protected function getTypesFieldName(): string
    {
        return PrinterType::getForeignKeyField();
    }

    public function prepare(): array
    {
        parent::prepare();

        if (!property_exists($this->raw_data->content ?? new stdClass(), 'network_device')) {
            $autoupdatesystems_id = $this->data[0]->autoupdatesystems_id;
            $this->data = [];
            foreach ($this->raw_data as $val) {
                $val->autoupdatesystems_id = $autoupdatesystems_id;
                $val->last_inventory_update = $_SESSION['glpi_currenttime'];
                $this->data[] = $val;
            }
        }

        $rulecollection = new RuleDictionnaryPrinterCollection();

        $mapping_pcounter = [
            'total'        => 'total_pages',
            'black'        => 'bw_pages',
            'color'        => 'color_pages',
            'duplex'       => 'rv_pages', //keep first, rectoverso is the standard and should be used if present
            'rectoverso'   => 'rv_pages',
            'scanned'      => 'scanned',
            'printtotal'   => 'prints',
            'printblack'   => 'bw_prints',
            'printcolor'   => 'color_prints',
            'copytotal'    => 'copies',
            'copyblack'    => 'bw_copies',
            'copycolor'    => 'color_copies',
            'faxtotal'     => 'faxed',
        ];

        foreach ($this->data as $k => &$val) {
            //no way to know if printer has a USB port but...
            $val->have_usb = 0;
            //inventoried printers certainly have ethernet
            $val->have_ethernet = 1;

            // Hack for USB Printer serial
            if (
                property_exists($val, 'serial')
                && preg_match('/\/$/', $val->serial)
            ) {
                $val->serial = preg_replace('/\/$/', '', $val->serial);
            }

            if (property_exists($val, 'ram')) {
                $val->memory_size = $val->ram;
                unset($val->ram);
            }

            if (property_exists($val, 'credentials')) {
                $val->snmpcredentials_id = $val->credentials;
                unset($val->credentials);
            }

            $res_rule = $rulecollection->processAllRules(['name' => $val->name]);
            if (
                (!isset($res_rule['_ignore_ocs_import']) || $res_rule['_ignore_ocs_import'] != "1")
                && (!isset($res_rule['_ignore_import']) || $res_rule['_ignore_import'] != "1")
            ) {
                if (isset($res_rule['name'])) {
                    $val->name = $res_rule['name'];
                }
                if (isset($res_rule['manufacturer'])) {
                    $val->manufacturers_id = $res_rule['manufacturer'];
                    $known_key = md5('manufacturers_id' . $res_rule['manufacturer']);
                    $this->known_links[$known_key] = $res_rule['manufacturer'];
                }

                if (isset($this->extra_data['pagecounters'])) {
                    $pcounter = (object) $this->extra_data['pagecounters'];
                    foreach ($mapping_pcounter as $origin => $dest) {
                        if (property_exists($pcounter, $origin)) {
                            $pcounter->$dest = $pcounter->$origin;
                        }

                        if (property_exists($pcounter, 'total_pages')) {
                            $val->last_pages_counter = $pcounter->total_pages;
                        }
                        $this->counters = $pcounter;
                    }
                }
            } else {
                unset($this->data[$k]);
            }
        }

        //try to know if management port IP is already known as IP port
        //if yes remove it from management port
        $known_ports = $port_managment = $this->getManagementPorts();
        if (isset($known_ports['management']) && property_exists($known_ports['management'], 'ipaddress')) {
            foreach ($known_ports['management']->ipaddress as $pa_ip_key => $pa_ip_val) {
                if (property_exists($this->raw_data->content, 'network_ports')) {
                    foreach ($this->raw_data->content->network_ports as $port_obj) {
                        if (property_exists($port_obj, 'ips')) {
                            foreach ($port_obj->ips as $port_ip) {
                                if ($pa_ip_val == $port_ip) {
                                    unset($port_managment['management']->ipaddress[$pa_ip_key]);
                                    if (empty($port_managment['management']->ipaddress)) {
                                        unset($port_managment['management']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->setManagementPorts($port_managment);

        return $this->data;
    }

    public function handle()
    {
        parent::handle();
        $this->handleMetrics();
    }

    /**
     * Get printer counters
     *
     * @return stdClass
     */
    public function getCounters(): stdClass
    {
        return $this->counters;
    }

    /**
     * Handle printer metrics
     *
     * @return void
     */
    public function handleMetrics()
    {
        if ($this->counters === null) {
            return;
        }

        $unicity_input = [
            'itemtype' => $this->item::class,
            'items_id' => $this->item->fields['id'],
            'date' => date('Y-m-d', strtotime($_SESSION['glpi_currenttime'])),
        ];
        $input = array_merge((array) $this->counters, $unicity_input);

        $metrics = new PrinterLog();
        if ($metrics->getFromDBByCrit($unicity_input)) {
            $input['id'] = $metrics->fields['id'];
            $metrics->update($input, false);
        } else {
            $metrics->add($input, [], false);
        }
    }
}
