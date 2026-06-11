<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

namespace Glpi\Inventory\Asset;

use Glpi\Inventory\Conf;
use Item_DeviceSoundCard;

class SoundCard extends Device
{
    protected array $extra_data = ['usbdevices' => null];

    public function prepare(): array
    {
        $mapping = [
            'name'          => 'designation',
            'caption'       => 'designation',
            'manufacturer'  => 'manufacturers_id',
            'description'   => 'comment',
        ];

        $usb_vendor = new \USBVendor();

        /** @var \stdClass $val */
        foreach ($this->data as &$val) {
            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }
            $val->is_dynamic = 1;

            if (isset($this->extra_data['usbdevices'])) {
                $found_usb = false;
                $usbdevices = is_array($this->extra_data['usbdevices']) ? $this->extra_data['usbdevices'] : [$this->extra_data['usbdevices']];
                foreach ($usbdevices as $usbdevice) {
                    /** @var \stdClass $usbdevice */
                    $match_name = property_exists($usbdevice, 'name') && property_exists($val, 'name') && $usbdevice->name === $val->name;

                    if ($match_name) {
                        $found_usb = $usbdevice;
                        break;
                    }
                }

                if ($found_usb) {
                    if (property_exists($found_usb, 'vendorid') && property_exists($found_usb, 'productid')) {
                        $manufacturer = $usb_vendor->getManufacturer($found_usb->vendorid);
                        if ($manufacturer !== false) {
                            $val->manufacturers_id = $manufacturer;
                        }
                        $product_name = $usb_vendor->getProductName($found_usb->vendorid, $found_usb->productid);
                        if ($product_name !== false) {
                            $val->designation = $product_name;
                        }
                        $val->devicesoundcardmodels_id = $val->designation;
                    }
                }
            }
        }
        return $this->data;
    }

    public function checkConf(Conf $conf): bool
    {
        return $conf->component_soundcard == 1 && parent::checkConf($conf);
    }

    public function getItemtype(): string
    {
        return Item_DeviceSoundCard::class;
    }
}
