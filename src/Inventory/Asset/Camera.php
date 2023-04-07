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
use Glpi\Toolbox\Sanitizer;
use Item_Devices;

class Camera extends Device
{
    public function prepare(): array
    {

        $mapping = [
            'manufacturer'    => 'manufacturers_id',
            'model'           => 'devicecameramodels_id',
            'designation'     => 'name'
        ];

        foreach ($this->data as &$val) {
            if (property_exists($val, 'flashunit')) {
                $val->flashunit = $val->flashunit ? 1 : 0;
            }

            foreach ($mapping as $origin => $dest) {
                if (property_exists($val, $origin)) {
                    $val->$dest = $val->$origin;
                }
            }
            $val->is_dynamic = 1;
        }

        return $this->data;
    }


    protected function itemdeviceAdded(Item_Devices $itemdevice, $val)
    {

       //handle resolutions
        if (property_exists($val, 'resolution')) {
            $this->handleResolution($itemdevice, $val->resolution);
        }

        if (property_exists($val, 'resolutionvideo')) {
            $this->handleResolution($itemdevice, $val->resolutionvideo, true);
        }

        if (property_exists($val, 'imageformats')) {
            $this->handleFormats($itemdevice, $val->imageformats);
        }
    }

    private function handleResolution($itemdevice, $val, $is_video = false)
    {
        if (!is_array($val)) {
            $val = [$val];
        }

        foreach ($val as $rsl) {
            if (empty($rsl)) {
                continue;
            }

            $rsl = Sanitizer::sanitize($rsl);

            $resolution = new \ImageResolution();
            if (!$resolution->getFromDBByCrit(['name' => $rsl])) {
                $resolution->add([
                    'name'         => $rsl,
                    'is_video'     => $is_video,
                    'is_dynamic'   => 1
                ]);
            }

            $cam_resolutions = new \Item_DeviceCamera_ImageResolution();
            $data = [
                'items_devicecameras_id' => $itemdevice->fields['devicecameras_id'],
                'imageresolutions_id' => $resolution->fields['id'],
                'is_dynamic' => 1
            ];

            if (!$cam_resolutions->getFromDBByCrit($data)) {
                $cam_resolutions->add($data);
            }
        }
    }

    private function handleFormats($itemdevice, $val)
    {
        if (!is_array($val)) {
            $val = [$val];
        }

        $format = new \ImageFormat();
        foreach ($val as $fmt) {
            if (empty($fmt)) {
                continue;
            }

            $fmt = Sanitizer::sanitize($fmt);

            if (!$format->getFromDBByCrit(['name' => $fmt])) {
                $format->add([
                    'name' => $fmt,
                    'is_dynamic' => 1
                ]);
            }

            $cam_formats = new \Item_DeviceCamera_ImageFormat();
            $data = [
                'items_devicecameras_id' => $itemdevice->fields['devicecameras_id'],
                'imageformats_id' => $format->fields['id'],
                'is_dynamic' => 1
            ];

            if (!$cam_formats->getFromDBByCrit($data)) {
                $cam_formats->add($data);
            }
        }
    }

    public function checkConf(Conf $conf): bool
    {
        return true;
    }

    public function getItemtype(): string
    {
        return \Item_DeviceCamera::class;
    }
}
